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

// Get company details and automation settings
$company = null;
$automation_settings = null;
$keywords = [];
$zip_targets = [];
$queue_items = [];

if ($company_id > 0) {
    try {
        // Get company info
        $company_stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $company_stmt->execute([$company_id]);
        $company = $company_stmt->fetch();
        
        if ($company) {
            // Get automation keywords
            $keywords_stmt = $pdo->prepare("SELECT * FROM auto_posting_keywords WHERE company_id = ? ORDER BY priority DESC, keyword_pattern");
            $keywords_stmt->execute([$company_id]);
            $keywords = $keywords_stmt->fetchAll();
            
            // Get ZIP targets
            $zips_stmt = $pdo->prepare("SELECT * FROM auto_posting_zip_targets WHERE company_id = ? ORDER BY priority DESC, zip_code");
            $zips_stmt->execute([$company_id]);
            $zip_targets = $zips_stmt->fetchAll();
            
            // Get recent queue items
            $queue_stmt = $pdo->prepare("
                SELECT apq.*, bp.title as post_title 
                FROM auto_posting_queue apq 
                LEFT JOIN blog_posts bp ON apq.blog_post_id = bp.id 
                WHERE apq.company_id = ? 
                ORDER BY apq.scheduled_for DESC 
                LIMIT 20
            ");
            $queue_stmt->execute([$company_id]);
            $queue_items = $queue_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HVAC Tool - Automated Posting</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: auto; }
        .form-section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-row { display: flex; gap: 15px; margin: 10px 0; align-items: center; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; box-sizing: border-box; }
        .btn { padding: 10px 20px; background: #0073e6; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #005bb5; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .btn-small { padding: 5px 10px; font-size: 0.9em; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #dee2e6; }
        .stat-number { font-size: 1.8em; font-weight: bold; color: #0073e6; }
        .keyword-item, .zip-item, .queue-item { 
            background: #f8f9fa; padding: 12px; margin: 8px 0; border-radius: 5px; border: 1px solid #dee2e6; 
        }
        .priority-high { border-left: 4px solid #28a745; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #6c757d; }
        .automation-status { display: inline-block; padding: 5px 10px; border-radius: 15px; font-weight: bold; }
        .automation-enabled { background: #d4edda; color: #155724; }
        .automation-disabled { background: #f8d7da; color: #721c24; }
        .schedule-preview { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🤖 Automated Blog Posting</h1>

<form method="get">
    <div class="form-row">
        <div class="form-group">
            <label>Select Company:</label>
            <select name="company_id" onchange="this.form.submit()">
                <option value="">Choose a company...</option>
                <?php foreach ($companies as $comp): ?>
                <option value="<?= $comp['id'] ?>" <?= $comp['id'] == $company_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($comp['company_name']) ?> (<?= htmlspecialchars($comp['company_type']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<div>
    <a href="dashboard.php" class="btn btn-secondary">← Dashboard</a>
    <a href="admin.php?company_id=<?= $company_id ?>" class="btn btn-secondary">Content Manager</a>
</div>

<?php if ($company): ?>

<!-- Company Overview -->
<div class="form-section">
    <h2><?= htmlspecialchars($company['company_name']) ?></h2>
    <div class="form-row">
        <div>
            <strong>Automation Status:</strong> 
            <span class="automation-status <?= $company['auto_posting_enabled'] ? 'automation-enabled' : 'automation-disabled' ?>">
                <?= $company['auto_posting_enabled'] ? 'ENABLED' : 'DISABLED' ?>
            </span>
        </div>
        <?php if ($company['auto_posting_enabled']): ?>
        <div>
            <strong>Frequency:</strong> <?= ucfirst($company['auto_posting_frequency']) ?> 
            (every <?= $company['auto_posting_interval'] ?> <?= $company['auto_posting_frequency'] === 'hourly' ? 'hour(s)' : ($company['auto_posting_frequency'] === 'daily' ? 'day(s)' : ($company['auto_posting_frequency'] === 'weekly' ? 'week(s)' : 'month(s)')) ?>)
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($company['auto_posting_enabled'] && $company['next_auto_post']): ?>
    <div class="schedule-preview">
        <strong>📅 Next Scheduled Post:</strong> <?= date('F j, Y g:i A', strtotime($company['next_auto_post'])) ?>
        <?php if ($company['last_auto_post']): ?>
        <br><strong>Last Post:</strong> <?= date('F j, Y g:i A', strtotime($company['last_auto_post'])) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= count($keywords) ?></div>
        <div>Target Keywords</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count($zip_targets) ?></div>
        <div>ZIP Targets</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count(array_filter($queue_items, fn($q) => $q['status'] === 'pending')) ?></div>
        <div>Pending Posts</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count(array_filter($queue_items, fn($q) => $q['status'] === 'completed')) ?></div>
        <div>Completed Today</div>
    </div>
</div>

<!-- Automation Settings -->
<div class="form-section">
    <h3>⚙️ Automation Settings</h3>
    <form id="automationForm">
        <input type="hidden" name="company_id" value="<?= $company_id ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label>Enable Automation:</label>
                <select name="auto_posting_enabled">
                    <option value="0" <?= !$company['auto_posting_enabled'] ? 'selected' : '' ?>>Disabled</option>
                    <option value="1" <?= $company['auto_posting_enabled'] ? 'selected' : '' ?>>Enabled</option>
                </select>
            </div>
            <div class="form-group">
                <label>Frequency:</label>
                <select name="auto_posting_frequency">
                    <option value="hourly" <?= $company['auto_posting_frequency'] === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                    <option value="daily" <?= $company['auto_posting_frequency'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $company['auto_posting_frequency'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $company['auto_posting_frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                </select>
            </div>
            <div class="form-group">
                <label>Interval:</label>
                <input type="number" name="auto_posting_interval" value="<?= $company['auto_posting_interval'] ?>" min="1" max="24">
            </div>
        </div>
        
        <button type="button" onclick="saveAutomationSettings()" class="btn">Save Settings</button>
        <?php if ($company['auto_posting_enabled']): ?>
        <button type="button" onclick="generateQueue()" class="btn btn-success">Generate Queue Now</button>
        <?php endif; ?>
    </form>
</div>

<!-- Keyword Management -->
<div class="form-section">
    <h3>🎯 Target Keywords</h3>
    <div class="form-row">
        <div class="form-group">
            <input type="text" id="newKeyword" placeholder="Enter keyword pattern">
        </div>
        <div class="form-group">
            <select id="keywordType">
                <option value="include">Include</option>
                <option value="exclude">Exclude</option>
            </select>
        </div>
        <div class="form-group">
            <input type="number" id="keywordPriority" placeholder="Priority" value="0" min="0" max="100">
        </div>
        <button onclick="addKeyword()" class="btn">Add Keyword</button>
    </div>
    
    <div id="keywordsList">
        <?php foreach ($keywords as $kw): ?>
        <div class="keyword-item priority-<?= $kw['priority'] > 50 ? 'high' : ($kw['priority'] > 25 ? 'medium' : 'low') ?>">
            <div style="display: flex; justify-content: between; align-items: center;">
                <div style="flex: 1;">
                    <strong><?= htmlspecialchars($kw['keyword_pattern']) ?></strong>
                    <span style="margin-left: 10px; color: #666;">
                        (<?= $kw['keyword_type'] ?>, Priority: <?= $kw['priority'] ?>)
                    </span>
                </div>
                <button onclick="removeKeyword(<?= $kw['id'] ?>)" class="btn btn-danger btn-small">Remove</button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($keywords)): ?>
        <p style="color: #666; font-style: italic;">No keywords configured. Add keywords above to get started.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ZIP Code Targets -->
<div class="form-section">
    <h3>📍 ZIP Code Targets</h3>
    <div class="form-row">
        <div class="form-group">
            <input type="text" id="newZipCode" placeholder="ZIP Code" maxlength="10">
        </div>
        <div class="form-group">
            <input type="number" id="zipPriority" placeholder="Priority" value="0" min="0" max="100">
        </div>
        <button onclick="addZipTarget()" class="btn">Add ZIP Target</button>
        <button onclick="importZipsFromPosts()" class="btn btn-secondary">Import from Existing Posts</button>
    </div>
    
    <div id="zipTargetsList">
        <?php foreach ($zip_targets as $zip): ?>
        <div class="zip-item priority-<?= $zip['priority'] > 50 ? 'high' : ($zip['priority'] > 25 ? 'medium' : 'low') ?>">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1;">
                    <strong><?= htmlspecialchars($zip['zip_code']) ?></strong>
                    <span style="margin-left: 10px; color: #666;">
                        (Priority: <?= $zip['priority'] ?>, Posts: <?= $zip['posts_generated'] ?>)
                        <?php if ($zip['last_posted']): ?>
                        - Last: <?= date('M j', strtotime($zip['last_posted'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <button onclick="removeZipTarget(<?= $zip['id'] ?>)" class="btn btn-danger btn-small">Remove</button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($zip_targets)): ?>
        <p style="color: #666; font-style: italic;">No ZIP targets configured. Add ZIP codes above or import from existing posts.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Queue Activity -->
<div class="form-section">
    <h3>📋 Recent Queue Activity</h3>
    <div style="margin-bottom: 10px;">
        <button onclick="loadQueue()" class="btn btn-secondary btn-small">Refresh</button>
        <button onclick="clearCompletedQueue()" class="btn btn-danger btn-small">Clear Completed</button>
    </div>
    
    <div id="queueList">
        <?php foreach ($queue_items as $item): ?>
        <div class="queue-item">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1;">
                    <strong><?= htmlspecialchars($item['keyword']) ?></strong> in <?= htmlspecialchars($item['zip_code']) ?>
                    <br><small>Scheduled: <?= date('M j, Y g:i A', strtotime($item['scheduled_for'])) ?></small>
                    <?php if ($item['post_title']): ?>
                    <br><small><em><?= htmlspecialchars($item['post_title']) ?></em></small>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="status-badge status-<?= $item['status'] ?>">
                        <?= strtoupper($item['status']) ?>
                    </span>
                </div>
            </div>
            <?php if ($item['error_message']): ?>
            <div style="color: #dc3545; font-size: 0.9em; margin-top: 5px;">
                Error: <?= htmlspecialchars($item['error_message']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($queue_items)): ?>
        <p style="color: #666; font-style: italic;">No recent queue activity.</p>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="form-section">
    <p>Select a company above to configure automated posting.</p>
</div>
<?php endif; ?>

<script>
const companyId = <?= $company_id ?>;

function saveAutomationSettings() {
    const form = new FormData(document.getElementById('automationForm'));
    
    fetch('api/automation.php', {
        method: 'POST',
        body: form
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Settings saved successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        alert('Error saving settings: ' + error.message);
    });
}

function addKeyword() {
    const keyword = document.getElementById('newKeyword').value.trim();
    const type = document.getElementById('keywordType').value;
    const priority = parseInt(document.getElementById('keywordPriority').value) || 0;
    
    if (!keyword) {
        alert('Please enter a keyword');
        return;
    }
    
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'add_keyword',
            company_id: companyId,
            keyword_pattern: keyword,
            keyword_type: type,
            priority: priority
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Add keyword error:', error);
        alert('Error adding keyword: ' + error.message);
    });
}

function removeKeyword(id) {
    if (!confirm('Remove this keyword?')) return;
    
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'remove_keyword',
            keyword_id: id
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error removing keyword: ' + error.message);
    });
}

function addZipTarget() {
    const zipCode = document.getElementById('newZipCode').value.trim();
    const priority = parseInt(document.getElementById('zipPriority').value) || 0;
    
    if (!zipCode) {
        alert('Please enter a ZIP code');
        return;
    }
    
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'add_zip_target',
            company_id: companyId,
            zip_code: zipCode,
            priority: priority
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error adding ZIP target: ' + error.message);
    });
}

function removeZipTarget(id) {
    if (!confirm('Remove this ZIP target?')) return;
    
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'remove_zip_target',
            zip_id: id
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error removing ZIP target: ' + error.message);
    });
}

function importZipsFromPosts() {
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'import_zips_from_posts',
            company_id: companyId
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`Imported ${data.imported_count} ZIP codes from existing posts`);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error importing ZIP codes: ' + error.message);
    });
}

function generateQueue() {
    if (!confirm('Generate new queue items for automated posting?')) return;
    
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'generate_queue',
            company_id: companyId
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`Generated ${data.queue_items} queue items`);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error generating queue: ' + error.message);
    });
}

function loadQueue() {
    location.reload();
}

function clearCompletedQueue() {
    if (!confirm('Clear all completed queue items?')) return;
    
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'clear_completed_queue',
            company_id: companyId
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Cleared completed queue items');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error clearing queue: ' + error.message);
    });
}
</script>

</body>
</html>