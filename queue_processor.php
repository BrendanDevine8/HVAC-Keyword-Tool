<?php
/**
 * Queue Processor - Automated Blog Post Generator
 * This script processes pending items in the auto_posting_queue table
 * Run this via cron job every 5 minutes to maintain automated posting
 */

require_once __DIR__ . "/config.php";

// Configuration
$MAX_POSTS_PER_RUN = 10;  // Limit posts per execution to prevent overload
$MAX_EXECUTION_TIME = 300; // 5 minutes max execution time
$LOG_FILE = __DIR__ . '/logs/queue_processor.log';

// Set execution time limit
set_time_limit($MAX_EXECUTION_TIME);

// Ensure log directory exists
$log_dir = dirname($LOG_FILE);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

/**
 * Log a message with timestamp
 */
function logMessage($message, $level = 'INFO') {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}\n";
    file_put_contents($LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    
    // Also output to console if run manually
    if (php_sapi_name() === 'cli') {
        echo $log_entry;
    }
}

/**
 * Update queue item status
 */
function updateQueueStatus($pdo, $queue_id, $status, $blog_post_id = null, $error_message = null) {
    try {
        $stmt = $pdo->prepare("
            UPDATE auto_posting_queue 
            SET status = ?, 
                blog_post_id = ?, 
                error_message = ?, 
                processed_at = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $blog_post_id, $error_message, $queue_id]);
        return true;
    } catch (PDOException $e) {
        logMessage("Failed to update queue status: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Update company last_auto_post timestamp
 */
function updateCompanyLastPost($pdo, $company_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE companies 
            SET last_auto_post = NOW(),
                next_auto_post = CASE 
                    WHEN auto_posting_frequency = 'hourly' THEN DATE_ADD(NOW(), INTERVAL auto_posting_interval HOUR)
                    WHEN auto_posting_frequency = 'daily' THEN DATE_ADD(NOW(), INTERVAL auto_posting_interval DAY)
                    WHEN auto_posting_frequency = 'weekly' THEN DATE_ADD(NOW(), INTERVAL (auto_posting_interval * 7) DAY)
                    WHEN auto_posting_frequency = 'monthly' THEN DATE_ADD(NOW(), INTERVAL auto_posting_interval MONTH)
                    ELSE DATE_ADD(NOW(), INTERVAL 1 DAY)
                END
            WHERE id = ?
        ");
        
        $stmt->execute([$company_id]);
        return true;
    } catch (PDOException $e) {
        logMessage("Failed to update company timestamp: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Generate blog post content using the existing API
 */
function generateBlogPost($pdo, $company_id, $keyword, $zip_code) {
    logMessage("Generating blog post for company {$company_id}, keyword '{$keyword}', ZIP {$zip_code}");
    
    // Get company details
    $company_stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $company_stmt->execute([$company_id]);
    $company = $company_stmt->fetch();
    
    if (!$company) {
        throw new Exception("Company {$company_id} not found");
    }
    
    // Build the API request similar to generate_post.php
    global $CLAUDE_API_KEY, $CLAUDE_API_VERSION;
    
    // Get location data
    $location = getLocationFromZip($pdo, $zip_code);
    $city = $location["city"];
    $county = $location["county"];
    $state = $location["state"];
    $area = $location["area"];
    
    $company_name = $company['company_name'];
    $company_type = $company['company_type'];
    $company_location = $company['location'];
    $company_hours = $company['hours'];
    
    $prompt = "
Write a unique, SEO-optimized HVAC blog article based on the following:

Primary Keyword: \"$keyword\"
ZIP Code: $zip_code
City: $city
County: $county
State: $state
Neighborhood/Area: $area

Company Information:
Company Name: $company_name
Company Type: $company_type
Company Location: $company_location
Business Hours: $company_hours

Requirements:
• 100% unique wording (no boilerplate, no repetition)
• Naturally mention $city, $county, and $area throughout
• Include references to $company_name as the local expert
• Mention company type ($company_type) and service area
• Include the business hours where relevant (emergency services, scheduling)
• 800–1200 words minimum
• Use HVAC expert-level explanations but simple enough for homeowners
• Include clear sections with <h2> and <h3> headings
• Include bullet lists, troubleshooting steps, and safety advice
• Include DIY fixes homeowners can attempt
• Include warnings about when to call a professional
• Include local climate or regional HVAC issues if relevant
• Add a call-to-action mentioning the company's services
• Output clean HTML only — no markdown, no backticks
• Start with an <h1> title that includes the keyword and location
";
    
    // Claude API request
    $payload = [
        "model" => "claude-sonnet-4-20250514", 
        "max_tokens" => 2000,
        "messages" => [
            [
                "role" => "user",
                "content" => $prompt
            ]
        ]
    ];
    
    $headers = [
        "Content-Type: application/json",
        "x-api-key: $CLAUDE_API_KEY",
        "anthropic-version: " . ($CLAUDE_API_VERSION ?: "2023-06-01")
    ];
    
    $ch = curl_init("https://api.anthropic.com/v1/messages");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        throw new Exception("CURL error: " . $curl_error);
    }
    
    if ($http_code !== 200) {
        throw new Exception("Claude API error: HTTP {$http_code} - " . substr($response, 0, 500));
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data["content"][0]["text"])) {
        throw new Exception("Invalid Claude API response: " . substr($response, 0, 500));
    }
    
    $post_content = $data["content"][0]["text"];
    
    // Extract title
    $title = '';
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/', $post_content, $matches)) {
        $title = strip_tags($matches[1]);
    }
    if (empty($title)) {
        $title = ucfirst($keyword) . " in " . $city . ", " . $state;
    }
    
    // Calculate word count
    $word_count = str_word_count(strip_tags($post_content));
    
    // Store in database
    $insert_stmt = $pdo->prepare("
        INSERT INTO blog_posts (company_id, zip_code, keyword, title, content, word_count, generated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $insert_stmt->execute([
        $company_id,
        $zip_code,
        $keyword,
        $title,
        $post_content,
        $word_count
    ]);
    
    $blog_post_id = $pdo->lastInsertId();
    
    logMessage("Successfully created blog post {$blog_post_id}: '{$title}' ({$word_count} words)");
    
    return [
        'blog_post_id' => $blog_post_id,
        'title' => $title,
        'word_count' => $word_count,
        'content' => $post_content
    ];
}

/**
 * ZIP → city/county/state lookup from database
 */
function getLocationFromZip($pdo, $zip) {
    try {
        $stmt = $pdo->prepare("
            SELECT city, county, state, state_code, area_description as area 
            FROM zip_codes 
            WHERE zip_code = ? 
            LIMIT 1
        ");
        $stmt->execute([$zip]);
        $location = $stmt->fetch();
        
        if ($location) {
            return [
                "city" => $location["city"],
                "county" => $location["county"], 
                "state" => $location["state"],
                "area" => $location["area"]
            ];
        }
    } catch (PDOException $e) {
        logMessage("Failed to lookup ZIP {$zip}: " . $e->getMessage(), 'WARNING');
    }
    
    // Default fallback
    return [
        "city" => "Unknown City",
        "county" => "Unknown County",
        "state" => "Unknown State", 
        "area" => "Local Area"
    ];
}

/**
 * Update automation statistics
 */
function updateAutomationStats($pdo, $company_id, $success) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO auto_posting_stats (company_id, posts_generated, posts_failed, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                posts_generated = posts_generated + ?,
                posts_failed = posts_failed + ?,
                created_at = NOW()
        ");
        
        $posts_generated = $success ? 1 : 0;
        $posts_failed = $success ? 0 : 1;
        
        $stmt->execute([
            $company_id, 
            $posts_generated, 
            $posts_failed,
            $posts_generated,
            $posts_failed
        ]);
    } catch (PDOException $e) {
        logMessage("Failed to update stats: " . $e->getMessage(), 'WARNING');
    }
}

// ============= MAIN EXECUTION =============

try {
    logMessage("Queue processor starting...");
    
    // Get pending queue items that are due for processing
    $queue_query = "
        SELECT aq.*, c.company_name 
        FROM auto_posting_queue aq
        JOIN companies c ON aq.company_id = c.id
        WHERE aq.status = 'pending' 
        AND aq.scheduled_for <= NOW()
        AND c.auto_posting_enabled = 1
        ORDER BY aq.scheduled_for ASC
        LIMIT " . $MAX_POSTS_PER_RUN;
    
    $queue_stmt = $pdo->query($queue_query);
    $pending_items = $queue_stmt->fetchAll();
    
    if (empty($pending_items)) {
        logMessage("No pending queue items to process");
        exit(0);
    }
    
    logMessage("Found " . count($pending_items) . " pending items to process");
    
    $processed = 0;
    $successful = 0;
    $failed = 0;
    
    foreach ($pending_items as $item) {
        $queue_id = $item['id'];
        $company_id = $item['company_id'];
        $company_name = $item['company_name'];
        $keyword = $item['keyword'];
        $zip_code = $item['zip_code'];
        
        logMessage("Processing queue item {$queue_id} for {$company_name}: '{$keyword}' in {$zip_code}");
        
        // Update status to processing
        updateQueueStatus($pdo, $queue_id, 'processing');
        
        try {
            // Generate the blog post
            $result = generateBlogPost($pdo, $company_id, $keyword, $zip_code);
            
            // Update queue item as completed
            updateQueueStatus($pdo, $queue_id, 'completed', $result['blog_post_id']);
            
            // Update company last post timestamp
            updateCompanyLastPost($pdo, $company_id);
            
            // Update statistics
            updateAutomationStats($pdo, $company_id, true);
            
            $successful++;
            logMessage("Successfully processed queue item {$queue_id} - Created blog post {$result['blog_post_id']}");
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            logMessage("Failed to process queue item {$queue_id}: {$error_message}", 'ERROR');
            
            // Update queue item as failed
            updateQueueStatus($pdo, $queue_id, 'failed', null, $error_message);
            
            // Update statistics
            updateAutomationStats($pdo, $company_id, false);
            
            $failed++;
        }
        
        $processed++;
        
        // Small delay to prevent API rate limiting
        usleep(100000); // 0.1 second delay
    }
    
    logMessage("Queue processing completed: {$processed} items processed, {$successful} successful, {$failed} failed");

} catch (Exception $e) {
    logMessage("FATAL ERROR in queue processor: " . $e->getMessage(), 'ERROR');
    exit(1);
}

exit(0);
?>