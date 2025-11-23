<?php
// dashboard.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HVAC Keyword Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --rbmg-midnight: #000b30;
            --rbmg-purple: #57165b;
            --rbmg-danger: #ce4033;
            --rbmg-light: #f1f2f2;
            --rbmg-gradient: linear-gradient(60deg, #000b30 10%, #57165b 25%, #6f2c23 50%, #ce4033 85%);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--rbmg-light);
            color: var(--rbmg-midnight);
        }

        /* Custom Button Styles */
        .btn-rbmg-primary {
            background: var(--rbmg-danger);
            border-color: var(--rbmg-danger);
            color: white;
            font-weight: 500;
        }
        
        .btn-rbmg-primary:hover {
            background: #e14f3f;
            border-color: #e14f3f;
            color: white;
        }

        .btn-rbmg-secondary {
            background: var(--rbmg-midnight);
            border-color: var(--rbmg-midnight);
            color: white;
            font-weight: 500;
        }
        
        .btn-rbmg-secondary:hover {
            background: var(--rbmg-purple);
            border-color: var(--rbmg-purple);
            color: white;
        }

        .btn-outline-rbmg {
            border-color: var(--rbmg-danger);
            color: var(--rbmg-danger);
            font-weight: 500;
        }
        
        .btn-outline-rbmg:hover {
            background: var(--rbmg-danger);
            border-color: var(--rbmg-danger);
            color: white;
        }

        /* Header Gradient */
        .rbmg-header {
            background: var(--rbmg-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .rbmg-header h1 {
            font-weight: 700;
            margin: 0;
            font-size: 2.5rem;
        }

        /* Card Styles */
        .rbmg-card {
            background: white;
            border: 2px solid var(--rbmg-danger);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 11, 48, 0.1);
            transition: transform 0.2s ease;
        }

        .rbmg-card:hover {
            transform: translateY(-2px);
        }

        .rbmg-card-header {
            background: linear-gradient(135deg, var(--rbmg-midnight), var(--rbmg-purple));
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1.25rem;
            border-bottom: 2px solid var(--rbmg-danger);
        }

        .rbmg-card-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        /* Company Item Styles */
        .company-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .company-item:hover {
            border-color: var(--rbmg-danger);
            box-shadow: 0 2px 8px rgba(206, 64, 51, 0.15);
            transform: translateX(5px);
        }

        .company-item.selected {
            background: linear-gradient(135deg, rgba(0, 11, 48, 0.05), rgba(206, 64, 51, 0.05));
            border-color: var(--rbmg-danger);
            border-left: 4px solid var(--rbmg-danger);
        }

        /* Form Styles */
        .form-control, .form-select {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--rbmg-danger);
            box-shadow: 0 0 0 0.2rem rgba(206, 64, 51, 0.25);
        }

        /* Keyword Result Styles */
        .keyword-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .keyword-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--rbmg-danger);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .keyword-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 11, 48, 0.15);
        }

        .keyword-item:hover::before {
            transform: scaleY(1);
        }

        .keyword-score {
            background: var(--rbmg-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Blog Post Styles */
        .blog-post-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }

        .blog-post-item:hover {
            border-color: var(--rbmg-danger);
            box-shadow: 0 2px 8px rgba(206, 64, 51, 0.15);
        }

        .blog-post-title a {
            color: var(--rbmg-midnight);
            text-decoration: none;
            font-weight: 600;
        }

        .blog-post-title a:hover {
            color: var(--rbmg-danger);
        }

        /* Navigation Styles */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(206, 64, 51, 0.2);
        }

        /* Utility Classes */
        .text-rbmg-primary {
            color: var(--rbmg-danger) !important;
        }

        .text-rbmg-midnight {
            color: var(--rbmg-midnight) !important;
        }

        .bg-rbmg-gradient {
            background: var(--rbmg-gradient) !important;
        }

        /* Loading Spinner */
        .loading-spinner {
            width: 1.5rem;
            height: 1.5rem;
            border: 3px solid rgba(206, 64, 51, 0.2);
            border-top: 3px solid var(--rbmg-danger);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Badge Styles */
        .badge-rbmg {
            background: var(--rbmg-danger);
            color: white;
        }

        /* Section Headers */
        .section-header {
            border-bottom: 3px solid var(--rbmg-danger);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--rbmg-midnight);
            font-weight: 600;
        }

        /* Category Section Styling */
        .category-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .category-section:hover {
            box-shadow: 0 4px 12px rgba(0, 11, 48, 0.1);
        }

        .category-content {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        /* Enhanced keyword items for categories */
        .keyword-item.small {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .keyword-item.border-primary {
            border-color: var(--rbmg-danger) !important;
            box-shadow: 0 2px 4px rgba(206, 64, 51, 0.1);
        }

        .keyword-item.border-success {
            border-color: #198754 !important;
            box-shadow: 0 2px 4px rgba(25, 135, 84, 0.1);
        }

        .keyword-item.border-warning {
            border-color: #fd7e14 !important;
            box-shadow: 0 2px 4px rgba(253, 126, 20, 0.1);
        }

        .keyword-item.border-info {
            border-color: #0dcaf0 !important;
            box-shadow: 0 2px 4px rgba(13, 202, 240, 0.1);
        }

        .keyword-item.border-secondary {
            border-color: #6c757d !important;
            box-shadow: 0 2px 4px rgba(108, 117, 125, 0.1);
        }

        .keyword-item.border-danger {
            border-color: var(--rbmg-danger) !important;
            box-shadow: 0 2px 4px rgba(206, 64, 51, 0.1);
        }

        /* Badge variations for different priorities */
        .badge.bg-success {
            background: #198754 !important;
        }

        .badge.bg-warning {
            background: #fd7e14 !important;
            color: white !important;
        }

        /* Hide class for Bootstrap compatibility */
        .d-none { display: none !important; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="rbmg-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="bi bi-thermometer-half me-3"></i>
                        HVAC Keyword Research Tool
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">Professional HVAC Content Generation with Local Expertise</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        <button class="btn btn-outline-light btn-sm" onclick="window.location.reload()">
                            <i class="bi bi-house-door"></i> Dashboard
                        </button>
                        <button class="btn btn-outline-light btn-sm" onclick="window.open('admin.php', '_blank')">
                            <i class="bi bi-gear"></i> Admin
                        </button>
                        <button class="btn btn-outline-light btn-sm" onclick="window.open('automation.php', '_blank')">
                            <i class="bi bi-robot"></i> Automation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Company Selection Section -->
        <div id="companySection" class="mb-4">
            <div class="rbmg-card">
                <div class="rbmg-card-header">
                    <h2><i class="bi bi-building me-2"></i>Step 1: Company Information</h2>
                </div>
                <div class="card-body p-4">
                    <!-- Company Selection Buttons -->
                    <div id="companySelection" class="text-center">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-rbmg-primary btn-lg w-100 mb-3" onclick="showNewCompanyForm()">
                                    <i class="bi bi-plus-circle me-2"></i>Create New Company
                                </button>
                                <button type="button" class="btn btn-outline-rbmg btn-lg w-100" onclick="showExistingCompanies()">
                                    <i class="bi bi-list me-2"></i>Select Existing Company
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Companies List -->
                    <div id="existingCompanies" class="d-none">
                        <h3 class="section-header">
                            <i class="bi bi-buildings me-2"></i>Select Your Company
                        </h3>
                        <div id="companiesList" class="mb-3">
                            <div class="text-center py-4">
                                <div class="loading-spinner"></div>
                                <span>Loading companies...</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-rbmg-secondary" onclick="showCompanySelection()">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </button>
                            <button type="button" class="btn btn-outline-rbmg" onclick="goToHomeDashboard()">
                                <i class="bi bi-house-door me-1"></i>Dashboard Home
                            </button>
                        </div>
                    </div>

                    <!-- New Company Form -->
                    <div id="newCompanyForm" class="d-none">
                        <h3 class="section-header">
                            <i class="bi bi-plus-circle me-2"></i>Create New Company
                        </h3>
                        <form id="companyForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="companyName" class="form-label fw-semibold">Company Name</label>
                                        <input type="text" id="companyName" name="company_name" class="form-control" 
                                               placeholder="ABC HVAC Services" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="location" class="form-label fw-semibold">Location</label>
                                        <input type="text" id="location" name="location" class="form-control" 
                                               placeholder="City, State or Full Address" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="companyType" class="form-label fw-semibold">Company Type</label>
                                        <select id="companyType" name="company_type" class="form-select" required>
                                            <option value="">Select Company Type</option>
                                            <option value="HVAC">HVAC</option>
                                            <option value="Plumbing">Plumbing</option>
                                            <option value="Electric">Electric</option>
                                            <option value="Commercial HVAC">Commercial HVAC</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="hours" class="form-label fw-semibold">Business Hours</label>
                                        <textarea id="hours" name="hours" class="form-control" rows="3" 
                                                  placeholder="Mon-Fri: 8AM-6PM&#10;Sat: 9AM-4PM&#10;24/7 Emergency Service Available" required></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-rbmg-primary" onclick="registerCompany()">
                                    <i class="bi bi-check-circle me-1"></i>Create Company
                                </button>
                                <button type="button" class="btn btn-rbmg-secondary" onclick="showCompanySelection()">
                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                </button>
                                <button type="button" class="btn btn-outline-rbmg" onclick="goToHomeDashboard()">
                                    <i class="bi bi-house-door me-1"></i>Dashboard Home
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Selected Company Display -->
                    <div id="selectedCompanyDisplay" class="d-none">
                        <div class="alert alert-success border-0" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(25, 135, 84, 0.1));">
                            <h3 class="alert-heading mb-3">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>Company Selected
                            </h3>
                            <div id="selectedCompanyInfo" class="mb-3"></div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="changeCompany()">
                                    <i class="bi bi-arrow-repeat me-1"></i>Change Company
                                </button>
                                <button type="button" class="btn btn-rbmg-primary btn-sm" onclick="openAutomationSettings()">
                                    <i class="bi bi-robot me-1"></i>Automation Settings
                                </button>
                                <button type="button" class="btn btn-outline-rbmg btn-sm" onclick="openAdminPanel()">
                                    <i class="bi bi-gear me-1"></i>Manage Content
                                </button>
                            </div>
                        </div>
                        
                        <!-- Blog Posts Section -->
                        <div id="companyBlogPosts" class="mt-4">
                            <h4 class="section-header">
                                <i class="bi bi-file-text me-2"></i>Generated Blog Posts
                            </h4>
                            <div id="blogPostsList">
                                <div class="text-center py-4">
                                    <div class="loading-spinner"></div>
                                    <span>Loading posts...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ZIP Code Search Section -->
        <div id="zipSection" class="mb-4 d-none">
            <div class="rbmg-card">
                <div class="rbmg-card-header">
                    <h2><i class="bi bi-search me-2"></i>Step 2: ZIP Code Keyword Research</h2>
                </div>
                <div class="card-body p-4">
                    <form id="zipForm" class="row align-items-end">
                        <div class="col-md-8">
                            <label for="zip" class="form-label fw-semibold">Enter a 5-Digit ZIP Code</label>
                            <input type="text" id="zip" name="zip" class="form-control" 
                                   placeholder="e.g., 90210" pattern="[0-9]{5}" maxlength="5">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-rbmg-primary w-100" onclick="fetchKeywords()">
                                <i class="bi bi-search me-2"></i>Search Keywords
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Keyword Results -->
        <div id="results"></div>

        <!-- Debug Section -->
        <div id="debugSection" class="mt-5">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-bug me-2"></i>Debug Tools
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="toggleDebug()">
                            Toggle Debug
                        </button>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="debugContent" class="d-none">
                        <div class="bg-dark text-light p-3 rounded" style="font-family: monospace; font-size: 0.875rem; max-height: 200px; overflow-y: auto;">
                            <p class="mb-0 text-muted">Debug console will appear here...</p>
                        </div>
                    </div>
                    
                    <!-- Version History Demo -->
                    <div class="alert alert-info border-0 mt-3" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.1), rgba(33, 37, 41, 0.05));">
                        <h5 class="alert-heading">
                            <i class="bi bi-clock-history me-2"></i>Version History Features
                        </h5>
                        <p>Version history is now integrated into the admin panel! Features include:</p>
                        <ul class="mb-3">
                            <li><strong>View Version History:</strong> See all revisions of each blog post</li>
                            <li><strong>Compare Versions:</strong> View differences between any two versions</li>
                            <li><strong>Revert Changes:</strong> Restore previous versions of content</li>
                            <li><strong>Edit with Tracking:</strong> All edits create new tracked versions</li>
                            <li><strong>User Attribution:</strong> Track who made what changes</li>
                            <li><strong>Automatic Cleanup:</strong> Old versions pruned after 6 months</li>
                        </ul>
                        <div class="d-flex gap-2 flex-wrap">
                            <button onclick="window.open('admin.php', '_blank')" class="btn btn-info btn-sm">
                                <i class="bi bi-target me-1"></i>Try Version History in Admin Panel
                            </button>
                            <button onclick="window.open('automation.php', '_blank')" class="btn btn-success btn-sm">
                                <i class="bi bi-robot me-1"></i>Configure Automated Posting
                            </button>
                        </div>
                    </div>
                    
                    <!-- Setup Required -->
                    <div class="alert alert-warning border-0">
                        <h5 class="alert-heading">
                            <i class="bi bi-tools me-2"></i>Database Setup
                        </h5>
                        <p class="mb-3">If you see database errors, you may need to set up the automation tables:</p>
                        <button onclick="window.open('setup_automation.php', '_blank')" class="btn btn-warning btn-sm">
                            <i class="bi bi-wrench me-1"></i>Run Database Setup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Global variables
