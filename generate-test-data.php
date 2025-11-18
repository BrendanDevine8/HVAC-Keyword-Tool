<?php
require_once 'config.php';

echo "<h1>Generate Test Blog Posts</h1>\n";

// Test keywords to generate posts for
$test_keywords = [
    'ac not working',
    'furnace repair', 
    'heat pump troubleshooting',
    'hvac maintenance',
    'ac leaking water'
];

$test_zips = ['05674', '05673', '05675'];
$company_id = 1; // Assuming Brendan's HVAC

echo "<p>Generating test blog posts for company ID $company_id...</p>\n";

foreach ($test_keywords as $keyword) {
    foreach ($test_zips as $zip) {
        // Check if this combination already exists
        $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE company_id = ? AND zip_code = ? AND keyword = ?");
        $stmt->execute([$company_id, $zip, $keyword]);
        
        if ($stmt->fetch()) {
            echo "<p>⏭️ Skipping: $keyword in $zip (already exists)</p>\n";
            continue;
        }
        
        // Generate a simple test post
        $title = ucwords($keyword) . " in ZIP $zip - " . date('M j, Y');
        $content = "<h1>$title</h1>\n<p>This is a test blog post about $keyword in ZIP code $zip.</p>";
        $word_count = str_word_count(strip_tags($content));
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO blog_posts (company_id, zip_code, keyword, title, content, word_count)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$company_id, $zip, $keyword, $title, $content, $word_count]);
            $post_id = $pdo->lastInsertId();
            
            echo "<p>✅ Created: <strong>$title</strong> (ID: $post_id)</p>\n";
            
        } catch (PDOException $e) {
            echo "<p>❌ Error creating post for '$keyword' in $zip: " . $e->getMessage() . "</p>\n";
        }
        
        // Small delay to vary timestamps
        usleep(100000); // 0.1 second
    }
}

echo "<br><h2>Test Data Generated!</h2>\n";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a> | <a href='admin.php'>View in Admin Panel</a></p>";
?>