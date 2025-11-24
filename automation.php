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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HVAC Tool - Automated Posting</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --rbmg-midnight: #000b30;
            --rbmg-dark-midnight: #00061e;
            --rbmg-purple: #2d102f;
            --rbmg-purple-tint: #57165b;
            --rbmg-danger: #ce4033;
            --rbmg-light: #f1f2f2;
            --rbmg-gradient: linear-gradient(60deg, #000b30 10%, #2d102f 25%, #6f2c23 50%, #ce4033 85%);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #fff;
            color: #000b30;
        }

        /* RBMG Button Styles */
        .btn {
            padding: 0.75rem 1.25rem !important;
            font-weight: 700;
            transition-duration: 0.25s;
            border-radius: 0;
        }
        
        .btn-rbmg-primary {
            color: #fff;
            background: #ce4033;
            border-color: #ce4033;
        }
        
        .btn-rbmg-primary:hover {
            color: #fff;
            background: #f64f3f;
            border-color: #ce4033;
        }

        .btn-rbmg-secondary {
            color: #fff;
            background: #00061e;
            border-color: #00061e;
        }
        
        .btn-rbmg-secondary:hover {
            color: #fff;
            background: #57165b;
            border-color: #00061e;
        }

        .btn-outline-rbmg {
            color: #00061e;
            background: transparent;
            border-color: #00061e;
        }
        
        .btn-outline-rbmg:hover {
            color: #fff;
            background: #00061e;
            border-color: #00061e;
        }

        /* RBMG Header Gradient */
        .rbmg-header {
            background: rgb(0,11,48);
            background: linear-gradient(60deg, rgba(0,11,48,1) 10%, rgba(45,16,47,1) 25%, rgba(111,44,35,1) 50%, rgba(206,64,51,1) 85%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .rbmg-header h1 {
            font-weight: 700;
            margin: 0;
            font-size: 2.5rem;
        }

        /* RBMG Card Styles */
        .rbmg-card {
            background: white;
            border: 2px solid #ce4033;
            border-radius: 0;
            box-shadow: 0 4px 6px rgba(0, 11, 48, 0.1);
            transition: transform 0.25s;
        }

        .rbmg-card:hover {
            transform: translateY(-2px);
        }

        .rbmg-card-header {
            background: linear-gradient(135deg, #000b30, #2d102f);
            color: white;
            border-radius: 0;
            padding: 1rem;
            border: none;
        }

        .stat-card {
            background: white;
            border: 2px solid #ce4033;
            border-radius: 0;
            box-shadow: 0 4px 6px rgba(0, 11, 48, 0.1);
            transition: transform 0.25s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .custom-form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: border-color 0.3s ease;
        }
        .custom-form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        /* Priority badge styling using RBMG colors */
        .priority-high {
            border-left: 4px solid #ce4033 !important;
        }
        .priority-medium {
            border-left: 4px solid #f1c332 !important;
        }
        .priority-low {
            border-left: 4px solid #28a745 !important;
        }
        
        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fef3cd;
            color: #664d03;
        }
        .status-processing {
            background-color: #cff4fc;
            color: #055160;
        }
        .status-completed {
            background-color: #d1edff;
            color: #0c63e4;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* RBMG Color Classes */
        .text-danger {
            color: #ce4033 !important;
        }
        .bg-danger {
            background: #ce4033 !important;
        }
        .text-dark {
            color: #000b30 !important;
        }
        .bg-dark {
            background: #000b30 !important;
        }
        .text-purple {
            color: #2d102f !important;
        }
        .bg-purple {
            background: #2d102f !important;
        }
        .text-light {
            color: #f1f2f2 !important;
        }
        .bg-light {
            background: #f1f2f2 !important;
        }
    </style>
    </style>
</head>
<body>
    <!-- Header -->
    <div class="rbmg-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-4">
                <div>
                    <h1 class="display-5 fw-bold mb-2"><i class="bi bi-robot me-3"></i>Automation Settings</h1>
                    <p class="lead mb-0">Configure automated blog post generation and scheduling</p>
                </div>
                <div class="d-flex gap-3">
                    <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-house me-2"></i>Dashboard
                    </a>
                    <a href="admin.php" class="btn btn-light btn-lg">
                        <i class="bi bi-gear me-2"></i>Manage Content
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-12">
                <!-- Company Selection -->
            <div class="rbmg-card mb-4">
                <div class="rbmg-card-header">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Select Company</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <select name="company_id" class="form-select" onchange="this.form.submit()">
                                <option value="0">Select a company...</option>
                                <?php foreach ($companies as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $company_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['company_name']) ?> (<?= htmlspecialchars($c['company_type']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-rbmg-primary w-100">
                                <i class="bi bi-search"></i> Load Company
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($company): ?>
            <!-- Company Info Header -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title text-primary">
                                <i class="bi bi-building-check"></i> <?= htmlspecialchars($company['company_name']) ?>
                            </h4>
                            <p class="card-text">
                                <span class="badge bg-secondary me-2"><?= htmlspecialchars($company['company_type']) ?></span>
                                <span class="text-muted"><?= htmlspecialchars($company['location']) ?></span>
                            </p>
                            <p class="card-text small text-muted mb-0">
                                <i class="bi bi-clock"></i> <?= htmlspecialchars($company['hours']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-grid gap-2">
                        <a href="admin.php?company_id=<?= $company_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-gear"></i> Content Manager
                        </a>
                        <a href="setup_automation.php?company_id=<?= $company_id ?>" class="btn btn-outline-success">
                            <i class="bi bi-tools"></i> Setup Automation
                        </a>
                    </div>
                </div>
            </div>

<!-- Company Overview -->
<div class="rbmg-card mb-4">
    <div class="rbmg-card-header">
        <h4 class="mb-0"><?= htmlspecialchars($company['company_name']) ?> - Automation Status</h4>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <strong class="me-2">Status:</strong> 
                    <span class="automation-status <?= $company['auto_posting_enabled'] ? 'automation-enabled' : 'automation-disabled' ?>">
                        <?= $company['auto_posting_enabled'] ? 'ENABLED' : 'DISABLED' ?>
                    </span>
                </div>
            </div>
            <?php if ($company['auto_posting_enabled']): ?>
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <strong class="me-2">Frequency:</strong> 
                    <span><?= ucfirst($company['auto_posting_frequency']) ?> 
                    (every <?= $company['auto_posting_interval'] ?> <?= $company['auto_posting_frequency'] === 'hourly' ? 'hour(s)' : ($company['auto_posting_frequency'] === 'daily' ? 'day(s)' : ($company['auto_posting_frequency'] === 'weekly' ? 'week(s)' : 'month(s)')) ?>)</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($company['auto_posting_enabled'] && $company['next_auto_post']): ?>
        <div class="mt-3 p-3 bg-light rounded">
            <div class="row">
                <div class="col-md-6">
                    <strong>📅 Next Scheduled Post:</strong><br>
                    <?= date('F j, Y g:i A', strtotime($company['next_auto_post'])) ?>
                </div>
                <?php if ($company['last_auto_post']): ?>
                <div class="col-md-6">
                    <strong>Last Post:</strong><br>
                    <?= date('F j, Y g:i A', strtotime($company['last_auto_post'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="stat-card text-center p-4">
            <div class="stat-number display-4 fw-bold mb-2" style="color: #ce4033;"><?= count($keywords) ?></div>
            <div class="text-muted">Target Keywords</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center p-4">
            <div class="stat-number display-4 fw-bold mb-2" style="color: #000b30;"><?= count($zip_targets) ?></div>
            <div class="text-muted">ZIP Targets</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center p-4">
            <div class="stat-number display-4 fw-bold mb-2" style="color: #2d102f;"><?= count(array_filter($queue_items, fn($q) => $q['status'] === 'pending')) ?></div>
            <div class="text-muted">Pending Posts</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center p-4">
            <div class="stat-number display-4 fw-bold mb-2" style="color: #57165b;"><?= count(array_filter($queue_items, fn($q) => $q['status'] === 'completed')) ?></div>
            <div class="text-muted">Completed Today</div>
        </div>
    </div>
</div>

<!-- Automation Settings -->
<div class="rbmg-card mb-4">
    <div class="rbmg-card-header">
        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Automation Settings</h5>
    </div>
    <div class="card-body p-4">
        <form id="automationForm">
            <input type="hidden" name="company_id" value="<?= $company_id ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Enable Automation:</label>
                    <select name="auto_posting_enabled" class="form-select">
                        <option value="0" <?= !$company['auto_posting_enabled'] ? 'selected' : '' ?>>Disabled</option>
                        <option value="1" <?= $company['auto_posting_enabled'] ? 'selected' : '' ?>>Enabled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Posting Frequency:</label>
                    <select name="auto_posting_frequency" class="form-select">
                        <option value="daily" <?= $company['auto_posting_frequency'] === 'daily' ? 'selected' : '' ?>>Daily (once per day)</option>
                        <option value="weekly" <?= $company['auto_posting_frequency'] === 'weekly' ? 'selected' : '' ?>>Weekly (once per week)</option>
                        <option value="monthly" <?= $company['auto_posting_frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly (once per month)</option>
                    </select>
                    <input type="hidden" name="auto_posting_interval" value="1">
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-top">
                <button type="button" onclick="saveAutomationSettings()" class="btn btn-rbmg-primary me-2">Save Settings</button>
                <?php if ($company['auto_posting_enabled']): ?>
                <button type="button" onclick="generateQueue()" class="btn btn-rbmg-secondary">Generate Queue Now</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Keyword Management -->
<div class="rbmg-card mb-4">
    <div class="rbmg-card-header">
        <h5 class="mb-0"><i class="bi bi-bullseye me-2"></i>Target Keywords</h5>
    </div>
    <div class="card-body p-4">
        <div class="mb-4 pb-4 border-bottom">
            <h6 class="text-muted mb-3"><i class="bi bi-plus-circle me-2"></i>Add New ZIP Target</h6>
            <div class="row g-3">"><i class="bi bi-plus-circle me-2"></i>Add New Keyword</h6>
            <div class="row g-3">
                <div class="col-md-5">
                    <label for="newKeyword" class="form-label fw-bold">Keyword Pattern</label>
                    <input type="text" id="newKeyword" class="form-control" placeholder="Enter keyword pattern">
                </div>
            <div class="col-md-2">
                <label for="keywordType" class="form-label fw-bold">Type</label>
                <select id="keywordType" class="form-select">
                    <option value="primary">Primary</option>
                    <option value="secondary">Secondary</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="keywordPriority" class="form-label fw-bold">Priority</label>
                <input type="number" id="keywordPriority" class="form-control" placeholder="0-100" value="0" min="0" max="100">
                <small class="text-muted">Scale: 0-100 (higher = more important)</small>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button onclick="addKeyword()" class="btn btn-rbmg-primary w-100">Add Keyword</button>
            </div>
            </div>
        </div>
        
        <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted mb-0"><i class="bi bi-list me-2"></i>Current Keywords</h6>
                <div class="d-flex gap-2">
                    <button id="selectAllKeywords" class="btn btn-outline-secondary btn-sm">Select All</button>
                    <button id="deleteSelectedKeywords" class="btn btn-outline-danger btn-sm" disabled>Delete Selected</button>
                </div>
            </div>
            <div id="keywordsList" class="mt-3">
        <?php foreach ($keywords as $kw): ?>
            <div class="keyword-item priority-<?= $kw['priority'] > 50 ? 'high' : ($kw['priority'] > 25 ? 'medium' : 'low') ?> mb-3 p-4 border rounded-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="form-check-input me-3 keyword-checkbox" value="<?= $kw['id'] ?>">
                    <div>
                        <strong><?= htmlspecialchars($kw['keyword_pattern']) ?></strong>
                        <span class="text-muted ms-2">
                            (<?= $kw['keyword_type'] ?>, Priority: <?= $kw['priority'] ?>)
                        </span>
                    </div>
                </div>
                <button onclick="removeKeyword(<?= $kw['id'] ?>)" class="btn btn-outline-danger btn-sm">Remove</button>
            </div>
        </div>
        <?php endforeach; ?>
        
            <?php if (empty($keywords)): ?>
            <div class="text-center py-5 border rounded-3 bg-light">
                <i class="bi bi-bullseye text-muted opacity-50" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-2 fs-5">No keywords configured yet</p>
                <small class="text-muted">Add keywords above to get started with automation</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ZIP Code Targets -->
<div class="rbmg-card mb-4">
    <div class="rbmg-card-header">
        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>ZIP Code Targets</h5>
    </div>
    <div class="card-body p-4">
        <div class="mb-4">
            <h6 class="text-muted mb-3">Add New ZIP Target</h6>
            <div class="row g-3">
            <h6 class="text-muted mb-3">Add New Keyword</h6>
            <div class="row g-3">
            <div class="col-md-3">
                <label for="newZipCode" class="form-label fw-bold">ZIP Code</label>
                <input type="text" id="newZipCode" class="form-control" placeholder="Enter ZIP code" maxlength="10">
            </div>
            <div class="col-md-2">
                <label for="zipPriority" class="form-label fw-bold">Priority</label>
                <input type="number" id="zipPriority" class="form-control" placeholder="0-100" value="0" min="0" max="100">
                <small class="text-muted">Scale: 0-100 (higher = more important)</small>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button onclick="addZipTarget()" class="btn btn-rbmg-primary w-100">Add ZIP Target</button>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button onclick="importZipsFromPosts()" class="btn btn-outline-secondary w-100">Import from Existing Posts</button>
            </div>
            </div>
        </div>
        
        <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted mb-0"><i class="bi bi-geo me-2"></i>Current ZIP Targets</h6>
                <div class="d-flex gap-2">
                    <button id="selectAllZips" class="btn btn-outline-secondary btn-sm">Select All</button>
                    <button id="deleteSelectedZips" class="btn btn-outline-danger btn-sm" disabled>Delete Selected</button>
                </div>
            </div>
            <div id="zipTargetsList" class="mt-3">
            <?php foreach ($zip_targets as $zip): ?>
            <div class="zip-item priority-<?= $zip['priority'] > 50 ? 'high' : ($zip['priority'] > 25 ? 'medium' : 'low') ?> mb-3 p-4 border rounded-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <input type="checkbox" class="form-check-input me-3 zip-checkbox" value="<?= $zip['id'] ?>">
                        <div>
                            <strong><?= htmlspecialchars($zip['zip_code']) ?></strong>
                            <span class="text-muted ms-2">
                                (Priority: <?= $zip['priority'] ?>, Posts: <?= $zip['posts_generated'] ?>)
                                <?php if ($zip['last_posted']): ?>
                                - Last: <?= date('M j', strtotime($zip['last_posted'])) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <button onclick="removeZipTarget(<?= $zip['id'] ?>)" class="btn btn-outline-danger btn-sm">Remove</button>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($zip_targets)): ?>
            <div class="text-center py-5 border rounded-3 bg-light">
                <i class="bi bi-geo-alt text-muted opacity-50" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-2 fs-5">No ZIP codes configured yet</p>
                <small class="text-muted">Add ZIP codes above or import from existing posts</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    </div>
</div>

<!-- Recent Queue Activity -->
<div class="rbmg-card mb-4">
    <div class="rbmg-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📋 Recent Queue Activity</h5>
        <div>
            <button onclick="loadQueue()" class="btn btn-outline-light btn-sm me-2">Refresh</button>
            <button onclick="clearCompletedQueue()" class="btn btn-outline-danger btn-sm">Clear Completed</button>
        </div>
    </div>
    <div class="card-body p-4">
        <div id="queueList" class="mt-3">
            <?php foreach ($queue_items as $item): ?>
            <div class="queue-item mb-3 p-4 border rounded-3 shadow-sm bg-white">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong><?= htmlspecialchars($item['keyword']) ?></strong> in <?= htmlspecialchars($item['zip_code']) ?>
                        <br><small class="text-muted">Scheduled: <?= date('M j, Y g:i A', strtotime($item['scheduled_for'])) ?></small>
                        <?php if ($item['post_title']): ?>
                        <br><small class="fst-italic text-secondary"><?= htmlspecialchars($item['post_title']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="status-badge status-<?= $item['status'] ?>">
                            <?= strtoupper($item['status']) ?>
                        </span>
                    </div>
                </div>
                <?php if ($item['error_message']): ?>
                <div class="mt-2 text-danger small">
                    <strong>Error:</strong> <?= htmlspecialchars($item['error_message']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($queue_items)): ?>
            <div class="text-center py-5 border rounded-3 bg-light">
                <i class="bi bi-list-check text-muted opacity-50" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-2 fs-5">No recent queue activity</p>
                <small class="text-muted">Queue items will appear here when automation is running</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<div class="rbmg-card">
    <div class="card-body text-center p-5">
        <i class="bi bi-building text-muted mb-3" style="font-size: 3rem;"></i>
        <p class="mb-0 fs-5">Select a company above to configure automated posting.</p>
    </div>
</div>
<?php endif; ?>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>

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

// Multi-select functionality for keywords
document.addEventListener('DOMContentLoaded', function() {
    // Keyword multi-select
    const selectAllKeywords = document.getElementById('selectAllKeywords');
    const deleteSelectedKeywords = document.getElementById('deleteSelectedKeywords');
    const keywordCheckboxes = () => document.querySelectorAll('.keyword-checkbox');
    
    if (selectAllKeywords) {
        selectAllKeywords.addEventListener('click', function() {
            const checkboxes = keywordCheckboxes();
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            this.textContent = allChecked ? 'Select All' : 'Deselect All';
            updateDeleteButton('keyword');
        });
    }
    
    if (deleteSelectedKeywords) {
        deleteSelectedKeywords.addEventListener('click', function() {
            const selected = Array.from(keywordCheckboxes()).filter(cb => cb.checked).map(cb => cb.value);
            if (selected.length === 0) return;
            
            if (!confirm(`Delete ${selected.length} selected keyword(s)?`)) return;
            
            fetch('api/automation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'bulk_delete_keywords',
                    keyword_ids: selected
                })
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(`Deleted ${selected.length} keyword(s)`);
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error deleting keywords: ' + error.message);
            });
        });
    }
    
    // ZIP targets multi-select
    const selectAllZips = document.getElementById('selectAllZips');
    const deleteSelectedZips = document.getElementById('deleteSelectedZips');
    const zipCheckboxes = () => document.querySelectorAll('.zip-checkbox');
    
    if (selectAllZips) {
        selectAllZips.addEventListener('click', function() {
            const checkboxes = zipCheckboxes();
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            this.textContent = allChecked ? 'Select All' : 'Deselect All';
            updateDeleteButton('zip');
        });
    }
    
    if (deleteSelectedZips) {
        deleteSelectedZips.addEventListener('click', function() {
            const selected = Array.from(zipCheckboxes()).filter(cb => cb.checked).map(cb => cb.value);
            if (selected.length === 0) return;
            
            if (!confirm(`Delete ${selected.length} selected ZIP target(s)?`)) return;
            
            fetch('api/automation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'bulk_delete_zips',
                    zip_ids: selected
                })
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(`Deleted ${selected.length} ZIP target(s)`);
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error deleting ZIP targets: ' + error.message);
            });
        });
    }
    
    // Event delegation for checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.matches('.keyword-checkbox')) {
            updateDeleteButton('keyword');
        } else if (e.target.matches('.zip-checkbox')) {
            updateDeleteButton('zip');
        }
    });
});

function updateDeleteButton(type) {
    const checkboxes = type === 'keyword' ? document.querySelectorAll('.keyword-checkbox') : document.querySelectorAll('.zip-checkbox');
    const deleteButton = type === 'keyword' ? document.getElementById('deleteSelectedKeywords') : document.getElementById('deleteSelectedZips');
    const selectAllButton = type === 'keyword' ? document.getElementById('selectAllKeywords') : document.getElementById('selectAllZips');
    
    const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    const totalCount = checkboxes.length;
    
    if (deleteButton) {
        deleteButton.disabled = selectedCount === 0;
        deleteButton.textContent = selectedCount > 0 ? `Delete Selected (${selectedCount})` : 'Delete Selected';
    }
    
    if (selectAllButton) {
        selectAllButton.textContent = selectedCount === totalCount && totalCount > 0 ? 'Deselect All' : 'Select All';
    }
}
</script>

<!-- Bootstrap 5 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>