let selectedCompanyId = null;
let selectedCompanyData = null;
let debugMode = true;

// Debug helper function
function addDebugLog(message) {
    if (!debugMode) return;

    const now = new Date().toLocaleTimeString();
    const debugDiv = document.getElementById('debugContent');
    if (!debugDiv) {
        console.log(message);
        return;
    }
    debugDiv.innerHTML += `[${now}] ${message}<br>`;
    debugDiv.scrollTop = debugDiv.scrollHeight;
    console.log(message);
}

function toggleDebug() {
    const debugSection = document.getElementById('debugSection');
    debugMode = !debugSection.classList.contains('d-none');
    if (debugMode) {
        debugSection.classList.add('d-none');
        debugMode = false;
    } else {
        debugSection.classList.remove('d-none');
        debugMode = true;
    }
}

// Company Management Functions
function showNewCompanyForm() {
    addDebugLog('Showing new company form');
    document.getElementById('companySelection').classList.add('d-none');
    document.getElementById('existingCompanies').classList.add('d-none');
    document.getElementById('newCompanyForm').classList.remove('d-none');
}

function showExistingCompanies() {
    addDebugLog('Showing existing companies');
    document.getElementById('companySelection').classList.add('d-none');
    document.getElementById('newCompanyForm').classList.add('d-none');
    document.getElementById('existingCompanies').classList.remove('d-none');
    loadExistingCompanies();
}

