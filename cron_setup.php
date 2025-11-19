<?php
/**
 * Cron Job Setup Helper
 * Helps configure automated queue processing via cron jobs
 */

require_once __DIR__ . "/config.php";

$php_path = '/Applications/MAMP/bin/php/php8.2.0/bin/php';
$script_path = __DIR__ . '/queue_processor.php';
$log_path = __DIR__ . '/logs/cron.log';

echo "<h1>🕒 Cron Job Setup for Automated Blog Posting</h1>\n";

echo "<h2>Current System Status</h2>\n";

// Check if queue processor exists
if (file_exists($script_path)) {
    echo "✅ Queue processor script exists: <code>{$script_path}</code><br>\n";
} else {
    echo "❌ Queue processor script missing: <code>{$script_path}</code><br>\n";
    exit;
}

// Check if logs directory exists  
if (is_dir(dirname($log_path))) {
    echo "✅ Logs directory exists: <code>" . dirname($log_path) . "</code><br>\n";
} else {
    echo "❌ Logs directory missing: <code>" . dirname($log_path) . "</code><br>\n";
    echo "<p>Creating logs directory...</p>\n";
    mkdir(dirname($log_path), 0755, true);
    echo "✅ Created logs directory<br>\n";
}

// Check PHP executable
if (file_exists($php_path)) {
    echo "✅ PHP executable found: <code>{$php_path}</code><br>\n";
} else {
    echo "⚠️ PHP executable not found at: <code>{$php_path}</code><br>\n";
    echo "Please update the path in this script or use: <code>/usr/bin/php</code><br>\n";
}

// Test queue processor
echo "<h2>Testing Queue Processor</h2>\n";
echo "<p>Running a test execution...</p>\n";

$test_command = "cd " . escapeshellarg(__DIR__) . " && " . escapeshellarg($php_path) . " " . escapeshellarg($script_path) . " 2>&1";
$test_output = shell_exec($test_command);

echo "<div style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; font-family: monospace; white-space: pre-wrap;'>";
echo htmlspecialchars($test_output);
echo "</div>\n";

// Cron job configuration
echo "<h2>Cron Job Configuration</h2>\n";

$cron_entry_5min = "*/5 * * * * {$php_path} {$script_path} >> {$log_path} 2>&1";
$cron_entry_1min = "* * * * * {$php_path} {$script_path} >> {$log_path} 2>&1";

echo "<p>Choose your automation frequency:</p>\n";

echo "<h3>Option 1: Every 5 Minutes (Recommended)</h3>\n";
echo "<p>This will check for pending posts every 5 minutes, which provides good responsiveness while not overloading your server.</p>\n";
echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; margin: 10px 0;'>";
echo htmlspecialchars($cron_entry_5min);
echo "</div>\n";

echo "<h3>Option 2: Every Minute (High Frequency)</h3>\n";
echo "<p>This will check for pending posts every minute. Use this only if you need very fast posting or have high-volume requirements.</p>\n";
echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; margin: 10px 0;'>";
echo htmlspecialchars($cron_entry_1min);
echo "</div>\n";

echo "<h2>Setup Instructions</h2>\n";
echo "<ol>\n";
echo "<li><strong>Open Terminal</strong></li>\n";
echo "<li><strong>Edit your crontab:</strong><br><code>crontab -e</code></li>\n";
echo "<li><strong>Add one of the cron entries above</strong> (copy and paste the entire line)</li>\n";
echo "<li><strong>Save and exit</strong> (in nano: Ctrl+O, then Ctrl+X)</li>\n";
echo "<li><strong>Verify it was added:</strong><br><code>crontab -l</code></li>\n";
echo "</ol>\n";

echo "<h2>Monitoring and Troubleshooting</h2>\n";

echo "<h3>View Cron Log</h3>\n";
echo "<p>Monitor automation activity with:</p>\n";
echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; margin: 10px 0;'>";
echo "tail -f {$log_path}";
echo "</div>\n";

echo "<h3>View Queue Processor Logs</h3>\n";
echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; margin: 10px 0;'>";
echo "tail -f " . dirname($log_path) . "/queue_processor.log";
echo "</div>\n";

echo "<h3>Manual Testing</h3>\n";
echo "<p>Test the queue processor manually:</p>\n";
echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; margin: 10px 0;'>";
echo "cd " . __DIR__ . "<br>";
echo "{$php_path} queue_processor.php";
echo "</div>\n";

// Check current crontab
echo "<h2>Current Crontab</h2>\n";
$current_cron = shell_exec('crontab -l 2>/dev/null');
if ($current_cron) {
    echo "<div style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; font-family: monospace; white-space: pre-wrap;'>";
    echo htmlspecialchars($current_cron);
    echo "</div>\n";
} else {
    echo "<p style='color: orange;'>No crontab entries found. You'll need to add the automation cron job.</p>\n";
}

// System health check
echo "<h2>System Health Check</h2>\n";

try {
    // Check if there are any companies with automation enabled
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM companies WHERE auto_posting_enabled = 1");
    $enabled_companies = $stmt->fetch()['count'];
    
    if ($enabled_companies > 0) {
        echo "✅ {$enabled_companies} companies have automation enabled<br>\n";
    } else {
        echo "⚠️ No companies have automation enabled<br>\n";
    }
    
    // Check for pending queue items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM auto_posting_queue WHERE status = 'pending'");
    $pending_items = $stmt->fetch()['count'];
    
    if ($pending_items > 0) {
        echo "📋 {$pending_items} pending queue items ready for processing<br>\n";
    } else {
        echo "✅ No pending queue items<br>\n";
    }
    
    // Check for overdue items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM auto_posting_queue WHERE status = 'pending' AND scheduled_for <= NOW()");
    $overdue_items = $stmt->fetch()['count'];
    
    if ($overdue_items > 0) {
        echo "<span style='color: red;'>⚠️ {$overdue_items} overdue queue items that should be processed immediately</span><br>\n";
    } else {
        echo "✅ No overdue queue items<br>\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Important Notes</h2>\n";
echo "<ul>\n";
echo "<li>🔒 <strong>Security:</strong> Make sure your Claude API key is secure and not exposed in public repositories</li>\n";
echo "<li>💰 <strong>API Costs:</strong> Each generated post uses your Claude API quota. Monitor your usage at <a href='https://console.anthropic.com/' target='_blank'>console.anthropic.com</a></li>\n";
echo "<li>📊 <strong>Rate Limiting:</strong> The queue processor includes a 0.1-second delay between posts to prevent API rate limiting</li>\n";
echo "<li>🚫 <strong>Error Handling:</strong> Failed posts are marked as 'failed' in the queue and won't retry automatically</li>\n";
echo "<li>🔄 <strong>Queue Management:</strong> Use the <a href='automation.php'>Automation Settings</a> to manage keywords, ZIP targets, and generate new queue items</li>\n";
echo "</ul>\n";

echo "<br><p><a href='dashboard.php'>← Back to Dashboard</a> | <a href='automation.php'>Automation Settings</a> | <a href='debug_automation.php'>Debug System</a></p>\n";
?>