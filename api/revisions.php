<?php
/**
 * Blog Post Revisions API
 * Handles version history, creation, retrieval, and management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/delta_engine.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$post_id = $_GET['post_id'] ?? null;
$version = $_GET['version'] ?? null;
$action = $_GET['action'] ?? null;

// Set current user (for now, use a simple system)
$current_user = $_SESSION['user_id'] ?? 'anonymous';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $post_id, $version, $action);
            break;
            
        case 'POST':
            handlePostRequest($pdo, $post_id, $current_user);
            break;
            
        case 'DELETE':
            handleDeleteRequest($pdo, $_GET['revision_id'] ?? null, $current_user);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($pdo, $post_id, $version, $action) {
    if (!$post_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID required']);
        return;
    }
    
    switch ($action) {
        case 'history':
            getRevisionHistory($pdo, $post_id);
            break;
            
        case 'content':
            if (!$version) {
                http_response_code(400);
                echo json_encode(['error' => 'Version number required']);
                return;
            }
            getContentAtVersion($pdo, $post_id, $version);
            break;
            
        case 'diff':
            $from_version = $_GET['from'] ?? null;
            $to_version = $_GET['to'] ?? null;
            if (!$from_version || !$to_version) {
                http_response_code(400);
                echo json_encode(['error' => 'Both from and to version numbers required']);
                return;
            }
            getVersionDiff($pdo, $post_id, $from_version, $to_version);
            break;
            
        case 'stats':
            getRevisionStats($pdo, $post_id);
            break;
            
        default:
            // Default: return revision summary
            getRevisionSummary($pdo, $post_id);
    }
}

/**
 * Handle POST requests (create new revision or revert)
 */