function showCompanySelection() {
    addDebugLog('Showing company selection');
    document.getElementById('companySelection').classList.remove('d-none');
    document.getElementById('existingCompanies').classList.add('d-none');
    document.getElementById('newCompanyForm').classList.add('d-none');
    document.getElementById('selectedCompanyDisplay').classList.add('d-none');
    clearCompanyForm();
}

function changeCompany() {
    addDebugLog('Changing company');
    selectedCompanyId = null;
    selectedCompanyData = null;
    document.getElementById('zipSection').classList.add('d-none');
    document.getElementById('results').innerHTML = '';
    showCompanySelection();
}

function openAutomationSettings() {
    if (!selectedCompanyId) {
        alert('No company selected');
        return;
    }
    addDebugLog(`Opening automation settings for company ID: ${selectedCompanyId}`);
    window.open(`automation.php?company_id=${selectedCompanyId}`, '_blank');
}

function openAdminPanel() {
    if (!selectedCompanyId) {
        alert('No company selected');
        return;
    }
    addDebugLog(`Opening admin panel for company ID: ${selectedCompanyId}`);
    window.open(`admin.php?company_id=${selectedCompanyId}`, '_blank');
}

function goToHomeDashboard() {
    addDebugLog('Returning to dashboard home');
    // Reset everything and go back to initial state
    selectedCompanyId = null;
    selectedCompanyData = null;
    document.getElementById('zipSection').classList.add('d-none');
    document.getElementById('results').innerHTML = '';
    showCompanySelection();
}

