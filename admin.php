<?php
require_once 'config.php';

$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;

// Get all companies for dropdown
try {
    $companies_stmt = $pdo->query("SELECT id, company_name, company_type FROM companies ORDER BY company_name");
    $companies = $companies_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get company details and posts if company selected
$company = null;
$posts = [];
if ($company_id > 0) {
    try {
        $company_stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $company_stmt->execute([$company_id]);
        $company = $company_stmt->fetch();
        
        if ($company) {
            $posts_stmt = $pdo->prepare("
                SELECT id, zip_code, keyword, title, word_count, generated_at,
                       LEFT(content, 200) as content_preview
                FROM blog_posts 
                WHERE company_id = ? 
                ORDER BY generated_at DESC
            ");
            $posts_stmt->execute([$company_id]);
            $posts = $posts_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HVAC Tool - Company Content Manager</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: auto; }
        select, input { padding: 8px; margin: 5px; }
        .company-card { 
            background: #f9f9f9; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 20px 0; 
        }
        .post-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            background: #fff;
        }
        .post-meta {
            color: #666;
            font-size: 0.9em;
            margin-top: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary { background: #0073e6; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .stats {
            display: flex;
            gap: 20px;
            margin: 15px 0;
        }
        .stat-box {
            background: #e8f4fd;
            border: 1px solid #0073e6;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #0073e6;
        }
        .revision-section {
            margin-top: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .revision-header {
            background: #e9ecef;
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            cursor: pointer;
        }
        .revision-header:hover {
            background: #dee2e6;
        }
        .revision-content {
            padding: 15px;
            display: none;
        }
        .revision-item {
            padding: 8px 12px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            border-radius: 3px;
            background: white;
        }
        .revision-current {
            background: #d4edda;
            border-color: #28a745;
        }
        .revision-baseline {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .revision-meta {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .revision-buttons {
            margin-top: 8px;
        }
        .btn-small {
            padding: 4px 8px;
            font-size: 0.8em;
            margin-right: 5px;
        }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-info { background: #17a2b8; color: white; }
        .diff-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .diff-added { background: #d4edda; color: #155724; }
        .diff-removed { background: #f8d7da; color: #721c24; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 800px;
            max-height: 80%;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover { color: black; }
    </style>
</head>
<body>

<h1>🏢 Company Content Manager</h1>

<form method="get">
    <label>Select Company:</label>
    <select name="company_id" onchange="this.form.submit()">
        <option value="">Choose a company...</option>
        <?php foreach ($companies as $comp): ?>
            <option value="<?= $comp['id'] ?>" <?= $company_id == $comp['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($comp['company_name']) ?> (<?= $comp['company_type'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</form>

<div>
    <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    <a href="automation.php?company_id=<?= $company_id ?>" class="btn btn-info">🤖 Automation Settings</a>
</div>

<?php if ($company): ?>
    <div class="company-card">
        <h2><?= htmlspecialchars($company['company_name']) ?></h2>
        <p><strong>Type:</strong> <?= htmlspecialchars($company['company_type']) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($company['location']) ?></p>
        <p><strong>Hours:</strong><br><?= nl2br(htmlspecialchars($company['hours'])) ?></p>
        <p><strong>Created:</strong> <?= date('M j, Y', strtotime($company['created_at'])) ?></p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-number"><?= count($posts) ?></div>
            <div>Blog Posts</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= array_sum(array_column($posts, 'word_count')) ?></div>
            <div>Total Words</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= count(array_unique(array_column($posts, 'zip_code'))) ?></div>
            <div>ZIP Codes</div>
        </div>
    </div>

    <h3>Generated Content (<?= count($posts) ?> posts)</h3>

    <?php if (empty($posts)): ?>
        <p>No content generated yet for this company.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-item">
                <h4><?= htmlspecialchars($post['title']) ?></h4>
                <p><strong>Keyword:</strong> <?= htmlspecialchars($post['keyword']) ?></p>
                <p><strong>ZIP:</strong> <?= htmlspecialchars($post['zip_code']) ?></p>
                <p><?= htmlspecialchars($post['content_preview']) ?>...</p>
                
                <div class="post-meta">
                    <strong>Word Count:</strong> <?= $post['word_count'] ?> | 
                    <strong>Generated:</strong> <?= date('M j, Y g:i A', strtotime($post['generated_at'])) ?>
                </div>
                
                <a href="api/generate_post.php?company_id=<?= $company_id ?>&zip=<?= urlencode($post['zip_code']) ?>&keyword=<?= urlencode($post['keyword']) ?>" 
                   target="_blank" class="btn btn-primary">View Full Post</a>
                
                <a href="live_editor.php?id=<?= $post['id'] ?>" 
                   target="_blank" class="btn btn-success">🎨 Live Editor</a>
                
                <a href="api/generate_post.php?company_id=<?= $company_id ?>&zip=<?= urlencode($post['zip_code']) ?>&keyword=<?= urlencode($post['keyword']) ?>&regenerate=1" 
                   target="_blank" class="btn btn-secondary">Regenerate</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php elseif ($company_id > 0): ?>
    <p>Company not found.</p>
<?php else: ?>
    <div class="company-card">
        <h3>Welcome to the Content Manager</h3>
        <p>Select a company from the dropdown above to view and manage their generated blog posts.</p>
        <p>You can:</p>
        <ul>
            <li>View all generated content for each company</li>
            <li>See content statistics and analytics</li>
            <li>Regenerate existing posts</li>
            <li>Track keyword performance</li>
        </ul>
    </div>
<?php endif; ?>

<!-- Version History Modal -->
<div id="diffModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDiffModal()">&times;</span>
        <h3>Version Comparison</h3>
        <div id="diffContent"></div>
    </div>
</div>

<!-- Content Editor Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Blog Post</h3>
        <div id="editContent">
            <textarea id="editTextarea" style="width: 100%; height: 400px;"></textarea>
            <br><br>
            <button onclick="saveEdit()" class="btn btn-primary">Save Changes</button>
            <button onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<script>
let currentEditingPostId = null;
let loadedRevisions = {};

// Version History Functions
function toggleRevisions(postId) {
    const section = document.getElementById(`revisions-${postId}`);
    const content = document.getElementById(`revision-content-${postId}`);
    
    if (section.style.display === 'none' || section.style.display === '') {
        section.style.display = 'block';
        content.style.display = 'block';
        
        // Load revisions if not already loaded
        if (!loadedRevisions[postId]) {
            loadRevisions(postId);
        }
    } else {
        section.style.display = 'none';
    }
}

function loadRevisions(postId) {
    fetch(`/api/revisions.php?post_id=${postId}&action=history`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            displayRevisions(postId, data.revisions);
            loadedRevisions[postId] = true;
        })
        .catch(error => {
            console.error('Error loading revisions:', error);
            document.getElementById(`revision-content-${postId}`).innerHTML = 
                '<p style="color: red;">Error loading version history: ' + error.message + '</p>';
        });
}

function displayRevisions(postId, revisions) {
    const container = document.getElementById(`revision-content-${postId}`);
    
    if (revisions.length === 0) {
        container.innerHTML = '<p>No version history available.</p>';
        return;
    }
    
    let html = '';
    revisions.forEach(revision => {
        let classes = 'revision-item';
        if (revision.is_current) classes += ' revision-current';
        if (revision.is_baseline) classes += ' revision-baseline';
        
        html += `
            <div class="${classes}">
                <div class="revision-meta">
                    <strong>Version ${revision.version_number}</strong> 
                    - ${formatDate(revision.created_at)}
                    ${revision.created_by !== 'system' ? ' by ' + revision.created_by : ''}
                    ${revision.is_current ? ' <strong>(Current)</strong>' : ''}
                    ${revision.is_baseline ? ' <strong>(Baseline)</strong>' : ''}
                </div>
                <div>
                    <strong>Size:</strong> ${formatSize(revision.content_size)} 
                    ${revision.change_summary ? '&bull; <strong>Changes:</strong> ' + revision.change_summary : ''}
                </div>
                <div class="revision-buttons">
                    <button onclick="viewVersion(${postId}, ${revision.version_number})" class="btn btn-info btn-small">View</button>
                    ${!revision.is_current ? `<button onclick="revertToVersion(${postId}, ${revision.version_number})" class="btn btn-warning btn-small">Revert</button>` : ''}
                    ${revision.version_number > 1 ? `<button onclick="showDiff(${postId}, ${revision.version_number - 1}, ${revision.version_number})" class="btn btn-secondary btn-small">Show Changes</button>` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function viewVersion(postId, version) {
    fetch(`/api/revisions.php?post_id=${postId}&version=${version}&action=content`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            openPost(postId, data.content);
        })
        .catch(error => {
            alert('Error viewing version: ' + error.message);
        });
}

function revertToVersion(postId, version) {
    if (!confirm(`Are you sure you want to revert to version ${version}? This will create a new version with the old content.`)) {
        return;
    }
    
    fetch('/api/revisions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            post_id: postId,
            action: 'revert',
            target_version: version
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        alert('Successfully reverted to version ' + version);
        // Reload revisions
        loadedRevisions[postId] = false;
        loadRevisions(postId);
        // Refresh page to show updated content
        location.reload();
    })
    .catch(error => {
        alert('Error reverting: ' + error.message);
    });
}

function showDiff(postId, fromVersion, toVersion) {
    fetch(`/api/revisions.php?post_id=${postId}&action=diff&from=${fromVersion}&to=${toVersion}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            displayDiff(data.diff, fromVersion, toVersion);
        })
        .catch(error => {
            alert('Error loading diff: ' + error.message);
        });
}

function displayDiff(diff, fromVersion, toVersion) {
    const modal = document.getElementById('diffModal');
    const content = document.getElementById('diffContent');
    
    content.innerHTML = `
        <p><strong>Comparing Version ${fromVersion} → Version ${toVersion}</strong></p>
        <div class="diff-container">${formatDiff(diff)}</div>
    `;
    
    modal.style.display = 'block';
}

function formatDiff(diff) {
    if (typeof diff === 'string') {
        return escapeHtml(diff);
    }
    
    // If we get a structured diff object, format it nicely
    if (diff && diff.operations) {
        let formatted = '';
        diff.operations.forEach(op => {
            if (op.type === 'insert') {
                formatted += `<span class="diff-added">+ ${escapeHtml(op.content)}</span>\n`;
            } else if (op.type === 'delete') {
                formatted += `<span class="diff-removed">- ${escapeHtml(op.content)}</span>\n`;
            } else {
                formatted += escapeHtml(op.content) + '\n';
            }
        });
        return formatted;
    }
    
    return escapeHtml(JSON.stringify(diff, null, 2));
}

function closeDiffModal() {
    document.getElementById('diffModal').style.display = 'none';
}

// Enhanced editing functions
function editPost(postId) {
    currentEditingPostId = postId;
    
    // Get current content
    fetch(`/api/revisions.php?post_id=${postId}&action=content`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            document.getElementById('editTextarea').value = data.content;
            document.getElementById('editModal').style.display = 'block';
        })
        .catch(error => {
            alert('Error loading content: ' + error.message);
        });
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    currentEditingPostId = null;
}

function saveEdit() {
    if (!currentEditingPostId) return;
    
    const newContent = document.getElementById('editTextarea').value;
    
    fetch('/api/revisions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            post_id: currentEditingPostId,
            action: 'create',
            content: newContent,
            comment: 'Manual edit via admin panel'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        alert('Content updated successfully!');
        closeEditModal();
        // Reload the page to show updated content
        location.reload();
    })
    .catch(error => {
        alert('Error saving: ' + error.message);
    });
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' bytes';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return Math.round(bytes / (1024 * 1024)) + ' MB';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Enhanced openPost function
function openPost(postId, content = null) {
    if (content) {
        // Open content in new window
        const newWindow = window.open('', '_blank');
        newWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head><title>Blog Post Version</title></head>
            <body style="font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: auto;">
                ${content}
            </body>
            </html>
        `);
        newWindow.document.close();
    } else {
        // Original functionality - open current version
        window.open(`view_post.php?id=${postId}`, '_blank');
    }
}

// Open live editor
function openLiveEditor(postId) {
    window.open(`live_editor.php?id=${postId}`, '_blank', 'width=1400,height=900');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const diffModal = document.getElementById('diffModal');
    const editModal = document.getElementById('editModal');
    
    if (event.target === diffModal) {
        closeDiffModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
}
</script>

</body>
</html>