function handlePostRequest($pdo, $post_id, $current_user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$post_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID required']);
        return;
    }
    
    $action = $input['action'] ?? 'create';
    
    switch ($action) {
        case 'create':
            createNewRevision($pdo, $post_id, $input, $current_user);
            break;
            
        case 'revert':
            $target_version = $input['target_version'] ?? null;
            if (!$target_version) {
                http_response_code(400);
                echo json_encode(['error' => 'Target version required']);
                return;
            }
            revertToVersion($pdo, $post_id, $target_version, $current_user);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($pdo, $revision_id, $current_user) {
    if (!$revision_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Revision ID required']);
        return;
    }
    
    // Only allow deletion of non-baseline revisions that aren't the latest
    $stmt = $pdo->prepare("
        SELECT r.*, 
               (SELECT MAX(version_number) FROM blog_post_revisions WHERE post_id = r.post_id) as latest_version
        FROM blog_post_revisions r 
        WHERE r.id = ?
    ");
    $stmt->execute([$revision_id]);
    $revision = $stmt->fetch();
    
    if (!$revision) {
        http_response_code(404);
        echo json_encode(['error' => 'Revision not found']);
        return;
    }
    
    if ($revision['is_baseline']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete baseline revisions']);
        return;
    }
    
    if ($revision['version_number'] == $revision['latest_version']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete the latest revision']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM blog_post_revisions WHERE id = ?");
    $stmt->execute([$revision_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Revision deleted successfully',
        'deleted_version' => $revision['version_number']
    ]);
}

/**
 * Get revision history for a post
 */
function getRevisionHistory($pdo, $post_id) {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               bp.title, bp.current_version,
               r.version_number = bp.current_version as is_current
        FROM blog_post_revisions r
        JOIN blog_posts bp ON r.post_id = bp.id
        WHERE r.post_id = ?
        ORDER BY r.version_number DESC
    ");
    $stmt->execute([$post_id]);
    $revisions = $stmt->fetchAll();
    
    // Add change summaries for delta revisions
    foreach ($revisions as &$revision) {
        if (!$revision['is_baseline'] && $revision['delta_changes']) {
            $delta = json_decode($revision['delta_changes'], true);
            $revision['auto_summary'] = DeltaEngine::getChangeSummary($delta);
        }
        
        // Format dates
        $revision['created_at_formatted'] = date('M j, Y g:i A', strtotime($revision['created_at']));
        
        // Add revision size info
        if ($revision['is_baseline'] && $revision['baseline_content']) {
            $revision['content_size'] = strlen($revision['baseline_content']);
        } elseif ($revision['delta_changes']) {
            $revision['delta_size'] = strlen($revision['delta_changes']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'post_id' => (int)$post_id,
        'total_revisions' => count($revisions),
        'revisions' => $revisions
    ]);
}

/**
 * Get content at a specific version
 */
function getContentAtVersion($pdo, $post_id, $version) {
    $content = reconstructContentAtVersion($pdo, $post_id, $version);
    
    if ($content === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Could not reconstruct content at specified version']);
        return;
    }
    
    // Get revision info
    $stmt = $pdo->prepare("
        SELECT * FROM blog_post_revisions 
        WHERE post_id = ? AND version_number = ?
    ");
    $stmt->execute([$post_id, $version]);
    $revision = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'post_id' => (int)$post_id,
        'version' => (float)$version,
        'content' => $content,
        'word_count' => str_word_count(strip_tags($content)),
        'revision_info' => $revision
    ]);
}

/**
 * Get diff between two versions
 */
function getVersionDiff($pdo, $post_id, $from_version, $to_version) {
    $fromContent = reconstructContentAtVersion($pdo, $post_id, $from_version);
    $toContent = reconstructContentAtVersion($pdo, $post_id, $to_version);
    
    if ($fromContent === null || $toContent === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Could not reconstruct content for one or both versions']);
        return;
    }
    
    // Generate diff
    $delta = DeltaEngine::generateDelta($fromContent, $toContent);
    
    echo json_encode([
        'success' => true,
        'post_id' => (int)$post_id,
        'from_version' => (float)$from_version,
        'to_version' => (float)$to_version,
        'delta' => $delta,
        'summary' => DeltaEngine::getChangeSummary($delta),
        'from_word_count' => str_word_count(strip_tags($fromContent)),
        'to_word_count' => str_word_count(strip_tags($toContent))
    ]);
}

/**
 * Get revision statistics
 */
function getRevisionStats($pdo, $post_id) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_revisions,
            COUNT(CASE WHEN is_baseline = 1 THEN 1 END) as baseline_count,
            MIN(created_at) as first_revision,
            MAX(created_at) as last_revision,
            COUNT(DISTINCT created_by) as unique_contributors,
            SUM(CASE WHEN is_baseline = 1 AND baseline_content IS NOT NULL THEN LENGTH(baseline_content) ELSE 0 END) as baseline_storage,
            SUM(CASE WHEN is_baseline = 0 AND delta_changes IS NOT NULL THEN LENGTH(delta_changes) ELSE 0 END) as delta_storage
        FROM blog_post_revisions 
        WHERE post_id = ?
    ");
    $stmt->execute([$post_id]);
    $stats = $stmt->fetch();
    
    // Get contributor details
    $stmt = $pdo->prepare("
        SELECT created_by, COUNT(*) as revision_count, MAX(created_at) as last_contribution
        FROM blog_post_revisions 
        WHERE post_id = ?
        GROUP BY created_by
        ORDER BY revision_count DESC
    ");
    $stmt->execute([$post_id]);
    $contributors = $stmt->fetchAll();
    
    // Calculate storage efficiency
    $total_storage = $stats['baseline_storage'] + $stats['delta_storage'];
    $estimated_full_storage = $stats['total_revisions'] * $stats['baseline_storage'] / max($stats['baseline_count'], 1);
    $compression_ratio = $estimated_full_storage > 0 ? round((1 - $total_storage / $estimated_full_storage) * 100, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'post_id' => (int)$post_id,
        'statistics' => [
            'total_revisions' => (int)$stats['total_revisions'],
            'baseline_count' => (int)$stats['baseline_count'],
            'delta_count' => (int)$stats['total_revisions'] - (int)$stats['baseline_count'],
            'first_revision' => $stats['first_revision'],
            'last_revision' => $stats['last_revision'],
            'unique_contributors' => (int)$stats['unique_contributors'],
            'storage' => [
                'baseline_bytes' => (int)$stats['baseline_storage'],
                'delta_bytes' => (int)$stats['delta_storage'],
                'total_bytes' => $total_storage,
                'estimated_full_storage_bytes' => (int)$estimated_full_storage,
                'compression_ratio_percent' => $compression_ratio
            ]
        ],
        'contributors' => $contributors
    ]);
}

/**
 * Get revision summary (default GET response)
 */
function getRevisionSummary($pdo, $post_id) {
    $stmt = $pdo->prepare("
        SELECT 
            bp.title, bp.current_version, bp.last_modified_by, bp.last_modified_at,
            COUNT(r.id) as revision_count,
            COUNT(CASE WHEN r.is_baseline = 1 THEN 1 END) as baseline_count,
            MAX(r.created_at) as last_revision_date
        FROM blog_posts bp
        LEFT JOIN blog_post_revisions r ON bp.id = r.post_id
        WHERE bp.id = ?
        GROUP BY bp.id
    ");
    $stmt->execute([$post_id]);
    $summary = $stmt->fetch();
    
    if (!$summary) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        return;
    }
    
    // Get recent revisions
    $stmt = $pdo->prepare("
        SELECT version_number, created_by, created_at, change_summary, is_baseline
        FROM blog_post_revisions
        WHERE post_id = ?
        ORDER BY version_number DESC
        LIMIT 5
    ");
    $stmt->execute([$post_id]);
    $recent_revisions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'post_id' => (int)$post_id,
        'title' => $summary['title'],
        'current_version' => (int)$summary['current_version'],
        'last_modified_by' => $summary['last_modified_by'],
        'last_modified_at' => $summary['last_modified_at'],
        'revision_count' => (int)$summary['revision_count'],
        'baseline_count' => (int)$summary['baseline_count'],
        'recent_revisions' => $recent_revisions
    ]);
}

/**
 * Create a new revision
 */
function createNewRevision($pdo, $post_id, $input, $current_user) {
    $new_title = trim($input['title'] ?? '');
    $new_content = trim($input['content'] ?? '');
    $change_summary = trim($input['change_summary'] ?? '');
    
    if (empty($new_content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Content is required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get current post data
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            throw new Exception('Post not found');
        }
        
        $old_content = $post['content'];
        $old_word_count = $post['word_count'];
        $new_word_count = str_word_count(strip_tags($new_content));
        $new_version = $post['current_version'] + 1;
        
        // Auto-generate change summary if not provided
        if (empty($change_summary)) {
            $delta = DeltaEngine::generateDelta($old_content, $new_content);
            $change_summary = DeltaEngine::getChangeSummary($delta);
        }
        
        // Update the main blog post
        $update_fields = ['content = ?', 'word_count = ?', 'current_version = ?', 'last_modified_by = ?'];
        $update_values = [$new_content, $new_word_count, $new_version, $current_user];
        
        if (!empty($new_title) && $new_title !== $post['title']) {
            $update_fields[] = 'title = ?';
            $update_values[] = $new_title;
        }
        
        $stmt = $pdo->prepare("UPDATE blog_posts SET " . implode(', ', $update_fields) . " WHERE id = ?");
        $update_values[] = $post_id;
        $stmt->execute($update_values);
        
        // Create the revision
        $delta = DeltaEngine::generateDelta($old_content, $new_content);
        $word_count_delta = $new_word_count - $old_word_count;
        
        // Determine if this should be a baseline (every 10 revisions)
        $is_baseline = ($new_version % 10 === 0);
        
        if ($is_baseline) {
            $stmt = $pdo->prepare("
                INSERT INTO blog_post_revisions 
                (post_id, version_number, change_summary, created_by, is_baseline, baseline_content, word_count_delta)
                VALUES (?, ?, ?, ?, 1, ?, ?)
            ");
            $stmt->execute([$post_id, $new_version, $change_summary, $current_user, $new_content, $word_count_delta]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO blog_post_revisions 
                (post_id, version_number, delta_changes, change_summary, word_count_delta, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$post_id, $new_version, json_encode($delta), $change_summary, $word_count_delta, $current_user]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Revision created successfully',
            'new_version' => $new_version,
            'is_baseline' => $is_baseline,
            'change_summary' => $change_summary,
            'word_count_delta' => $word_count_delta
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

/**
 * Revert to a previous version
 */
function revertToVersion($pdo, $post_id, $target_version, $current_user) {
    try {
        $pdo->beginTransaction();
        
        // Get content at target version
        $target_content = reconstructContentAtVersion($pdo, $post_id, $target_version);
        
        if ($target_content === null) {
            throw new Exception('Could not reconstruct content at target version');
        }
        
        // Get current version info
        $stmt = $pdo->prepare("SELECT current_version FROM blog_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $current_version = $stmt->fetchColumn();
        
        if (!$current_version) {
            throw new Exception('Post not found');
        }
        
        if ($target_version == $current_version) {
            throw new Exception('Already at target version');
        }
        
        // Create new revision with reverted content
        $new_version = $current_version + 1;
        $new_word_count = str_word_count(strip_tags($target_content));
        $change_summary = "Reverted to version {$target_version}";
        
        // Update main post
        $stmt = $pdo->prepare("
            UPDATE blog_posts 
            SET content = ?, word_count = ?, current_version = ?, last_modified_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$target_content, $new_word_count, $new_version, $current_user, $post_id]);
        
        // Create revert revision
        $stmt = $pdo->prepare("
            INSERT INTO blog_post_revisions 
            (post_id, version_number, change_summary, created_by, is_baseline, baseline_content)
            VALUES (?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([$post_id, $new_version, $change_summary, $current_user, $target_content]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully reverted to version ' . $target_version,
            'new_version' => $new_version,
            'reverted_to_version' => $target_version
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

/**
 * Reconstruct content at a specific version
 */
function reconstructContentAtVersion($pdo, $post_id, $version_number) {
    try {
        // Find the most recent baseline at or before this version
        $stmt = $pdo->prepare("
            SELECT version_number, baseline_content 
            FROM blog_post_revisions 
            WHERE post_id = ? AND version_number <= ? AND is_baseline = 1
            ORDER BY version_number DESC 
            LIMIT 1
        ");
        $stmt->execute([$post_id, $version_number]);
        $baseline = $stmt->fetch();
        
        if (!$baseline) {
            // No baseline found, get from main table if version 1
            if ($version_number == 1) {
                $stmt = $pdo->prepare("SELECT content FROM blog_posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $result = $stmt->fetch();
                return $result ? $result['content'] : null;
            }
            return null;
        }
        
        $content = $baseline['baseline_content'];
        $fromVersion = $baseline['version_number'];
        
        // Apply all deltas from baseline to target version
        if ($fromVersion < $version_number) {
            $stmt = $pdo->prepare("
                SELECT delta_changes 
                FROM blog_post_revisions 
                WHERE post_id = ? AND version_number > ? AND version_number <= ? AND is_baseline = 0
                ORDER BY version_number ASC
            ");
            $stmt->execute([$post_id, $fromVersion, $version_number]);
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