function clearCompanyForm() {
    document.getElementById('companyForm').reset();
}

function loadExistingCompanies() {
    addDebugLog('Loading existing companies...');
    document.getElementById('companiesList').innerHTML = `
        <div class="text-center py-4">
            <div class="loading-spinner"></div>
            <span>Loading companies...</span>
        </div>
    `;
    
    fetch('api/company.php')
        .then(r => {
            addDebugLog(`API response status: ${r.status}`);
            if (!r.ok) {
                throw new Error(`Network response was not ok: ${r.status}`);
            }
            return r.json();
        })
        .then(data => {
            addDebugLog(`Companies API response: ${JSON.stringify(data)}`);
            
            if (data.error) {
                document.getElementById('companiesList').innerHTML = '<p>Error loading companies: ' + data.error + '</p>';
                addDebugLog(`API returned error: ${data.error}`);
                return;
            }

            let html = '';
            if (!data.companies || data.companies.length === 0) {
                html = `
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-building display-1 opacity-25"></i>
                        <p class="mt-3">No companies found.</p>
                        <p class="small">Create a new one to get started!</p>
                    </div>
                `;
                addDebugLog('No companies found in response');
            } else {
                addDebugLog(`Found ${data.companies.length} companies`);
                data.companies.forEach((company, index) => {
                    addDebugLog(`Company ${index}: ID=${company.id}, Name=${company.company_name}, Posts=${company.post_count}`);
                    // Store company data in a data attribute instead of onclick parameters
                    html += `
                        <div class="company-item" data-company-id="${company.id}" data-company-index="${index}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-rbmg-midnight">${escapeHtml(company.company_name)}</h6>
                                    <div class="text-muted small">
                                        <i class="bi bi-geo-alt me-1"></i>${escapeHtml(company.location)}
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-tag me-1"></i>${escapeHtml(company.company_type)}
                                    </div>
                                </div>
                                <span class="badge badge-rbmg">${company.post_count} posts</span>
                            </div>
                        </div>
                    `;
                });
                
                // Store companies data globally for access
                window.companiesData = data.companies;
                addDebugLog(`Stored ${window.companiesData.length} companies globally`);
            }
            
            document.getElementById('companiesList').innerHTML = html;
            
            // Add click event listeners to company items
            const companyItems = document.querySelectorAll('.company-item');
            addDebugLog(`Adding click listeners to ${companyItems.length} company items`);
            
            companyItems.forEach(item => {
                item.addEventListener('click', function() {
                    const companyId = this.getAttribute('data-company-id');
                    const companyIndex = this.getAttribute('data-company-index');
                    addDebugLog(`Company item clicked: ID=${companyId}, Index=${companyIndex}`);
                    selectExistingCompanyFast(companyId, companyIndex);
                });
            });
        })
        .catch(err => {
            addDebugLog(`Error loading companies: ${err.message}`);
            console.error('Error loading companies:', err);
            document.getElementById('companiesList').innerHTML = '<p>Error loading companies. Please check your connection and try again.</p>';
        });
}

