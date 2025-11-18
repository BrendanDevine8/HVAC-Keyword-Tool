<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../config.php";

header('Content-Type: text/html; charset=utf-8');

// Read keyword + zip + company_id
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$zip     = isset($_GET['zip']) ? trim($_GET['zip']) : '';
$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;

if ($keyword === '' || $zip === '' || $company_id <= 0) {
    echo "<p>Missing keyword, ZIP, or company ID. Example:<br><br>
    <code>?company_id=1&zip=90001&keyword=ac%20not%20blowing%20cold%20air</code></p>";
    exit;
}

// Verify company exists
try {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    
    if (!$company) {
        echo "<p>Error: Company not found.</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Check if this exact content was already generated
try {
    $stmt = $pdo->prepare("
        SELECT id, title, content, generated_at 
        FROM blog_posts 
        WHERE company_id = ? AND zip_code = ? AND keyword = ?
        ORDER BY generated_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$company_id, $zip, $keyword]);
    $existing_post = $stmt->fetch();
    
    if ($existing_post) {
        // Show existing post with regenerate option
        echo "
        <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px; border-radius: 4px;'>
            <h3>📄 Previously Generated Content Found</h3>
            <p><strong>Company:</strong> " . htmlspecialchars($company['company_name']) . "</p>
            <p><strong>Generated:</strong> " . date('M j, Y g:i A', strtotime($existing_post['generated_at'])) . "</p>
            <p><a href='?company_id=$company_id&zip=$zip&keyword=" . urlencode($keyword) . "&regenerate=1' style='color: #0073e6;'>🔄 Generate New Version</a></p>
        </div>
        <hr>";
        
        if (!isset($_GET['regenerate'])) {
            echo $existing_post['content'];
            exit;
        }
    }
} catch (PDOException $e) {
    // Continue with generation if check fails
}

/**
 * ZIP → city/county/state lookup from database
 */
function getLocationFromZip($zip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM zip_codes WHERE zip_code = ?");
        $stmt->execute([$zip]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                "city" => $result['city'],
                "county" => $result['county'], 
                "state" => $result['state_code'],
                "area" => $result['area_description'] ?: $result['city'],
                "climate_zone" => $result['climate_zone'],
                "metro_area" => $result['metro_area']
            ];
        }
    } catch (PDOException $e) {
        // Fall back to default if database error
    }
    
    // Default fallback for unknown ZIP codes
    return [
        "city" => "Your Local Area",
        "county" => "Your County", 
        "state" => "",
        "area" => "your neighborhood",
        "climate_zone" => "Mixed",
        "metro_area" => "Your Metro Area"
    ];
}

$loc = getLocationFromZip($zip);

$city   = $loc["city"];
$county = $loc["county"];
$state  = $loc["state"];
$area   = $loc["area"];

/**
 * Enhanced Claude Prompt with Company Info
 */
$company_name = $company['company_name'];
$company_type = $company['company_type'];
$company_location = $company['location'];
$company_hours = $company['hours'];

$prompt = "
Write a unique, SEO-optimized HVAC blog article based on the following:

Primary Keyword: \"$keyword\"
ZIP Code: $zip
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
$url = "https://api.anthropic.com/v1/messages";

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
    "anthropic-version: " . (isset($CLAUDE_API_VERSION) ? $CLAUDE_API_VERSION : "2023-06-01")
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Check for Claude API errors
if (!isset($data["content"][0]["text"])) {
    echo "<h3>❌ Claude API Error</h3>";
    echo "<pre style='white-space: pre-wrap; background:#222; color:#0f0; padding:15px;'>";
    print_r($data);
    echo "</pre>";
    exit;
}

// Extract generated content
$post_content = $data["content"][0]["text"];

// Extract title from the content (assuming it starts with <h1>)
$title = '';
if (preg_match('/<h1[^>]*>(.*?)<\/h1>/', $post_content, $matches)) {
    $title = strip_tags($matches[1]);
}
if (empty($title)) {
    $title = ucwords($keyword) . " in " . $city . " - " . $company_name;
}

// Calculate approximate word count
$word_count = str_word_count(strip_tags($post_content));

// Store in database
try {
    $stmt = $pdo->prepare("
        INSERT INTO blog_posts (company_id, zip_code, keyword, title, content, word_count)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$company_id, $zip, $keyword, $title, $post_content, $word_count]);
    $post_id = $pdo->lastInsertId();
    
    // Track keyword search
    $stmt = $pdo->prepare("
        INSERT INTO keyword_searches (company_id, zip_code, keyword, search_count) 
        VALUES (?, ?, ?, 1) 
        ON DUPLICATE KEY UPDATE 
        search_count = search_count + 1, 
        last_searched = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$company_id, $zip, $keyword]);
    
    // Add success notification
    echo "
    <div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px; border-radius: 4px;'>
        <h3>✅ Content Generated Successfully!</h3>
        <p><strong>Post ID:</strong> $post_id | <strong>Word Count:</strong> $word_count | <strong>Company:</strong> " . htmlspecialchars($company_name) . "</p>
        <p><a href='../dashboard.php' style='color: #0073e6;'>← Back to Dashboard</a></p>
    </div>
    <hr>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px; border-radius: 4px;'>";
    echo "<h3>⚠️ Content generated but not saved to database</h3>";
    echo "<p>Database error: " . $e->getMessage() . "</p>";
    echo "</div><hr>";
}

// Output the generated content
echo $post_content;
