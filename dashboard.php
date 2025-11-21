<?php
// dashboard.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>HVAC Keyword Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1000px; margin: auto; }
        input[type=text], textarea, select { padding: 10px; width: 300px; margin: 5px 0; }
        textarea { height: 80px; resize: vertical; }
        select { width: 320px; }
        button { padding: 10px 20px; cursor: pointer; margin: 10px 5px 10px 0; }
        .primary-btn { background: #0073e6; color: white; border: none; border-radius: 4px; }
        .secondary-btn { background: #f8f8f8; color: #333; border: 1px solid #ddd; border-radius: 4px; }
        .form-section { 
            background: #f9f9f9; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 20px 0; 
        }
        .section { margin-top: 40px; }
        .keyword-box, .rank-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 6px 0;
            background: #f8f8f8;
            border-radius: 4px;
        }
        .rank-score {
            float: right;
            background: #0073e6;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        a { text-decoration: none; color: #0073e6; font-weight: bold; }
        h2 { margin-top: 30px; }
        h3 { margin-top: 20px; }
        .hidden { display: none; }
        .company-info {
            background: #e8f4fd;
            border: 1px solid #0073e6;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .existing-companies {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
        }
        .company-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .company-item:hover {
            background-color: #f0f0f0;
        }
        .company-item.selected {
            background-color: #e8f4fd;
            border-left: 4px solid #0073e6;
        }
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        .blog-post-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px;
            margin: 8px 0;
            transition: background-color 0.2s;
        }
        .blog-post-item:hover {
            background-color: #f8f9fa;
        }
        .blog-post-title {
            font-weight: bold;
            color: #0073e6;
            margin-bottom: 5px;
        }
        .blog-post-meta {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 8px;
        }
        .blog-post-preview {
            font-size: 0.9em;
            color: #555;
            line-height: 1.4;
        }
        .blog-posts-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .post-count-badge {
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8em;
            margin-left: 8px;
        }
        .loading-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0073e6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #dee2e6;">
    <h1 style="margin: 0 0 10px 0;">HVAC Keyword Finder By ZIP Code</h1>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" class="secondary-btn" onclick="window.location.reload()" style="padding: 5px 10px; font-size: 0.9em;">🏠 Dashboard Home</button>
        <button type="button" class="secondary-btn" onclick="window.open('admin.php', '_blank')" style="padding: 5px 10px; font-size: 0.9em;">⚙️ Content Manager</button>
        <button type="button" class="secondary-btn" onclick="window.open('automation.php', '_blank')" style="padding: 5px 10px; font-size: 0.9em;">🤖 Automation</button>
        <!-- <button type="button" class="secondary-btn" onclick="window.open('debug_automation.php', '_blank')" style="padding: 5px 10px; font-size: 0.9em;">🔍 Debug System</button> -->
    </div>
</div>

<!-- Step 1: Company Selection/Registration -->
<div id="companySection" class="form-section">
    <h2>🏢 Step 1: Company Information</h2>
    
    <!-- Company Selection -->
    <div id="companySelection">
        <button type="button" class="primary-btn" onclick="showNewCompanyForm()">Create New Company</button>
        <button type="button" class="secondary-btn" onclick="showExistingCompanies()">Select Existing Company</button>
    </div>

    <!-- Existing Companies List -->
    <div id="existingCompanies" class="hidden">
        <h3>Select Your Company:</h3>
        <div id="companiesList" class="existing-companies">
            <p>Loading companies...</p>
        </div>
        <div style="margin-top: 15px;">
            <button type="button" class="secondary-btn" onclick="showCompanySelection()">← Back</button>
            <button type="button" class="secondary-btn" onclick="goToHomeDashboard()" style="margin-left: 10px;">🏠 Dashboard Home</button>
        </div>
    </div>

    <!-- New Company Form -->
    <div id="newCompanyForm" class="hidden">
        <h3>Create New Company:</h3>
        <form id="companyForm">
            <div class="form-group">
                <label for="companyName">Company Name:</label>
                <input type="text" id="companyName" name="company_name" placeholder="ABC HVAC Services" required>
            </div>
            
            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" placeholder="City, State or Full Address" required>
            </div>
            
            <div class="form-group">
                <label for="hours">Business Hours:</label>
                <textarea id="hours" name="hours" placeholder="Mon-Fri: 8AM-6PM&#10;Sat: 9AM-4PM&#10;24/7 Emergency Service Available" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="companyType">Company Type:</label>
                <select id="companyType" name="company_type" required>
                    <option value="">Select Company Type</option>
                    <option value="HVAC">HVAC</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Electric">Electric</option>
                    <option value="Commercial HVAC">Commercial HVAC</option>
                </select>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="button" class="primary-btn" onclick="registerCompany()">Create Company</button>
                <button type="button" class="secondary-btn" onclick="showCompanySelection()" style="margin-left: 10px;">Cancel</button>
                <button type="button" class="secondary-btn" onclick="goToHomeDashboard()" style="margin-left: 10px;">🏠 Dashboard Home</button>
            </div>
        </form>
    </div>

    <!-- Selected Company Display -->
    <div id="selectedCompanyDisplay" class="company-info hidden">
        <h3>✅ Selected Company:</h3>
        <div id="selectedCompanyInfo"></div>
        <div style="margin: 10px 0;">
            <button type="button" class="secondary-btn" onclick="changeCompany()">Change Company</button>
            <button type="button" class="primary-btn" onclick="openAutomationSettings()" style="margin-left: 10px;">🤖 Automation Settings</button>
            <button type="button" class="secondary-btn" onclick="openAdminPanel()" style="margin-left: 10px;">⚙️ Manage Content</button>
        </div>
        
        <!-- Blog Posts Section -->
        <div id="companyBlogPosts" style="margin-top: 20px;">
            <h4>📝 Generated Blog Posts</h4>
            <div id="blogPostsList">
                <p>Loading posts...</p>
            </div>
        </div>
    </div>
</div>

<!-- Step 2: ZIP Code Search (hidden until company selected) -->
<div id="zipSection" class="form-section hidden">
    <h2>🔍 Step 2: ZIP Code Keyword Research</h2>
    <form id="zipForm">
        <input type="text" id="zip" name="zip" placeholder="Enter ZIP code (ex: 90001)">
        <button type="button" class="primary-btn" onclick="fetchKeywords()">Search Keywords</button>
    </form>
</div>

<div id="results" style="margin-top: 20px;"></div>

    <!-- Debug Section (remove this in production) -->
    <div id="debugSection" class="form-section">
        <h3>🐞 Debug Tools <button onclick="toggleDebug()">Toggle Debug</button></h3>
        <div id="debugContent" style="display: none;">
            <p>Debug console will appear here...</p>
        </div>
        
        <!-- Version History Demo -->
        <div style="margin-top: 15px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
            <h4>📝 Version History Features</h4>
            <p>Version history is now integrated into the admin panel! Features include:</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>View Version History:</strong> See all revisions of each blog post</li>
                <li><strong>Compare Versions:</strong> View differences between any two versions</li>
                <li><strong>Revert Changes:</strong> Restore previous versions of content</li>
                <li><strong>Edit with Tracking:</strong> All edits create new tracked versions</li>
                <li><strong>User Attribution:</strong> Track who made what changes</li>
                <li><strong>Automatic Cleanup:</strong> Old versions pruned after 6 months</li>
            </ul>
            <button onclick="window.open('admin.php', '_blank')" class="btn btn-primary">
                🎯 Try Version History in Admin Panel
            </button>
            <button onclick="window.open('automation.php', '_blank')" class="btn btn-success">
                🤖 Configure Automated Posting
            </button>
        </div>
        
        <!-- Setup Required -->
        <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <h4>⚙️ Database Setup</h4>
            <p>If you see database errors, you may need to set up the automation tables:</p>
            <button onclick="window.open('setup_automation.php', '_blank')" class="btn btn-warning">
                🔧 Run Database Setup
            </button>
        </div>
    </div>

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
    debugMode = !debugSection.classList.contains('hidden');
    if (debugMode) {
        debugSection.classList.add('hidden');
        debugMode = false;
    } else {
        debugSection.classList.remove('hidden');
        debugMode = true;
    }
}

