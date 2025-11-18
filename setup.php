<?php
require_once 'config.php';

echo "<h1>Database Setup</h1>\n";

// Read the SQL setup file
$sql_file = __DIR__ . '/setup_database.sql';
if (!file_exists($sql_file)) {
    die("SQL setup file not found!");
}

$sql_content = file_get_contents($sql_file);

// Remove comments and clean up the SQL
$sql_content = preg_replace('/--.*$/m', '', $sql_content);
$sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

// Split by semicolons and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql_content)));

echo "<h2>Executing SQL Statements:</h2>\n";

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    // Show what we're executing
    $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 80);
    echo "<p><strong>Executing:</strong> " . htmlspecialchars($preview) . "...</p>\n";
    
    try {
        $result = $pdo->exec($statement);
        echo "✅ <span style='color: green;'>SUCCESS</span><br>\n";
        $success_count++;
    } catch (PDOException $e) {
        echo "❌ <span style='color: red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</span><br>\n";
        echo "<details><summary>SQL Statement</summary><pre>" . htmlspecialchars($statement) . "</pre></details><br>\n";
        $error_count++;
    }
    
    echo "<hr>\n";
}

echo "<h2>Setup Summary</h2>\n";
echo "<p>✅ Successful: $success_count</p>\n";
echo "<p>❌ Errors: $error_count</p>\n";

if ($error_count == 0) {
    echo "<h3 style='color: green;'>🎉 Database Setup Complete!</h3>\n";
} else {
    echo "<h3 style='color: red;'>⚠️ Setup completed with errors</h3>\n";
}

// Test the tables
echo "<h2>Testing Created Tables:</h2>\n";

$test_tables = ['companies', 'blog_posts', 'keyword_searches'];
foreach ($test_tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll();
        echo "✅ <strong>$table</strong>: " . count($columns) . " columns<br>\n";
    } catch (PDOException $e) {
        echo "❌ <strong>$table</strong>: " . htmlspecialchars($e->getMessage()) . "<br>\n";
    }
}

echo "<br><h2>Quick Links</h2>\n";
echo "<p>";
echo "<a href='status.php' style='margin-right: 15px; color: #0073e6;'>📊 Check Status</a>";
echo "<a href='dashboard.php' style='margin-right: 15px; color: #0073e6;'>🏠 Dashboard</a>";
echo "<a href='admin.php' style='margin-right: 15px; color: #0073e6;'>⚙️ Admin Panel</a>";
echo "</p>";
?>