// New function for immediate company selection
function selectExistingCompanyFast(companyId, companyIndex) {
    addDebugLog(`Fast selecting company: ID=${companyId}, Index=${companyIndex}`);
    
    if (!window.companiesData || !window.companiesData[companyIndex]) {
        addDebugLog(`Error: Company data not found for index ${companyIndex}`);
        alert('Error: Company data not found');
        return;
    }
    
    const company = window.companiesData[companyIndex];
    selectedCompanyId = parseInt(companyId);
    selectedCompanyData = {
        id: company.id,
        company_name: company.company_name,
        location: company.location,
        company_type: company.company_type,
        hours: company.hours || 'Not specified'
    };
    
    addDebugLog(`Selected company data: ${JSON.stringify(selectedCompanyData)}`);
    
    // Show company info immediately
    document.getElementById('companySelection').classList.add('d-none');
    document.getElementById('existingCompanies').classList.add('d-none');
    document.getElementById('selectedCompanyDisplay').classList.remove('d-none');
    document.getElementById('zipSection').classList.remove('d-none');
    
    document.getElementById('selectedCompanyInfo').innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <h5 class="mb-2 text-rbmg-midnight">${escapeHtml(selectedCompanyData.company_name)}</h5>
                <p class="mb-1">
                    <i class="bi bi-geo-alt text-rbmg-primary me-2"></i>
                    ${escapeHtml(selectedCompanyData.location)}
                </p>
                <p class="mb-0">
                    <i class="bi bi-tag text-rbmg-primary me-2"></i>
                    ${escapeHtml(selectedCompanyData.company_type)}
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-success fs-6">Active</span>
            </div>
        </div>
    `;
    
    // Show blog posts loading and fetch them
    document.getElementById('blogPostsList').innerHTML = `
        <div style="text-align: center; padding: 15px; color: #666;">
            <div class="loading-spinner"></div>
            <strong>Loading ${company.post_count} blog posts...</strong>
        </div>
    `;
    
    // Fetch blog posts but don't wait
    fetchBlogPostsAsync(companyId);
    
    addDebugLog('Company selected and displayed instantly');
}

// Async function to load blog posts without blocking
function fetchBlogPostsAsync(companyId) {
    addDebugLog(`Async loading blog posts for company ID: ${companyId}`);
    
    fetch(`api/company.php?id=${companyId}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById('blogPostsList').innerHTML = '<p>Error loading blog posts: ' + data.error + '</p>';
                return;
            }
            displayBlogPosts(data.blog_posts, data.post_count);
            addDebugLog(`Blog posts loaded and displayed (${data.blog_posts.length} posts)`);
        })
        .catch(err => {
            addDebugLog(`Error loading blog posts: ${err.message}`);
            document.getElementById('blogPostsList').innerHTML = '<p>Error loading blog posts. Please try again.</p>';
        });
}