// Company Management Functions
function showNewCompanyForm() {
    addDebugLog('Showing new company form');
    document.getElementById('companySelection').classList.add('hidden');
    document.getElementById('existingCompanies').classList.add('hidden');
    document.getElementById('newCompanyForm').classList.remove('hidden');
}

function showExistingCompanies() {
    addDebugLog('Showing existing companies');
    document.getElementById('companySelection').classList.add('hidden');
    document.getElementById('newCompanyForm').classList.add('hidden');
    document.getElementById('existingCompanies').classList.remove('hidden');
    loadExistingCompanies();
}

function showCompanySelection() {
    addDebugLog('Showing company selection');
    document.getElementById('companySelection').classList.remove('hidden');
    document.getElementById('existingCompanies').classList.add('hidden');
    document.getElementById('newCompanyForm').classList.add('hidden');
    document.getElementById('selectedCompanyDisplay').classList.add('hidden');
    clearCompanyForm();
}

function changeCompany() {
    addDebugLog('Changing company');
    selectedCompanyId = null;
    selectedCompanyData = null;
    document.getElementById('zipSection').classList.add('hidden');
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
    document.getElementById('zipSection').classList.add('hidden');
    document.getElementById('results').innerHTML = '';
    showCompanySelection();
}

function clearCompanyForm() {
    document.getElementById('companyForm').reset();
}

