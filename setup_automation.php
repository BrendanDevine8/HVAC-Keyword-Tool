<?php
/**
 * Setup Automated Posting Tables
 * Run this script to create all the required tables for automated posting and version history
 */

require_once __DIR__ . "/config.php";

echo "<h1>🔧 Setting up Automated Posting & Version History Tables</h1>\n";
echo "<p>Creating required database tables...</p>\n";

$tables_created = 0;
$tables_updated = 0;
$errors = 0;

try {
    // 1. Add automated posting columns to companies table
    echo "<h3>1. Updating companies table...</h3>\n";
    try {
        $pdo->exec("
            ALTER TABLE companies 
            ADD COLUMN auto_posting_enabled TINYINT(1) DEFAULT 0,
            ADD COLUMN auto_posting_frequency ENUM('hourly', 'daily', 'weekly', 'monthly') DEFAULT 'daily',
            ADD COLUMN auto_posting_interval INT DEFAULT 1 COMMENT 'How many hours/days/weeks between posts',
            ADD COLUMN last_auto_post TIMESTAMP NULL,
            ADD COLUMN next_auto_post TIMESTAMP NULL
        ");
        echo "✅ Added automation columns to companies table<br>\n";
        $tables_updated++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ Companies table already has automation columns<br>\n";
        } else {
            echo "❌ Error updating companies table: " . $e->getMessage() . "<br>\n";
            $errors++;
        }
    }

    // 2. Add version tracking columns to blog_posts table
    echo "<h3>2. Updating blog_posts table...</h3>\n";
    try {
        $pdo->exec("
            ALTER TABLE blog_posts 
            ADD COLUMN current_version INT DEFAULT 1,
            ADD COLUMN last_modified_by VARCHAR(100) DEFAULT 'system',
            ADD COLUMN last_modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ");
        echo "✅ Added version tracking columns to blog_posts table<br>\n";
        $tables_updated++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ Blog_posts table already has version tracking columns<br>\n";
        } else {
            echo "❌ Error updating blog_posts table: " . $e->getMessage() . "<br>\n";
            $errors++;
        }
    }

    // 3. Create auto_posting_keywords table
    echo "<h3>3. Creating auto_posting_keywords table...</h3>\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auto_posting_keywords (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            keyword_type ENUM('include', 'exclude') NOT NULL,
            keyword_pattern VARCHAR(255) NOT NULL,
            priority INT DEFAULT 0 COMMENT 'Higher priority keywords are used first',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            INDEX idx_company_type (company_id, keyword_type),
            INDEX idx_priority (company_id, priority DESC)
        )
    ");
    echo "✅ Created auto_posting_keywords table<br>\n";
    $tables_created++;

    // 4. Create auto_posting_zip_targets table
    echo "<h3>4. Creating auto_posting_zip_targets table...</h3>\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auto_posting_zip_targets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            zip_code VARCHAR(10) NOT NULL,
            priority INT DEFAULT 0 COMMENT 'Order in which ZIPs should be targeted',
            posts_generated INT DEFAULT 0,
            last_posted TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            UNIQUE KEY unique_company_zip (company_id, zip_code),
            INDEX idx_priority (company_id, priority DESC),
            INDEX idx_next_target (company_id, posts_generated ASC, last_posted ASC)
        )
    ");
    echo "✅ Created auto_posting_zip_targets table<br>\n";
    $tables_created++;

    // 5. Create auto_posting_queue table
    echo "<h3>5. Creating auto_posting_queue table...</h3>\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auto_posting_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            zip_code VARCHAR(10) NOT NULL,
            keyword VARCHAR(255) NOT NULL,
            scheduled_for TIMESTAMP NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            blog_post_id INT NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE SET NULL,
            INDEX idx_scheduled (status, scheduled_for),
            INDEX idx_company_schedule (company_id, scheduled_for),
            INDEX idx_status (status)
        )
    ");
    echo "✅ Created auto_posting_queue table<br>\n";
    $tables_created++;

    // 6. Create auto_posting_stats table
    echo "<h3>6. Creating auto_posting_stats table...</h3>\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auto_posting_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            date DATE NOT NULL,
            posts_generated INT DEFAULT 0,
            posts_failed INT DEFAULT 0,
            total_words INT DEFAULT 0,
            avg_response_time DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            UNIQUE KEY unique_company_date (company_id, date),
            INDEX idx_company_date (company_id, date DESC)
        )
    ");
    echo "✅ Created auto_posting_stats table<br>\n";
    $tables_created++;

    // 7. Create blog_post_revisions table
    echo "<h3>7. Creating blog_post_revisions table...</h3>\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blog_post_revisions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            version_number INT NOT NULL,
            content_size INT NOT NULL,
            is_baseline TINYINT(1) DEFAULT 0 COMMENT '1 if this is a baseline (full content), 0 if delta',
            delta_data JSON NULL COMMENT 'Delta from previous version (NULL for baselines)',
            full_content LONGTEXT NULL COMMENT 'Full content for baselines only',
            change_summary VARCHAR(500) NULL COMMENT 'Human readable summary of changes',
            created_by VARCHAR(100) DEFAULT 'system',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            comment TEXT NULL,
            FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
            UNIQUE KEY unique_post_version (post_id, version_number),
            INDEX idx_post_version (post_id, version_number DESC),
            INDEX idx_baselines (post_id, is_baseline, version_number),
            INDEX idx_created_at (created_at),
            INDEX idx_user_activity (created_by, created_at DESC)
        )
    ");
    echo "✅ Created blog_post_revisions table<br>\n";
    $tables_created++;

    // Add some sample data for testing
    echo "<h3>8. Adding sample automation data...</h3>\n";
    
    // Get first company for sample data
    $stmt = $pdo->query("SELECT id, company_name FROM companies LIMIT 1");
    $company = $stmt->fetch();
    
    if ($company) {
        $company_id = $company['id'];
        $company_name = $company['company_name'];
        
        echo "<p>Setting up sample data for: <strong>" . htmlspecialchars($company_name) . "</strong></p>\n";
        
        // Add sample keywords
        $sample_keywords = [
            ['ac not working', 'include', 90],
            ['furnace repair', 'include', 85],
            ['heat pump problems', 'include', 80],
            ['hvac maintenance', 'include', 75],
            ['air conditioning service', 'include', 70],
            ['heating system repair', 'include', 65],
        ];
        
        foreach ($sample_keywords as $keyword_data) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO auto_posting_keywords (company_id, keyword_type, keyword_pattern, priority)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$company_id, $keyword_data[1], $keyword_data[0], $keyword_data[2]]);
            } catch (PDOException $e) {
                // Skip duplicates
            }
        }
        echo "✅ Added sample keywords<br>\n";
        
        // Import ZIP codes from existing blog posts
        $stmt = $pdo->prepare("
            SELECT DISTINCT zip_code, COUNT(*) as post_count
            FROM blog_posts 
            WHERE company_id = ? AND zip_code IS NOT NULL AND zip_code != ''
            GROUP BY zip_code
            ORDER BY post_count DESC
            LIMIT 10
        ");
        $stmt->execute([$company_id]);
        $existing_zips = $stmt->fetchAll();
        
        $imported_zips = 0;
        foreach ($existing_zips as $zip_data) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO auto_posting_zip_targets (company_id, zip_code, priority, posts_generated)
                    VALUES (?, ?, ?, ?)
                ");
                $priority = min(100, $zip_data['post_count'] * 10); // Higher priority for ZIPs with more posts
                $stmt->execute([$company_id, $zip_data['zip_code'], $priority, $zip_data['post_count']]);
                $imported_zips++;
            } catch (PDOException $e) {
                // Skip duplicates
            }
        }
        echo "✅ Imported $imported_zips ZIP targets from existing posts<br>\n";
    }

    // Final summary
    echo "<br><h2>✅ Setup Complete!</h2>\n";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<p><strong>Summary:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Tables created: $tables_created</li>\n";
    echo "<li>📝 Tables updated: $tables_updated</li>\n";
    echo "<li>❌ Errors: $errors</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    if ($errors == 0) {
        echo "<h3>🚀 You can now use:</h3>\n";
        echo "<ul>\n";
        echo "<li><a href='automation.php'><strong>Automated Posting Configuration</strong></a> - Set up keyword targeting and scheduling</li>\n";
        echo "<li><a href='admin.php'><strong>Content Manager with Version History</strong></a> - Manage posts with full revision tracking</li>\n";
        echo "<li><a href='live_editor.php?id=1'><strong>Live HTML Editor</strong></a> - Edit posts with real-time preview</li>\n";
        echo "<li><a href='dashboard.php'><strong>Main Dashboard</strong></a> - Access all features</li>\n";
        echo "</ul>\n";
    }

} catch (Exception $e) {
    echo "❌ <strong>Fatal Error:</strong> " . $e->getMessage() . "<br>\n";
    $errors++;
}

echo "<br><p><a href='dashboard.php'>← Back to Dashboard</a></p>\n";
?>