// Original function (kept for compatibility, but now loads in background)
function selectExistingCompany(companyId, companyIndex) {
    addDebugLog(`Selecting company: ID=${companyId}, Index=${companyIndex}`);
    
    if (!window.companiesData || !window.companiesData[companyIndex]) {
        addDebugLog(`Error: Company data not found for index ${companyIndex}`);
        alert('Error: Company data not found');
        return;
    }
    
    // Show loading state immediately
    document.getElementById('companySelection').classList.add('d-none');
    document.getElementById('existingCompanies').classList.add('d-none');
    document.getElementById('selectedCompanyDisplay').classList.remove('d-none');
    document.getElementById('selectedCompanyInfo').innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <strong>Loading ${company.company_name}...</strong><br>
            <div style="margin-top: 10px;">⏳ Fetching company details and blog posts</div>
        </div>
    `;
    document.getElementById('blogPostsList').innerHTML = `
        <div style="text-align: center; padding: 20px; color: #666;">
            <strong>Loading blog posts...</strong><br>
            <small>Please wait while we fetch the content</small>
        </div>
    `;
    
    const company = window.companiesData[companyIndex];
    selectedCompanyId = parseInt(companyId);
    
    // Immediately fetch full company details including blog posts
    addDebugLog(`Fetching full details for company ID: ${companyId}`);
    
    fetch(`api/company.php?id=${companyId}`)
        .then(r => {
            addDebugLog(`Company details API response status: ${r.status}`);
            if (!r.ok) {
                throw new Error(`Network response was not ok: ${r.status}`);
            }
            return r.json();
        })
        .then(data => {
            addDebugLog(`Company details response: ${JSON.stringify(data)}`);
            
            if (data.error) {
                alert('Error loading company details: ' + data.error);
                showCompanySelection();
                return;
            }
            
            // Store the full company data
            selectedCompanyData = {
                id: data.company.id,
                company_name: data.company.company_name,
                location: data.company.location,
                company_type: data.company.company_type,
                hours: data.company.hours || 'Not specified'
            };
            
            addDebugLog(`Selected company data: ${JSON.stringify(selectedCompanyData)}`);
            
            // Show company info
            showSelectedCompanyWithPosts(data.blog_posts, data.post_count);
        })
        .catch(err => {
            addDebugLog(`Error loading company details: ${err.message}`);
            alert('Error loading company details. Please try again.');
            showCompanySelection();
        });
}

function showSelectedCompanyWithPosts(blogPosts, postCount) {
    addDebugLog(`Showing selected company with ${blogPosts ? blogPosts.length : 0} posts`);
    
    if (!selectedCompanyData || !selectedCompanyData.company_name) {
        addDebugLog('Error: No company data available for display');
        alert('Error: No company data available');
        return;
    }
    
    // Company is already shown, just update the content
    document.getElementById('selectedCompanyInfo').innerHTML = `
        <strong>${escapeHtml(selectedCompanyData.company_name)}</strong><br>
        📍 ${escapeHtml(selectedCompanyData.location)}<br>
        🏷️ ${escapeHtml(selectedCompanyData.company_type)}
    `;
    
    // Show blog posts immediately
    displayBlogPosts(blogPosts, postCount);
    
    // Show the ZIP section
    document.getElementById('zipSection').classList.remove('d-none');
    
    addDebugLog('Company display and ZIP section shown with blog posts');
}

function showSelectedCompany() {
    addDebugLog(`Showing selected company: ${selectedCompanyData ? selectedCompanyData.company_name : 'null'}`);
    
    if (!selectedCompanyData || !selectedCompanyData.company_name) {
        addDebugLog('Error: No company data available for display');
        alert('Error: No company data available');
        return;
    }
    
    document.getElementById('companySelection').classList.add('d-none');
    document.getElementById('existingCompanies').classList.add('d-none');
    document.getElementById('newCompanyForm').classList.add('d-none');

    document.getElementById('selectedCompanyInfo').innerHTML = `
        <strong>${escapeHtml(selectedCompanyData.company_name)}</strong><br>
        📍 ${escapeHtml(selectedCompanyData.location)}<br>
        🏷️ ${escapeHtml(selectedCompanyData.company_type)}
    `;

    document.getElementById('selectedCompanyDisplay').classList.remove('d-none');
    document.getElementById('zipSection').classList.remove('d-none');    // For new companies, show empty blog posts section
    displayBlogPosts([], 0);
    
    addDebugLog('Company display and ZIP section shown');
}

function displayBlogPosts(posts, totalCount) {
    addDebugLog(`Displaying ${posts ? posts.length : 0} blog posts out of ${totalCount} total`);
    
    let html = '';
    
    if (!posts || posts.length === 0) {
        html = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-file-text display-1 opacity-25"></i>
                <p class="mt-3 mb-0">No blog posts generated yet.</p>
                <p class="small">Use the ZIP code search below to create your first post!</p>
            </div>
        `;
    } else {
        html += `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">
                    <strong>${totalCount}</strong> total posts 
                    ${totalCount > 10 ? '(showing latest 10)' : ''}
                </span>
                ${totalCount > 10 ? `<a href="admin.php?company_id=${selectedCompanyId}" target="_blank" class="btn btn-outline-rbmg btn-sm">View All Posts</a>` : ''}
            </div>
        `;
        
        posts.forEach(post => {
            const generatedDate = new Date(post.generated_at);
            const formattedDate = generatedDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            html += `
                <div class="blog-post-item">
                    <div class="row align-items-start">
                        <div class="col-md-8">
                            <div class="blog-post-title">
                                <a href="api/generate_post.php?company_id=${selectedCompanyId}&zip=${encodeURIComponent(post.zip_code)}&keyword=${encodeURIComponent(post.keyword)}" target="_blank">
                                    ${escapeHtml(post.title || post.keyword)}
                                </a>
                            </div>
                            <div class="blog-post-preview text-muted small mt-1">
                                ${escapeHtml(post.content_preview)}...
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="blog-post-meta">
                                <div class="d-flex flex-wrap gap-1 justify-content-md-end">
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-calendar3 me-1"></i>${formattedDate}
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="bi bi-geo-alt me-1"></i>ZIP ${post.zip_code}
                                    </span>
                                    <span class="badge badge-rbmg">
                                        <i class="bi bi-file-text me-1"></i>${post.word_count} words
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    document.getElementById('blogPostsList').innerHTML = html;
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function registerCompany() {
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function registerCompany() {
    const formData = {
        company_name: document.getElementById('companyName').value.trim(),
        location: document.getElementById('location').value.trim(),
        hours: document.getElementById('hours').value.trim(),
        company_type: document.getElementById('companyType').value
    };

    if (!formData.company_name || !formData.location || !formData.hours || !formData.company_type) {
        alert('Please fill in all fields');
        return;
    }

    fetch('api/company.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }

        selectedCompanyId = data.company_id;
        selectedCompanyData = formData;
        selectedCompanyData.id = data.company_id;
        
        addDebugLog(`New company created with ID: ${data.company_id}`);
        showSelectedCompany();
    })
    .catch(err => {
        alert('Error creating company.');
    });
}

function showSelectedCompany() {
    console.log('Showing selected company:', selectedCompanyData); // Debug log
    
    if (!selectedCompanyData || !selectedCompanyData.company_name) {
        alert('Error: No company data available');
        return;
    }
    
    document.getElementById('companySelection').classList.add('d-none');
    document.getElementById('existingCompanies').classList.add('d-none');
    document.getElementById('newCompanyForm').classList.add('d-none');
    
    document.getElementById('selectedCompanyInfo').innerHTML = `
        <strong>${escapeHtml(selectedCompanyData.company_name)}</strong><br>
        📍 ${escapeHtml(selectedCompanyData.location)}<br>
        🏷️ ${escapeHtml(selectedCompanyData.company_type)}
    `;
    
    document.getElementById('selectedCompanyDisplay').classList.remove('d-none');
    document.getElementById('zipSection').classList.remove('d-none');
}

// Keyword Search Functions
function fetchKeywords() {
    if (!selectedCompanyId) {
        alert("Please select or create a company first");
        return;
    }

    const zip = document.getElementById("zip").value.trim();
    if (!zip) {
        alert("Enter ZIP");
        return;
    }

    if (!/^\d{5}$/.test(zip)) {
        alert("Please enter a valid 5-digit ZIP code");
        return;
    }

    const resultsDiv = document.getElementById("results");
    resultsDiv.innerHTML = `
        <div class="rbmg-card">
            <div class="card-body text-center py-5">
                <div class="loading-spinner" style="width: 3rem; height: 3rem;"></div>
                <h5 class="mt-3 mb-1">Fetching Keywords</h5>
                <p class="text-muted mb-0">Analyzing HVAC search terms for ZIP ${zip}...</p>
            </div>
        </div>
    `;

    fetch("api/get_keywords.php?zip=" + zip)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error: ${data.error}
                    </div>
                `;
                return;
            }

            displayKeywordResults(data);
        })
        .catch(err => {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error fetching data. Please try again.
                </div>
            `;
        });
}

function displayKeywordResults(data) {
    const resultsDiv = document.getElementById("results");
    
    // Climate zone and location info
    let climateInfo = '';
    if (data.climate_zone) {
        climateInfo = `
            <div class="alert alert-info border-0 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            <i class="bi bi-thermometer me-2"></i>Climate Zone: <strong>${data.climate_zone}</strong>
                        </h6>
                        <p class="small mb-0 text-muted">
                            Cooling Priority: ${data.cooling_priority} | Heating Priority: ${data.heating_priority} | Season: ${data.current_season}
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="badge bg-primary px-3 py-2">ZIP ${data.zip}</div>
                    </div>
                </div>
            </div>
        `;
    }
    
    let html = climateInfo + `
        <div class="rbmg-card mb-4">
            <div class="rbmg-card-header">
                <h2><i class="bi bi-graph-up me-2"></i>Keywords for ZIP ${data.zip}</h2>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded p-3">
                            <h3 class="text-rbmg-primary mb-1">${data.keyword_count}</h3>
                            <p class="small text-muted mb-0">Total Keywords Found</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded p-3">
                            <h3 class="text-rbmg-midnight mb-1">${data.ranked_keywords.length}</h3>
                            <p class="small text-muted mb-0">Ranked Keywords</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded p-3">
                            <h3 class="text-success mb-1">${Object.keys(data.categories).length}</h3>
                            <p class="small text-muted mb-0">Categories</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Top 10 Ranked Keywords
    html += `
        <div class="rbmg-card mb-4">
            <div class="rbmg-card-header">
                <h3><i class="bi bi-fire me-2"></i>Top 10 Most Popular Searches</h3>
                <p class="mb-0 text-light opacity-75">Highest-scoring keywords based on search volume and climate relevance</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
    `;

    const top10 = data.ranked_keywords.slice(0, 10);
    top10.forEach((row, index) => {
        const encoded = encodeURIComponent(row.keyword);
        const isHighScore = row.score >= 80;
        const badgeClass = isHighScore ? 'bg-success' : (row.score >= 60 ? 'bg-warning' : 'bg-secondary');
        
        html += `
            <div class="col-lg-6">
                <div class="keyword-item ${isHighScore ? 'border-success' : ''}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-rbmg-midnight mb-2">
                                <span class="badge ${badgeClass} me-2">#${index + 1}</span>
                                ${row.keyword}
                            </div>
                            <a href="api/generate_post.php?company_id=${selectedCompanyId}&zip=${data.zip}&keyword=${encoded}" 
                               target="_blank" class="btn btn-rbmg-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>Generate Post
                            </a>
                        </div>
                        <span class="keyword-score">${row.score}</span>
                    </div>
                </div>
            </div>
        `;
    });

    html += `
                </div>
            </div>
        </div>
    `;

    // Categories - Enhanced Organization
    const categories = data.categories;
    const categoryNames = {
        cooling_issues: { 
            name: "Cooling Issues", 
            icon: "bi-snow", 
            color: "primary",
            description: "Air conditioning problems and cooling system failures"
        },
        heating_issues: { 
            name: "Heating Issues", 
            icon: "bi-fire", 
            color: "danger",
            description: "Furnace problems and heating system malfunctions"
        },
        heat_pump: { 
            name: "Heat Pump Problems", 
            icon: "bi-thermometer-half", 
            color: "warning",
            description: "Heat pump cooling and heating issues"
        },
        thermostat: { 
            name: "Thermostat Issues", 
            icon: "bi-sliders", 
            color: "info",
            description: "Thermostat malfunctions and control problems"
        },
        noise_smell: { 
            name: "Noises & Smells", 
            icon: "bi-soundwave", 
            color: "secondary",
            description: "Strange sounds and odors from HVAC systems"
        },
        airflow: { 
            name: "Airflow Problems", 
            icon: "bi-wind", 
            color: "success",
            description: "Weak airflow and ventilation issues"
        },
        leaks: { 
            name: "Leaks & Water Problems", 
            icon: "bi-droplet", 
            color: "info",
            description: "Water leaks and moisture issues"
        },
        electrical: { 
            name: "Electrical Issues", 
            icon: "bi-lightning", 
            color: "warning",
            description: "Power and electrical component problems"
        },
        efficiency: { 
            name: "Energy Efficiency", 
            icon: "bi-speedometer2", 
            color: "success",
            description: "High energy bills and efficiency concerns"
        },
        repair: { 
            name: "Repair & Service", 
            icon: "bi-tools", 
            color: "primary",
            description: "General repair and maintenance services"
        },
        troubleshooting: { 
            name: "Troubleshooting", 
            icon: "bi-search", 
            color: "secondary",
            description: "DIY troubleshooting and diagnostic queries"
        }
    };

    // Filter and sort categories by relevance
    const sortedCategories = Object.entries(categories)
        .filter(([key, items]) => items.length > 0)
        .sort((a, b) => b[1].length - a[1].length); // Sort by number of keywords

    if (sortedCategories.length > 0) {
        html += `
            <div class="rbmg-card">
                <div class="rbmg-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><i class="bi bi-grid-3x3-gap me-2"></i>Keyword Categories</h3>
                            <p class="mb-0 text-light opacity-75">Organized by HVAC problem type (max 150 keywords per category)</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-light btn-sm" onclick="expandAllCategories()">
                                <i class="bi bi-arrows-expand me-1"></i>Expand All
                            </button>
                            <button class="btn btn-outline-light btn-sm" onclick="collapseAllCategories()">
                                <i class="bi bi-arrows-collapse me-1"></i>Collapse All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
        `;

        sortedCategories.forEach(([key, items]) => {
            const category = categoryNames[key] || { 
                name: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), 
                icon: "bi-tag", 
                color: "secondary",
                description: "Related HVAC keywords"
            };
            
            // Limit to 150 keywords per category
            const limitedItems = items.slice(0, 150);
            const hasMore = items.length > 150;
            
            html += `
                <div class="category-section mb-5" id="category-${key}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="section-header mb-1">
                                <i class="${category.icon} text-${category.color} me-2"></i>
                                ${category.name}
                                <span class="badge bg-${category.color} ms-2">${limitedItems.length}${hasMore ? '+' : ''}</span>
                            </h4>
                            <p class="text-muted small mb-0">${category.description}</p>
                        </div>
                        <button class="btn btn-outline-${category.color} btn-sm" onclick="toggleCategory('${key}')">
                            <i class="bi bi-chevron-down" id="toggle-icon-${key}"></i>
                        </button>
                    </div>
                    
                    <div class="category-content" id="content-${key}" style="display: none;">
                        <div class="row g-2">
            `;

            limitedItems.forEach((kw, index) => {
                const encoded = encodeURIComponent(kw);
                const isHighPriority = index < 5; // First 5 are high priority
                const isMediumPriority = index >= 5 && index < 15; // Next 10 are medium priority
                
                let priorityIndicator = '';
                let borderClass = '';
                
                if (isHighPriority) {
                    priorityIndicator = `<i class="bi bi-star-fill text-${category.color} me-1" title="High priority keyword"></i>`;
                    borderClass = `border-${category.color}`;
                } else if (isMediumPriority) {
                    priorityIndicator = `<i class="bi bi-star text-${category.color} me-1 opacity-75" title="Medium priority keyword"></i>`;
                }
                
                html += `
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="keyword-item small ${borderClass}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <div class="fw-medium text-rbmg-midnight small">
                                        ${priorityIndicator}
                                        ${kw}
                                    </div>
                                </div>
                                <a href="api/generate_post.php?company_id=${selectedCompanyId}&zip=${data.zip}&keyword=${encoded}" 
                                   target="_blank" class="btn btn-outline-${category.color} btn-sm ms-2" title="Generate blog post">
                                    <i class="bi bi-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                        </div>
                        ${hasMore ? `
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Showing top 150 of ${items.length} keywords in this category
                                </small>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    }

    resultsDiv.innerHTML = html;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    addDebugLog('Page loaded, initializing...');
    // Start with company selection
    showCompanySelection();
    addDebugLog('Company selection shown');
});

// Category toggle function for collapsible keyword sections
function toggleCategory(categoryKey) {
    const content = document.getElementById(`content-${categoryKey}`);
    const icon = document.getElementById(`toggle-icon-${categoryKey}`);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.className = 'bi bi-chevron-up';
    } else {
        content.style.display = 'none';
        icon.className = 'bi bi-chevron-down';
    }
}

// Expand all categories
function expandAllCategories() {
    document.querySelectorAll('.category-content').forEach(content => {
        content.style.display = 'block';
    });
    document.querySelectorAll('[id^="toggle-icon-"]').forEach(icon => {
        icon.className = 'bi bi-chevron-up';
    });
}

// Collapse all categories
function collapseAllCategories() {
    document.querySelectorAll('.category-content').forEach(content => {
        content.style.display = 'none';
    });
    document.querySelectorAll('[id^="toggle-icon-"]').forEach(icon => {
        icon.className = 'bi bi-chevron-down';
    });
}
</script>

</body>
</html>
