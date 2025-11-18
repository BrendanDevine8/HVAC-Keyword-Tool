<?php
require_once 'config.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    die("Invalid post ID");
}

try {
    $stmt = $pdo->prepare("
        SELECT bp.*, c.company_name, c.company_type 
        FROM blog_posts bp 
        LEFT JOIN companies c ON bp.company_id = c.id 
        WHERE bp.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        die("Post not found");
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($post['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .post-meta {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #0073e6;
        }
        .post-content {
            margin-top: 20px;
        }
        .back-link {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .version-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

<div class="post-meta">
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <p><strong>Company:</strong> <?= htmlspecialchars($post['company_name']) ?> (<?= htmlspecialchars($post['company_type']) ?>)</p>
    <p><strong>Target Location:</strong> ZIP <?= htmlspecialchars($post['zip_code']) ?></p>
    <p><strong>Keyword:</strong> <?= htmlspecialchars($post['keyword']) ?></p>
    <p><strong>Word Count:</strong> <?= number_format($post['word_count']) ?> words</p>
    <p><strong>Generated:</strong> <?= date('F j, Y g:i A', strtotime($post['generated_at'])) ?></p>
    
    <?php if (isset($post['current_version']) && $post['current_version'] > 1): ?>
    <div class="version-info">
        <strong>Version:</strong> <?= $post['current_version'] ?> 
        | <strong>Last Modified:</strong> <?= date('M j, Y g:i A', strtotime($post['last_modified_at'])) ?>
        <?php if ($post['last_modified_by'] !== 'system'): ?>
            by <?= htmlspecialchars($post['last_modified_by']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="post-content">
    <?= $post['content'] ?>
</div>

<div class="back-link">
    <a href="admin.php?company_id=<?= $post['company_id'] ?>">&larr; Back to Company Content</a> |
    <a href="dashboard.php">Dashboard</a>
</div>

</body>
</html>