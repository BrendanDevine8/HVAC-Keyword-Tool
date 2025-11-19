<?php
/**
 * Debug Automation System
 * Check automation settings and queue status for companies
 */

require_once __DIR__ . "/config.php";

echo "<h1>🔍 Automation System Debug</h1>\n";

try {
    // Get all companies with automation settings
    echo "<h2>Company Automation Settings</h2>\n";
    $stmt = $pdo->query("
        SELECT id, company_name, company_type, 
               auto_posting_enabled, auto_posting_frequency, auto_posting_interval,
               last_auto_post, next_auto_post, created_at
        FROM companies 
        ORDER BY company_name
    ");
    
    $companies = $stmt->fetchAll();
    
    if (empty($companies)) {
        echo "<p>❌ No companies found in database</p>\n";
        exit;
    }
    
    foreach ($companies as $company) {
        echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
        echo "<h3>{$company['company_name']} (ID: {$company['id']})</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Type:</strong> {$company['company_type']}</li>\n";
        echo "<li><strong>Auto Posting:</strong> " . ($company['auto_posting_enabled'] ? '✅ Enabled' : '❌ Disabled') . "</li>\n";
        echo "<li><strong>Frequency:</strong> {$company['auto_posting_frequency']} (every {$company['auto_posting_interval']})</li>\n";
        echo "<li><strong>Last Post:</strong> " . ($company['last_auto_post'] ?: 'Never') . "</li>\n";
        echo "<li><strong>Next Post:</strong> " . ($company['next_auto_post'] ?: 'Not scheduled') . "</li>\n";
        echo "</ul>\n";
        
        // Check keywords for this company
        $keyword_stmt = $pdo->prepare("
            SELECT keyword_type, keyword_pattern, priority 
            FROM auto_posting_keywords 
            WHERE company_id = ? 
            ORDER BY priority DESC, keyword_type
        ");
        $keyword_stmt->execute([$company['id']]);
        $keywords = $keyword_stmt->fetchAll();
        
        echo "<p><strong>Keywords:</strong> ";
        if (empty($keywords)) {
            echo "<span style='color: red;'>❌ No keywords configured</span>";
        } else {
            echo "<span style='color: green;'>✅ " . count($keywords) . " keywords</span>";
            echo "<ul>";
            foreach ($keywords as $kw) {
                echo "<li>[{$kw['keyword_type']}] {$kw['keyword_pattern']} (priority: {$kw['priority']})</li>";
            }
            echo "</ul>";
        }
        echo "</p>\n";
        
        // Check ZIP targets for this company
        $zip_stmt = $pdo->prepare("
            SELECT zip_code, priority, created_at 
            FROM auto_posting_zip_targets 
            WHERE company_id = ? 
            ORDER BY priority DESC, zip_code
        ");
        $zip_stmt->execute([$company['id']]);
        $zips = $zip_stmt->fetchAll();
        
        echo "<p><strong>ZIP Targets:</strong> ";
        if (empty($zips)) {
            echo "<span style='color: red;'>❌ No ZIP codes configured</span>";
        } else {
            echo "<span style='color: green;'>✅ " . count($zips) . " ZIP codes</span>";
            echo "<ul>";
            foreach ($zips as $zip) {
                echo "<li>{$zip['zip_code']} (priority: {$zip['priority']})</li>";
            }
            echo "</ul>";
        }
        echo "</p>\n";
        
        // Check queue items for this company
        $queue_stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count 
            FROM auto_posting_queue 
            WHERE company_id = ? 
            GROUP BY status
        ");
        $queue_stmt->execute([$company['id']]);
        $queue_stats = $queue_stmt->fetchAll();
        
        echo "<p><strong>Queue Items:</strong> ";
        if (empty($queue_stats)) {
            echo "<span style='color: orange;'>⚠️ No queue items</span>";
        } else {
            foreach ($queue_stats as $stat) {
                $color = match($stat['status']) {
                    'pending' => 'orange',
                    'processing' => 'blue',
                    'completed' => 'green',
                    'failed' => 'red',
                    default => 'gray'
                };
                echo "<span style='color: {$color};'>{$stat['status']}: {$stat['count']}</span> ";
            }
        }
        echo "</p>\n";
        
        echo "</div>\n";
    }
    
    // Check recent queue activity
    echo "<h2>Recent Queue Activity</h2>\n";
    $queue_stmt = $pdo->query("
        SELECT aq.*, c.company_name 
        FROM auto_posting_queue aq 
        JOIN companies c ON aq.company_id = c.id 
        ORDER BY aq.created_at DESC 
        LIMIT 20
    ");
    $recent_queue = $queue_stmt->fetchAll();
    
    if (empty($recent_queue)) {
        echo "<p>No queue items found</p>\n";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Company</th><th>ZIP</th><th>Keyword</th><th>Status</th><th>Scheduled</th><th>Created</th></tr>\n";
        foreach ($recent_queue as $item) {
            $status_color = match($item['status']) {
                'pending' => 'orange',
                'processing' => 'blue', 
                'completed' => 'green',
                'failed' => 'red',
                default => 'gray'
            };
            echo "<tr>\n";
            echo "<td>{$item['company_name']}</td>\n";
            echo "<td>{$item['zip_code']}</td>\n";
            echo "<td>" . substr($item['keyword'], 0, 50) . "...</td>\n";
            echo "<td style='color: {$status_color};'>{$item['status']}</td>\n";
            echo "<td>{$item['scheduled_for']}</td>\n";
            echo "<td>{$item['created_at']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check for overdue items
    echo "<h2>Overdue Queue Items</h2>\n";
    $overdue_stmt = $pdo->query("
        SELECT aq.*, c.company_name 
        FROM auto_posting_queue aq 
        JOIN companies c ON aq.company_id = c.id 
        WHERE aq.status = 'pending' AND aq.scheduled_for <= NOW()
        ORDER BY aq.scheduled_for ASC
    ");
    $overdue_items = $overdue_stmt->fetchAll();
    
    if (empty($overdue_items)) {
        echo "<p style='color: green;'>✅ No overdue items</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Found " . count($overdue_items) . " overdue items that should have been processed:</p>\n";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Company</th><th>ZIP</th><th>Keyword</th><th>Scheduled</th><th>Hours Overdue</th></tr>\n";
        foreach ($overdue_items as $item) {
            $scheduled_time = strtotime($item['scheduled_for']);
            $hours_overdue = round((time() - $scheduled_time) / 3600, 1);
            echo "<tr>\n";
            echo "<td>{$item['company_name']}</td>\n";
            echo "<td>{$item['zip_code']}</td>\n";
            echo "<td>" . substr($item['keyword'], 0, 50) . "...</td>\n";
            echo "<td>{$item['scheduled_for']}</td>\n";
            echo "<td style='color: red;'>{$hours_overdue} hours</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // System health check
    echo "<h2>System Health Check</h2>\n";
    $health_issues = [];
    
    // Check if automation tables exist
    $tables_to_check = [
        'auto_posting_keywords',
        'auto_posting_zip_targets', 
        'auto_posting_queue',
        'auto_posting_stats'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            $pdo->query("SELECT 1 FROM {$table} LIMIT 1")->fetch();
        } catch (PDOException $e) {
            $health_issues[] = "Table '{$table}' does not exist or is not accessible";
        }
    }
    
    // Check if any companies have automation enabled but no keywords/zips
    $broken_automation = $pdo->query("
        SELECT c.company_name 
        FROM companies c 
        WHERE c.auto_posting_enabled = 1 
        AND (
            NOT EXISTS (SELECT 1 FROM auto_posting_keywords WHERE company_id = c.id)
            OR NOT EXISTS (SELECT 1 FROM auto_posting_zip_targets WHERE company_id = c.id)
        )
    ")->fetchAll();
    
    foreach ($broken_automation as $company) {
        $health_issues[] = "Company '{$company['company_name']}' has automation enabled but missing keywords or ZIP targets";
    }
    
    if (empty($health_issues)) {
        echo "<p style='color: green;'>✅ No obvious system issues detected</p>\n";
    } else {
        echo "<ul style='color: red;'>\n";
        foreach ($health_issues as $issue) {
            echo "<li>❌ {$issue}</li>\n";
        }
        echo "</ul>\n";
    }
    
    echo "<h2>Missing Components</h2>\n";
    $missing_files = [];
    
    if (!file_exists(__DIR__ . '/queue_processor.php')) {
        $missing_files[] = 'queue_processor.php - Critical automation worker script';
    }
    
    if (!file_exists(__DIR__ . '/cron_setup.php')) {
        $missing_files[] = 'cron_setup.php - Helper script for setting up scheduled execution';
    }
    
    if (empty($missing_files)) {
        echo "<p style='color: green;'>✅ All automation components present</p>\n";
    } else {
        echo "<ul style='color: red;'>\n";
        foreach ($missing_files as $file) {
            echo "<li>❌ Missing: {$file}</li>\n";
        }
        echo "</ul>\n";
    }

} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>\n";
}

echo "<br><p><a href='dashboard.php'>← Back to Dashboard</a> | <a href='automation.php'>Automation Settings</a></p>\n";
?>