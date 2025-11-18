<?php
/**
 * Cleanup Old Blog Post Revisions
 * Run this daily via cron: 0 2 * * * php /path/to/cleanup_revisions.php
 * Removes revisions older than 6 months while preserving baselines and recent versions
 */

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/includes/delta_engine.php";

echo "🧹 Starting revision cleanup process...\n";
echo "📅 Cutoff date: " . date('Y-m-d H:i:s', strtotime('-6 months')) . "\n\n";

try {
    $cutoffDate = date('Y-m-d H:i:s', strtotime('-6 months'));
    $totalDeleted = 0;
    $totalBaselinesKept = 0;
    $totalRecentKept = 0;
    
    // Get statistics before cleanup
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blog_post_revisions");
    $totalRevisionsBefore = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blog_post_revisions WHERE created_at < '$cutoffDate'");
    $oldRevisionsCount = $stmt->fetch()['total'];
    
    echo "📊 Current revision statistics:\n";
    echo "   Total revisions: {$totalRevisionsBefore}\n";
    echo "   Revisions older than 6 months: {$oldRevisionsCount}\n\n";
    
    // Step 1: Identify posts with old revisions
    $stmt = $pdo->prepare("
        SELECT DISTINCT post_id, 
               COUNT(*) as total_revisions,
               COUNT(CASE WHEN created_at < ? THEN 1 END) as old_revisions,
               COUNT(CASE WHEN is_baseline = 1 THEN 1 END) as baselines,
               MAX(version_number) as latest_version
        FROM blog_post_revisions 
        WHERE post_id IN (
            SELECT DISTINCT post_id 
            FROM blog_post_revisions 
            WHERE created_at < ?
        )
        GROUP BY post_id
    ");
    $stmt->execute([$cutoffDate, $cutoffDate]);
    $postsToClean = $stmt->fetchAll();
    
    echo "🔍 Found " . count($postsToClean) . " posts with old revisions\n\n";
    
    foreach ($postsToClean as $post) {
        echo "📝 Processing post ID {$post['post_id']}:\n";
        echo "   Total revisions: {$post['total_revisions']}\n";
        echo "   Old revisions: {$post['old_revisions']}\n";
        echo "   Baselines: {$post['baselines']}\n";
        echo "   Latest version: {$post['latest_version']}\n";
        
        // Delete old non-baseline revisions, but keep:
        // 1. All baselines (for performance)
        // 2. The most recent version
        // 3. Revisions from the last 6 months
        $stmt = $pdo->prepare("
            DELETE FROM blog_post_revisions 
            WHERE post_id = ? 
            AND created_at < ?
            AND is_baseline = 0
            AND version_number < ?
        ");
        $stmt->execute([$post['post_id'], $cutoffDate, $post['latest_version']]);
        $deleted = $stmt->rowCount();
        
        $totalDeleted += $deleted;
        echo "   ✅ Deleted {$deleted} old revisions\n";
        
        // Count what we kept
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN is_baseline = 1 AND created_at < ? THEN 1 END) as baselines_kept,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as recent_kept
            FROM blog_post_revisions 
            WHERE post_id = ?
        ");
        $stmt->execute([$cutoffDate, $cutoffDate, $post['post_id']]);
        $kept = $stmt->fetch();
        
        $totalBaselinesKept += $kept['baselines_kept'];
        $totalRecentKept += $kept['recent_kept'];
        
        echo "   📌 Kept {$kept['baselines_kept']} baselines, {$kept['recent_kept']} recent revisions\n\n";
    }
    
    // Step 2: Clean up orphaned revisions (posts that no longer exist)
    echo "🔍 Checking for orphaned revisions...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphaned
        FROM blog_post_revisions r
        LEFT JOIN blog_posts p ON r.post_id = p.id
        WHERE p.id IS NULL
    ");
    $orphanedCount = $stmt->fetch()['orphaned'];
    
    if ($orphanedCount > 0) {
        $stmt = $pdo->query("
            DELETE r FROM blog_post_revisions r
            LEFT JOIN blog_posts p ON r.post_id = p.id
            WHERE p.id IS NULL
        ");
        $orphanedDeleted = $stmt->rowCount();
        echo "   ✅ Deleted {$orphanedDeleted} orphaned revisions\n";
        $totalDeleted += $orphanedDeleted;
    } else {
        echo "   ✅ No orphaned revisions found\n";
    }
    
    // Step 3: Update baseline gaps (create new baselines if needed)
    echo "\n🔧 Checking for baseline gaps...\n";
    
    $stmt = $pdo->query("
        SELECT post_id, MAX(version_number) as max_version,
               MAX(CASE WHEN is_baseline = 1 THEN version_number END) as latest_baseline
        FROM blog_post_revisions
        GROUP BY post_id
        HAVING (max_version - COALESCE(latest_baseline, 0)) >= 15
    ");
    $postsNeedingBaselines = $stmt->fetchAll();
    
    $baselinesCreated = 0;
    foreach ($postsNeedingBaselines as $post) {
        $gap = $post['max_version'] - ($post['latest_baseline'] ?? 0);
        echo "   📝 Post {$post['post_id']} has {$gap} revisions since last baseline\n";
        
        // Get current content and create new baseline
        $currentContent = getCurrentPostContent($pdo, $post['post_id']);
        if ($currentContent) {
            $newBaselineVersion = $post['max_version'] + 0.1; // Use decimal to avoid conflicts
            
            $stmt = $pdo->prepare("
                INSERT INTO blog_post_revisions (post_id, version_number, change_summary, created_by, is_baseline, baseline_content)
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([
                $post['post_id'],
                $newBaselineVersion,
                "Cleanup-generated baseline (gap: {$gap} revisions)",
                'system',
                $currentContent
            ]);
            
            $baselinesCreated++;
            echo "   ✅ Created new baseline at version {$newBaselineVersion}\n";
        }
    }
    
    // Final statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blog_post_revisions");
    $totalRevisionsAfter = $stmt->fetch()['total'];
    
    $spaceFreed = $totalRevisionsBefore - $totalRevisionsAfter;
    $percentReduction = round(($spaceFreed / $totalRevisionsBefore) * 100, 1);
    
    echo "\n📊 Cleanup Summary:\n";
    echo "   🗑️  Deleted revisions: {$totalDeleted}\n";
    echo "   📌 Baselines preserved: {$totalBaselinesKept}\n";
    echo "   📅 Recent revisions kept: {$totalRecentKept}\n";
    echo "   🆕 New baselines created: {$baselinesCreated}\n";
    echo "   📉 Total reduction: {$spaceFreed} revisions ({$percentReduction}%)\n";
    echo "   📊 Revisions remaining: {$totalRevisionsAfter}\n\n";
    
    // Log the cleanup activity
    $logMessage = "Revision cleanup completed: deleted {$totalDeleted}, kept {$totalBaselinesKept} baselines + {$totalRecentKept} recent, created {$baselinesCreated} new baselines";
    
    $logFile = __DIR__ . "/logs/revision_cleanup.log";
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$logMessage}\n", FILE_APPEND);
    
    echo "✅ Cleanup completed successfully!\n";
    echo "📝 Log entry written to: {$logFile}\n";
    
} catch (Exception $e) {
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
    
    // Log the error
    $logFile = __DIR__ . "/logs/revision_cleanup.log";
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $errorMessage = "Cleanup failed: " . $e->getMessage();
    file_put_contents($logFile, "[{$timestamp}] ERROR: {$errorMessage}\n", FILE_APPEND);
    
    exit(1);
}

/**
 * Get current content of a post by reconstructing from revisions
 */
function getCurrentPostContent($pdo, $postId) {
    try {
        // Get the latest version number
        $stmt = $pdo->prepare("
            SELECT MAX(version_number) as latest_version 
            FROM blog_post_revisions 
            WHERE post_id = ?
        ");
        $stmt->execute([$postId]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['latest_version']) {
            // Fall back to blog_posts table
            $stmt = $pdo->prepare("SELECT content FROM blog_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            return $post ? $post['content'] : null;
        }
        
        // Use the getPostContentAtVersion function (we'll need to include it)
        return getPostContentAtVersion($pdo, $postId, $result['latest_version']);
        
    } catch (Exception $e) {
        echo "   ⚠️  Warning: Could not get current content for post {$postId}: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Get post content at a specific version (copied from main script)
 */
function getPostContentAtVersion($pdo, $postId, $versionNumber) {
    try {
        // Find the most recent baseline at or before this version
        $stmt = $pdo->prepare("
            SELECT version_number, baseline_content 
            FROM blog_post_revisions 
            WHERE post_id = ? AND version_number <= ? AND is_baseline = 1
            ORDER BY version_number DESC 
            LIMIT 1
        ");
        $stmt->execute([$postId, $versionNumber]);
        $baseline = $stmt->fetch();
        
        if (!$baseline) {
            // No baseline found, start from blog_posts table
            $stmt = $pdo->prepare("SELECT content FROM blog_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $result = $stmt->fetch();
            return $result ? $result['content'] : null;
        }
        
        $content = $baseline['baseline_content'];
        $fromVersion = $baseline['version_number'];
        
        // Apply all deltas from baseline to target version
        if ($fromVersion < $versionNumber) {
            $stmt = $pdo->prepare("
                SELECT delta_changes 
                FROM blog_post_revisions 
                WHERE post_id = ? AND version_number > ? AND version_number <= ? AND is_baseline = 0
                ORDER BY version_number ASC
            ");
            $stmt->execute([$postId, $fromVersion, $versionNumber]);
            $deltas = $stmt->fetchAll();
            
            foreach ($deltas as $delta) {
                $deltaData = json_decode($delta['delta_changes'], true);
                $content = DeltaEngine::applyDelta($content, $deltaData);
            }
        }
        
        return $content;
        
    } catch (Exception $e) {
        return null;
    }
}
?>