function loadExistingCompanies() {
    addDebugLog('Loading existing companies...');
    document.getElementById('companiesList').innerHTML = '<p>Loading companies...</p>';
    
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
                html = '<p>No companies found. Create a new one first.</p>';
                addDebugLog('No companies found in response');
            } else {
                addDebugLog(`Found ${data.companies.length} companies`);
                data.companies.forEach((company, index) => {
                    addDebugLog(`Company ${index}: ID=${company.id}, Name=${company.company_name}, Posts=${company.post_count}`);
                    // Store company data in a data attribute instead of onclick parameters
                    html += `
                        <div class="company-item" data-company-id="${company.id}" data-company-index="${index}">
                            <strong>${escapeHtml(company.company_name)}</strong>
                            <span class="post-count-badge">${company.post_count} posts</span>
                            <br>
                            <small>${escapeHtml(company.location)} • ${escapeHtml(company.company_type)}</small>
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
    document.getElementById('companySelection').classList.add('hidden');
    document.getElementById('existingCompanies').classList.add('hidden');
    document.getElementById('selectedCompanyDisplay').classList.remove('hidden');
    document.getElementById('zipSection').classList.remove('hidden');
    
    document.getElementById('selectedCompanyInfo').innerHTML = `
        <strong>${escapeHtml(selectedCompanyData.company_name)}</strong><br>
        📍 ${escapeHtml(selectedCompanyData.location)}<br>
        🏷️ ${escapeHtml(selectedCompanyData.company_type)}
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
    document.getElementById('companySelection').classList.add('hidden');
    document.getElementById('existingCompanies').classList.add('hidden');
    document.getElementById('selectedCompanyDisplay').classList.remove('hidden');
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
    document.getElementById('zipSection').classList.remove('hidden');
    
    addDebugLog('Company display and ZIP section shown with blog posts');
}

function showSelectedCompany() {
    addDebugLog(`Showing selected company: ${selectedCompanyData ? selectedCompanyData.company_name : 'null'}`);
    
    if (!selectedCompanyData || !selectedCompanyData.company_name) {
        addDebugLog('Error: No company data available for display');
        alert('Error: No company data available');
        return;
    }
    
    document.getElementById('companySelection').classList.add('hidden');
    document.getElementById('existingCompanies').classList.add('hidden');
    document.getElementById('newCompanyForm').classList.add('hidden');
    
    document.getElementById('selectedCompanyInfo').innerHTML = `
        <strong>${escapeHtml(selectedCompanyData.company_name)}</strong><br>
        📍 ${escapeHtml(selectedCompanyData.location)}<br>
        🏷️ ${escapeHtml(selectedCompanyData.company_type)}
    `;
    
    document.getElementById('selectedCompanyDisplay').classList.remove('hidden');
    document.getElementById('zipSection').classList.remove('hidden');
    
    // For new companies, show empty blog posts section
    displayBlogPosts([], 0);
    
    addDebugLog('Company display and ZIP section shown');
}

function displayBlogPosts(posts, totalCount) {
    addDebugLog(`Displaying ${posts ? posts.length : 0} blog posts out of ${totalCount} total`);
    
    let html = '';
    
    if (!posts || posts.length === 0) {
        html = '<p>No blog posts generated yet. Use the ZIP code search below to create your first post!</p>';
    } else {
        html += `<p><strong>${totalCount}</strong> total posts (showing latest 10):</p>`;
        html += '<div class="blog-posts-container">';
        
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
                    <div class="blog-post-title">
                        <a href="api/generate_post.php?company_id=${selectedCompanyId}&zip=${encodeURIComponent(post.zip_code)}&keyword=${encodeURIComponent(post.keyword)}" target="_blank">
                            ${escapeHtml(post.title || post.keyword)}
                        </a>
                    </div>
                    <div class="blog-post-meta">
                        📅 ${formattedDate} • 📍 ZIP ${post.zip_code} • 📝 ${post.word_count} words
                    </div>
                    <div class="blog-post-preview">
                        ${escapeHtml(post.content_preview)}...
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        if (totalCount > 10) {
            html += `<p><a href="admin.php?company_id=${selectedCompanyId}" target="_blank">View all ${totalCount} posts in admin panel →</a></p>`;
        }
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
    
    document.getElementById('companySelection').classList.add('hidden');
    document.getElementById('existingCompanies').classList.add('hidden');
    document.getElementById('newCompanyForm').classList.add('hidden');
    
    document.getElementById('selectedCompanyInfo').innerHTML = `
        <strong>${escapeHtml(selectedCompanyData.company_name)}</strong><br>
        📍 ${escapeHtml(selectedCompanyData.location)}<br>
        🏷️ ${escapeHtml(selectedCompanyData.company_type)}
    `;
    
    document.getElementById('selectedCompanyDisplay').classList.remove('hidden');
    document.getElementById('zipSection').classList.remove('hidden');
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

    document.getElementById("results").innerHTML = "<p>Loading...</p>";

    fetch("api/get_keywords.php?zip=" + zip)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById("results").innerHTML = "<p>Error: " + data.error + "</p>";
                return;
            }

            let html = `
                <h2>Results for ZIP ${data.zip}</h2>
                <p><strong>${data.keyword_count}</strong> HVAC search terms found</p>
            `;

            // -------------------------------
            // TOP 10 RANKED KEYWORDS
            // -------------------------------
            html += `<div class="section"><h2>🔥 Top 10 Most Popular Searches</h2>`;

            const top10 = data.ranked_keywords.slice(0, 10);
            top10.forEach(row => {
                const encoded = encodeURIComponent(row.keyword);
                html += `
                    <div class="rank-box">
                        <a href="api/generate_post.php?company_id=${selectedCompanyId}&zip=${data.zip}&keyword=${encoded}" target="_blank">
                            ${row.keyword}
                        </a>
                        <span class="rank-score">Score: ${row.score}</span>
                    </div>
                `;
            });

            html += `</div>`;

            // -------------------------------
            // CATEGORY SECTIONS
            // -------------------------------
            const categories = data.categories;

            const categoryNames = {
                cooling_issues: "❄️ Cooling Issues",
                heating_issues: "🔥 Heating Issues",
                heat_pump: "♨️ Heat Pump Problems",
                noise_smell: "👃 Noises & Smells",
                leaks_water: "💧 Leaks & Water Problems",
                repair_intent: "🔧 Repair/Service Intent",
                troubleshooting: "🛠️ Troubleshooting Searches"
            };

            html += `<div class="section"><h2>📂 Keyword Categories</h2>`;

            for (const key in categories) {
                const items = categories[key];
                if (items.length === 0) continue;

                html += `<h3>${categoryNames[key]}</h3>`;

                items.forEach(kw => {
                    const encoded = encodeURIComponent(kw);
                    html += `
                        <div class="keyword-box">
                            <a href="api/generate_post.php?company_id=${selectedCompanyId}&zip=${data.zip}&keyword=${encoded}" target="_blank">
                                ${kw}
                            </a>
                        </div>
                    `;
                });
            }

            html += `</div>`;

            document.getElementById("results").innerHTML = html;

        })
        .catch(err => {
            document.getElementById("results").innerHTML = "<p>Error fetching data.</p>";
        });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    addDebugLog('Page loaded, initializing...');
    // Start with company selection
    showCompanySelection();
    addDebugLog('Company selection shown');
});
</script>

</body>
</html>
