<?php
/**
 * Automation Management API
 * Handles automated posting configuration and queue management
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Handle form data vs JSON input
    $input = null;
    if (isset($_POST['company_id'])) {
        // Form submission
        $input = $_POST;
    } else {
        // JSON input
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    if (!$input) {
        echo json_encode(['error' => 'No input data']);
        exit;
    }
    
    try {
        $action = $input['action'] ?? null;
        
        // If no action specified, check if it's automation settings update
        if (!$action && isset($input['company_id']) && isset($input['auto_posting_enabled'])) {
            $action = 'update_settings';
        }
        
        if (!$action) {
            echo json_encode(['error' => 'No action specified']);
            exit;
        }
        
        switch ($action) {
            case 'update_settings':
                updateAutomationSettings($pdo, $input);
                break;
                
            case 'add_keyword':
                addKeyword($pdo, $input);
                break;
                
            case 'remove_keyword':
                removeKeyword($pdo, $input);
                break;
                
            case 'add_zip_target':
                addZipTarget($pdo, $input);
                break;
                
            case 'remove_zip_target':
                removeZipTarget($pdo, $input);
                break;
                
            case 'import_zips_from_posts':
                importZipsFromPosts($pdo, $input);
                break;
                
            case 'generate_queue':
                generateQueue($pdo, $input);
                break;
                
            case 'clear_completed_queue':
                clearCompletedQueue($pdo, $input);
                break;
                
            default:
                echo json_encode(['error' => 'Unknown action: ' . $action]);
                exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['error' => 'Only POST method allowed']);
}

/**
 * Update automation settings for a company
 */
function updateAutomationSettings($pdo, $input) {
    $company_id = (int)($input['company_id'] ?? 0);
    
    if (!$company_id) {
        echo json_encode(['error' => 'Company ID required']);
        return;
    }
    
    $enabled = (int)($input['auto_posting_enabled'] ?? 0);
    $frequency = $input['auto_posting_frequency'] ?? 'daily';
    $interval = (int)($input['auto_posting_interval'] ?? 1);
    
    // Validate frequency
    if (!in_array($frequency, ['hourly', 'daily', 'weekly', 'monthly'])) {
        echo json_encode(['error' => 'Invalid frequency']);
        return;
    }
    
    // Calculate next post time if enabling
    $next_post = null;
    if ($enabled) {
        $next_post = calculateNextPostTime($frequency, $interval);
    }
    
    $stmt = $pdo->prepare("
        UPDATE companies 
        SET auto_posting_enabled = ?, 
            auto_posting_frequency = ?, 
            auto_posting_interval = ?,
            next_auto_post = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$enabled, $frequency, $interval, $next_post, $company_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Settings updated successfully',
        'next_post' => $next_post
    ]);
}

/**
 * Add a keyword targeting rule
 */
function addKeyword($pdo, $input) {
    $company_id = (int)($input['company_id'] ?? 0);
    $pattern = trim($input['keyword_pattern'] ?? '');
    $type = $input['keyword_type'] ?? 'include';
    $priority = (int)($input['priority'] ?? 0);
    
    if (!$company_id || !$pattern) {
        echo json_encode(['error' => 'Company ID and keyword pattern required']);
        return;
    }
    
    if (!in_array($type, ['include', 'exclude'])) {
        echo json_encode(['error' => 'Invalid keyword type']);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO auto_posting_keywords (company_id, keyword_type, keyword_pattern, priority)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$company_id, $type, $pattern, $priority]);
    
    echo json_encode(['success' => true, 'message' => 'Keyword added successfully']);
}

/**
 * Remove a keyword
 */
