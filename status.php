<?php
require_once 'config.php';

echo "<h1>HVAC Tool - System Status</h1>\n";

// Check database connection
try {
    $pdo->query("SELECT 1");
    echo "✅ Database connection: Working<br>\n";
} catch (PDOException $e) {
    echo "❌ Database connection: Failed - " . $e->getMessage() . "<br>\n";
}

// Check tables exist
$tables = ['companies', 'blog_posts', 'keyword_searches'];
foreach ($tables as $table) {
    try {
        $result = $pdo->query("DESCRIBE `$table`");
        echo "✅ Table `$table`: Exists<br>\n";
    } catch (PDOException $e) {
        echo "❌ Table `$table`: Missing<br>\n";
    }
}

// Count records
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM companies");
    $company_count = $stmt->fetch()['count'];
    echo "📊 Companies: $company_count<br>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM blog_posts");
    $posts_count = $stmt->fetch()['count'];
    echo "📊 Blog Posts: $posts_count<br>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM keyword_searches");
    $searches_count = $stmt->fetch()['count'];
    echo "📊 Keyword Searches: $searches_count<br>\n";
    
} catch (PDOException $e) {
    echo "❌ Error counting records: " . $e->getMessage() . "<br>\n";
}

echo "<br><h2>Quick Links</h2>\n";
echo "<p>";
echo "<a href='dashboard.php' style='margin-right: 15px; color: #0073e6;'>🏠 Dashboard</a>";
echo "<a href='admin.php' style='margin-right: 15px; color: #0073e6;'>⚙️ Admin Panel</a>";
echo "<a href='setup.php' style='margin-right: 15px; color: #0073e6;'>🔧 Setup Database</a>";
echo "</p>";
?>