function removeKeyword($pdo, $input) {
    $keyword_id = (int)($input['keyword_id'] ?? 0);
    
    if (!$keyword_id) {
        echo json_encode(['error' => 'Keyword ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM auto_posting_keywords WHERE id = ?");
    $stmt->execute([$keyword_id]);
    
    echo json_encode(['success' => true, 'message' => 'Keyword removed successfully']);
}

/**
 * Add a ZIP code target
 */
function addZipTarget($pdo, $input) {
    $company_id = (int)($input['company_id'] ?? 0);
    $zip_code = trim($input['zip_code'] ?? '');
    $priority = (int)($input['priority'] ?? 0);
    
    if (!$company_id || !$zip_code) {
        echo json_encode(['error' => 'Company ID and ZIP code required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO auto_posting_zip_targets (company_id, zip_code, priority)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$company_id, $zip_code, $priority]);
        
        echo json_encode(['success' => true, 'message' => 'ZIP target added successfully']);
        
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') { // Duplicate entry
            echo json_encode(['error' => 'ZIP code already exists for this company']);
        } else {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

/**
 * Remove a ZIP target
 */
function removeZipTarget($pdo, $input) {
    $zip_id = (int)($input['zip_id'] ?? 0);
    
    if (!$zip_id) {
        echo json_encode(['error' => 'ZIP ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM auto_posting_zip_targets WHERE id = ?");
    $stmt->execute([$zip_id]);
    
    echo json_encode(['success' => true, 'message' => 'ZIP target removed successfully']);
}

/**
 * Import ZIP codes from existing blog posts
 */
function importZipsFromPosts($pdo, $input) {
    $company_id = (int)($input['company_id'] ?? 0);
    
    if (!$company_id) {
        echo json_encode(['error' => 'Company ID required']);
        return;
    }
    
    // Get unique ZIP codes from existing posts
    $stmt = $pdo->prepare("
        SELECT DISTINCT zip_code 
        FROM blog_posts 
        WHERE company_id = ? AND zip_code IS NOT NULL AND zip_code != ''
    ");
    $stmt->execute([$company_id]);
    $existing_zips = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $imported = 0;
    foreach ($existing_zips as $zip) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO auto_posting_zip_targets (company_id, zip_code, priority, posts_generated)
                VALUES (?, ?, 0, 
                    (SELECT COUNT(*) FROM blog_posts WHERE company_id = ? AND zip_code = ?)
                )
            ");
            $stmt->execute([$company_id, $zip, $company_id, $zip]);
            $imported++;
        } catch (PDOException $e) {
            // Skip duplicates
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Imported $imported ZIP codes",
        'imported_count' => $imported
    ]);
}

/**
 * Generate queue items for automated posting
 */
function generateQueue($pdo, $input) {
    $company_id = (int)($input['company_id'] ?? 0);
    
    if (!$company_id) {
        echo json_encode(['error' => 'Company ID required']);
        return;
    }
    
    // Get company automation settings
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? AND auto_posting_enabled = 1");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    
    if (!$company) {
        echo json_encode(['error' => 'Company not found or automation not enabled']);
        return;
    }
    
    // Get keywords for this company
    $stmt = $pdo->prepare("
        SELECT * FROM auto_posting_keywords 
        WHERE company_id = ? AND keyword_type = 'include' 
        ORDER BY priority DESC, RAND()
    ");
    $stmt->execute([$company_id]);
    $keywords = $stmt->fetchAll();
    
    // Get ZIP targets
    $stmt = $pdo->prepare("
        SELECT * FROM auto_posting_zip_targets 
        WHERE company_id = ? 
        ORDER BY posts_generated ASC, priority DESC, last_posted ASC
    ");
    $stmt->execute([$company_id]);
    $zip_targets = $stmt->fetchAll();
    
    if (empty($keywords) || empty($zip_targets)) {
        echo json_encode(['error' => 'No keywords or ZIP targets configured']);
        return;
    }
    
    // Generate queue items for next period
    $queue_items = 0;
    $frequency = $company['auto_posting_frequency'];
    $interval = $company['auto_posting_interval'];
    
    // Calculate how many posts to generate
    $posts_per_period = calculatePostsPerPeriod($frequency, $interval);
    
    $start_time = new DateTime($company['next_auto_post'] ?? 'now');
    
    for ($i = 0; $i < $posts_per_period; $i++) {
        $keyword = $keywords[array_rand($keywords)]['keyword_pattern'];
        $zip_target = $zip_targets[$i % count($zip_targets)];
        
        $scheduled_time = clone $start_time;
        $scheduled_time->add(new DateInterval('PT' . ($i * 60) . 'M')); // Space posts 1 hour apart
        
        // Check if this combination already exists in queue
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM auto_posting_queue 
            WHERE company_id = ? AND zip_code = ? AND keyword = ? AND status IN ('pending', 'processing')
        ");
        $stmt->execute([$company_id, $zip_target['zip_code'], $keyword]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO auto_posting_queue (company_id, zip_code, keyword, scheduled_for)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $company_id, 
                $zip_target['zip_code'], 
                $keyword, 
                $scheduled_time->format('Y-m-d H:i:s')
            ]);
            $queue_items++;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Generated $queue_items queue items",
        'queue_items' => $queue_items
    ]);
}

/**
 * Clear completed queue items
 */
function clearCompletedQueue($pdo, $input) {
    $company_id = (int)($input['company_id'] ?? 0);
    
    if (!$company_id) {
        echo json_encode(['error' => 'Company ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        DELETE FROM auto_posting_queue 
        WHERE company_id = ? AND status = 'completed'
    ");
    $stmt->execute([$company_id]);
    
    $deleted = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => "Cleared $deleted completed items",
        'deleted_count' => $deleted
    ]);
}

/**
 * Calculate next post time based on frequency and interval
 */
function calculateNextPostTime($frequency, $interval) {
    $now = new DateTime();
    
    switch ($frequency) {
        case 'hourly':
            $now->add(new DateInterval('PT' . $interval . 'H'));
            break;
        case 'daily':
            $now->add(new DateInterval('P' . $interval . 'D'));
            break;
        case 'weekly':
            $now->add(new DateInterval('P' . ($interval * 7) . 'D'));
            break;
        case 'monthly':
            $now->add(new DateInterval('P' . $interval . 'M'));
            break;
    }
    
    return $now->format('Y-m-d H:i:s');
}

/**
 * Calculate how many posts to generate per period
 */
function calculatePostsPerPeriod($frequency, $interval) {
    switch ($frequency) {
        case 'hourly':
            return min(5, $interval); // Max 5 posts per hour
        case 'daily':
            return min(10, $interval * 2); // 2 posts per day max
        case 'weekly':
            return min(20, $interval * 5); // 5 posts per week max
        case 'monthly':
            return min(50, $interval * 15); // 15 posts per month max
        default:
            return 1;
    }
}
?>