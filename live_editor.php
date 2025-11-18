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
    <title>Live Editor - <?= htmlspecialchars($post['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        
        .editor-toolbar {
            background: #2c3e50;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .editor-title {
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .editor-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; }
        
        .editor-container {
            display: flex;
            height: calc(100vh - 60px);
        }
        
        .editor-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #bdc3c7;
        }
        
        .preview-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ecf0f1;
        }
        
        .panel-header {
            background: #34495e;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .editor-textarea {
            flex: 1;
            border: none;
            padding: 20px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.6;
            resize: none;
            outline: none;
            background: #fff;
        }
        
        .preview-content {
            flex: 1;
            padding: 20px;
            background: white;
            margin: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .status-bar {
            background: #34495e;
            color: white;
            padding-bottom: 15px;
            padding-left: 15px;
            padding-right: 15px;
            font-size: 0.85em;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;

        }
        
        .auto-save-indicator {
            color: #27ae60;
            font-weight: bold;
        }
        
        .auto-save-indicator.saving {
            color: #f39c12;
        }
        
        .auto-save-indicator.error {
            color: #e74c3c;
        }
        
        .format-toolbar {
            background: #ecf0f1;
            padding: 8px 15px;
            border-bottom: 1px solid #bdc3c7;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .format-btn {
            padding: 4px 8px;
            border: 1px solid #bdc3c7;
            background: white;
            cursor: pointer;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        .format-btn:hover {
            background: #d5dbdb;
        }
        
        .insert-menu {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 4px;
            z-index: 1000;
            top: 100%;
            left: 0;
        }
        
        .dropdown-content a {
            color: #2c3e50;
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .dropdown-content a:hover {
            background: #ecf0f1;
        }
        
        .dropdown-content.show { 
            display: block !important; 
        }
        
        .word-count {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        
        .ai-btn {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            font-weight: bold;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .ai-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .ai-processing {
            background: linear-gradient(45deg, #ffa726 0%, #ff7043 100%);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .ai-result-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 90vw;
            max-height: 80vh;
            z-index: 2000;
            display: none;
            /* Ensure header, scrollable body, and footer stay visible */
            display: none;
        }
        
        .ai-result-header {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ai-result-content {
            padding: 20px;
            max-height: 55vh;
            overflow-y: auto;
        }
        
        .ai-result-actions {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .ai-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 10px 0;
        }
        
        .ai-comparison-side {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .ai-comparison-header {
            background: #f8f9fa;
            padding: 10px 15px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        
        .ai-comparison-content {
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .version-info {
            background: rgba(52, 152, 219, 0.1);
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        
        .resize-handle {
            width: 5px;
            background: #bdc3c7;
            cursor: ew-resize;
            flex-shrink: 0;
            transition: background-color 0.3s;
        }
        
        .resize-handle:hover {
            background: #95a5a6;
        }
    </style>
</head>
<body>

<div class="editor-toolbar">
    <div class="editor-title">
        📝 Live Editor - <?= htmlspecialchars($post['title']) ?>
    </div>
    <div class="editor-actions">
        <button onclick="saveContent()" class="btn btn-success">💾 Save</button>
        <button onclick="saveAsVersion()" class="btn btn-primary">📋 Save as New Version</button>
        <button onclick="previewInNewTab()" class="btn btn-secondary">👁️ Preview in New Tab</button>
        <button onclick="viewVersionHistory()" class="btn btn-warning">🕐 Version History</button>
        <button onclick="closeEditor()" class="btn btn-secondary">✖️ Close</button>
    </div>
</div>

<div class="editor-container">
    <!-- Editor Panel -->
    <div class="editor-panel" id="editorPanel">
        <div class="panel-header">
            <span>📝 HTML Editor</span>
            <div>
                <button onclick="toggleWordWrap()" class="btn btn-secondary" style="padding: 3px 8px; font-size: 0.8em;">Word Wrap</button>
                <button onclick="toggleFullscreen('editor')" class="btn btn-secondary" style="padding: 3px 8px; font-size: 0.8em;">⛶ Fullscreen</button>
            </div>
        </div>
        
        <div class="format-toolbar">
            <button class="format-btn" onclick="insertHTML('<h1>', '</h1>')" title="Heading 1">H1</button>
            <button class="format-btn" onclick="insertHTML('<h2>', '</h2>')" title="Heading 2">H2</button>
            <button class="format-btn" onclick="insertHTML('<h3>', '</h3>')" title="Heading 3">H3</button>
            <button class="format-btn" onclick="insertHTML('<p>', '</p>')" title="Paragraph">P</button>
            <button class="format-btn" onclick="insertHTML('<strong>', '</strong>')" title="Bold">B</button>
            <button class="format-btn" onclick="insertHTML('<em>', '</em>')" title="Italic">I</button>
            <button class="format-btn" onclick="insertHTML('<br>')" title="Line Break">BR</button>
            <button class="format-btn" onclick="insertHTML('<a href=\"\">', '</a>')" title="Link">Link</button>
            
            <div class="insert-menu">
                <button class="format-btn" onclick="toggleDropdown('insertDropdown')">Insert ▼</button>
                <div id="insertDropdown" class="dropdown-content">
                    <a href="#" onclick="insertTemplate('cta'); return false;">Call-to-Action</a>
                    <a href="#" onclick="insertTemplate('contact'); return false;">Contact Info</a>
                    <a href="#" onclick="insertTemplate('service_area'); return false;">Service Area</a>
                    <a href="#" onclick="insertTemplate('emergency'); return false;">Emergency Service</a>
                    <a href="#" onclick="insertTemplate('review_prompt'); return false;">Review Prompt</a>
                    <a href="#" onclick="insertTemplate('social_links'); return false;">Social Links</a>
                </div>
            </div>
            
            <div class="insert-menu">
                <button class="format-btn ai-btn" onclick="toggleDropdown('aiDropdown')" style="background: linear-gradient(45deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;">🤖 AI Rewrite ▼</button>
                <div id="aiDropdown" class="dropdown-content">
                    <a href="#" onclick="aiRewrite('improve'); return false;">✨ Improve Writing</a>
                    <a href="#" onclick="aiRewrite('professional'); return false;">🎯 Make Professional</a>
                    <a href="#" onclick="aiRewrite('casual'); return false;">😊 Make Casual</a>
                    <a href="#" onclick="aiRewrite('technical'); return false;">🔧 More Technical</a>
                    <a href="#" onclick="aiRewrite('simple'); return false;">📝 Simplify</a>
                    <a href="#" onclick="aiRewrite('persuasive'); return false;">💪 More Persuasive</a>
                    <a href="#" onclick="aiRewrite('seo'); return false;">🔍 SEO Optimize</a>
                    <a href="#" onclick="aiRewrite('expand'); return false;">📈 Expand Content</a>
                    <a href="#" onclick="aiRewrite('shorten'); return false;">✂️ Shorten</a>
                    <hr style="margin: 5px 0;">
                    <a href="#" onclick="aiRewrite('custom'); return false;">💭 Custom Prompt</a>
                </div>
            </div>
        </div>
        
        <?php if (isset($post['current_version']) && $post['current_version'] > 1): ?>
        <div class="version-info">
            <strong>Current Version:</strong> <?= $post['current_version'] ?> 
            | <strong>Last Modified:</strong> <?= date('M j, Y g:i A', strtotime($post['last_modified_at'])) ?>
            <?php if ($post['last_modified_by'] !== 'system'): ?>
                by <?= htmlspecialchars($post['last_modified_by']) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <textarea 
            id="contentEditor" 
            class="editor-textarea" 
            placeholder="Enter your HTML content here..."
            spellcheck="false"
        ><?= htmlspecialchars($post['content']) ?></textarea>
        
        <div class="status-bar">
            <div>
                <span class="word-count" id="wordCount">0 words</span>
                <span style="margin-left: 10px;">Characters: <span id="charCount">0</span></span>
                <span style="margin-left: 10px;">Lines: <span id="lineCount">0</span></span>
            </div>
            <div class="auto-save-indicator" id="autoSaveStatus">Ready</div>
        </div>
    </div>
    
    <!-- Resize Handle -->
    <div class="resize-handle" id="resizeHandle"></div>
    
    <!-- Preview Panel -->
    <div class="preview-panel" id="previewPanel">
        <div class="panel-header">
            <span>👁️ Live Preview</span>
            <div>
                <button onclick="refreshPreview()" class="btn btn-secondary" style="padding: 3px 8px; font-size: 0.8em;">🔄 Refresh</button>
                <button onclick="toggleFullscreen('preview')" class="btn btn-secondary" style="padding: 3px 8px; font-size: 0.8em;">⛶ Fullscreen</button>
            </div>
        </div>
        <div class="preview-content" id="previewContent">
            <?= $post['content'] ?>
        </div>
    </div>
</div>

<!-- AI Result Modal -->
<div id="aiResultModal" class="ai-result-modal">
    <div class="ai-result-header">
        <h3 id="aiResultTitle">🤖 AI Rewrite Results</h3>
        <button onclick="closeAiModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
    </div>
    <div class="ai-result-content">
        <div id="aiResultContent">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
    <div class="ai-result-actions">
        <button onclick="closeAiModal()" class="btn btn-secondary">Cancel</button>
        <button onclick="applyAiResult()" class="btn btn-success" id="applyAiBtn">Apply Changes</button>
    </div>
</div>

<script>
const postId = <?= $post_id ?>;
const companyId = <?= $post['company_id'] ?>;
let autoSaveTimer = null;
let isResizing = false;
let currentAiResult = null;

// Initialize editor
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('contentEditor');
    
    if (!editor) {
        console.error('Content editor element not found!');
        return;
    }
    
    // Auto-update preview and stats
    editor.addEventListener('input', function() {
        updatePreview();
        updateStats();
        scheduleAutoSave();
    });
    
    // Initial stats update
    updateStats();
    
    // Initialize resize functionality
    initializeResize();
    
    // Auto-save every 30 seconds
    setInterval(function() {
        if (editor.value !== editor.defaultValue) {
            autoSave();
        }
    }, 30000);
    
    // Save on Ctrl+S
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveContent();
        }
    });
});

function updatePreview() {
    const content = document.getElementById('contentEditor').value;
    document.getElementById('previewContent').innerHTML = content;
}

function updateStats() {
    const content = document.getElementById('contentEditor').value;
    const text = content.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
    const words = text ? text.split(/\s+/).length : 0;
    const chars = content.length;
    const lines = content.split('\n').length;
    
    document.getElementById('wordCount').textContent = words + ' words';
    document.getElementById('charCount').textContent = chars;
    document.getElementById('lineCount').textContent = lines;
}

function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(autoSave, 3000); // Auto-save after 3 seconds of inactivity
}

function autoSave() {
    const status = document.getElementById('autoSaveStatus');
    status.textContent = 'Saving...';
    status.className = 'auto-save-indicator saving';
    
    const content = document.getElementById('contentEditor').value;
    
    fetch(`api/revisions.php?post_id=${postId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            post_id: postId,
            action: 'create',
            content: content,
            comment: 'Auto-save from live editor'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            status.textContent = 'Auto-saved ' + new Date().toLocaleTimeString();
            status.className = 'auto-save-indicator';
            document.getElementById('contentEditor').defaultValue = content;
        } else {
            throw new Error(data.error || 'Auto-save failed');
        }
    })
    .catch(error => {
        status.textContent = 'Auto-save failed';
        status.className = 'auto-save-indicator error';
        console.error('Auto-save error:', error);
    });
}

function saveContent() {
    const content = document.getElementById('contentEditor').value;
    
    // Update the main blog post
    fetch('api/generate_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update_post',
            post_id: postId,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Content saved successfully!');
            document.getElementById('contentEditor').defaultValue = content;
            
            // Also create a revision
            return fetch('api/revisions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    post_id: postId,
                    action: 'create',
                    content: content,
                    comment: 'Manual save from live editor'
                })
            });
        } else {
            throw new Error(data.error || 'Save failed');
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Revision creation failed:', data.error);
        }
    })
    .catch(error => {
        alert('Error saving content: ' + error.message);
    });
}

function saveAsVersion() {
    const comment = prompt('Enter a comment for this version:', 'Manual save via live editor');
    if (comment === null) return;
    
    const content = document.getElementById('contentEditor').value;
    
    fetch(`api/revisions.php?post_id=${postId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            post_id: postId,
            action: 'create',
            content: content,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('New version created successfully!');
            document.getElementById('contentEditor').defaultValue = content;
        } else {
            alert('Error creating version: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error creating version: ' + error.message);
    });
}

function insertHTML(openTag, closeTag = '') {
    const editor = document.getElementById('contentEditor');
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    const selectedText = editor.value.substring(start, end);
    
    const newText = openTag + selectedText + closeTag;
    
    editor.value = editor.value.substring(0, start) + newText + editor.value.substring(end);
    
    // Set cursor position
    const newCursorPos = start + openTag.length + selectedText.length;
    editor.setSelectionRange(newCursorPos, newCursorPos);
    
    editor.focus();
    updatePreview();
    updateStats();
}

function insertTemplate(type) {
    let template = '';
    
    switch (type) {
        case 'cta':
            template = '<div style="background: #0073e6; color: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">\n    <h3>Need HVAC Service?</h3>\n    <p>Contact us today for professional, reliable service!</p>\n    <p><strong>Call: (555) 123-4567</strong></p>\n</div>';
            break;
        case 'contact':
            template = '<?=
                addslashes(
                    '<div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #0073e6; margin: 15px 0;">' .
                    '<h4>Contact ' . htmlspecialchars($post["company_name"] ?? "Your Company") . '</h4>' .
                    '<p>📞 Phone: (555) 123-4567<br>' .
                    '📧 Email: info@company.com<br>' .
                    '🕒 Hours: Mon-Fri 8AM-6PM</p>' .
                    '</div>'
                )
            ?>';
            break;
        // other cases unchanged...
    }
    
    insertHTML(template);
    toggleDropdown('insertDropdown');
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function toggleDropdown(dropdownId) {
    // Close all dropdowns first
    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
        if (dropdown.id !== dropdownId) {
            dropdown.classList.remove('show');
        }
    });
    
    // Toggle the requested dropdown
    const targetDropdown = document.getElementById(dropdownId);
    if (targetDropdown) {
        targetDropdown.classList.toggle("show");
    }
}

// AI Rewriting Functions
function aiRewrite(type) {
    const editor = document.getElementById('contentEditor');
    const selectedText = getSelectedText(editor);
    let textToRewrite = selectedText;
    
    if (!textToRewrite) {
        // If no selection, use entire content
        textToRewrite = editor.value;
        if (!textToRewrite.trim()) {
            alert('Please enter some content to rewrite, or select specific text.');
            return;
        }
    }
    
    let prompt = '';
    let title = '';
    
    switch (type) {
        case 'improve':
            prompt = 'Improve the writing quality, grammar, and flow of this text while maintaining the original meaning and tone:';
            title = '✨ Improving Writing Quality';
            break;
        case 'professional':
            prompt = 'Rewrite this text to be more professional and business-appropriate:';
            title = '🎯 Making More Professional';
            break;
        case 'casual':
            prompt = 'Rewrite this text to be more casual and conversational:';
            title = '😊 Making More Casual';
            break;
        case 'technical':
            prompt = 'Rewrite this text to be more technical and detailed, adding relevant HVAC industry terminology:';
            title = '🔧 Adding Technical Details';
            break;
        case 'simple':
            prompt = 'Simplify this text to make it easier to understand for homeowners:';
            title = '📝 Simplifying Content';
            break;
        case 'persuasive':
            prompt = 'Rewrite this text to be more persuasive and compelling for potential HVAC customers:';
            title = '💪 Making More Persuasive';
            break;
        case 'seo':
            prompt = 'Rewrite this text to be more SEO-friendly while maintaining readability. Include relevant HVAC keywords naturally:';
            title = '🔍 SEO Optimizing';
            break;
        case 'expand':
            prompt = 'Expand this text with more details, examples, and helpful information for HVAC customers:';
            title = '📈 Expanding Content';
            break;
        case 'shorten':
            prompt = 'Shorten this text while keeping the most important information:';
            title = '✂️ Shortening Content';
            break;
        case 'custom':
            const customPrompt = prompt('Enter your custom rewriting instruction:');
            if (!customPrompt) return;
            prompt = customPrompt;
            title = '💭 Custom AI Rewrite';
            break;
        default:
            return;
    }
    
    performAiRewrite(textToRewrite, prompt, title, selectedText ? 'selection' : 'full');
    toggleDropdown('aiDropdown'); // Close dropdown
}

function getSelectedText(editor) {
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    return editor.value.substring(start, end);
}

function performAiRewrite(text, prompt, title, mode) {
    // Show processing state
    const aiBtn = document.querySelector('.ai-btn');
    const originalText = aiBtn.innerHTML;
    aiBtn.innerHTML = '⏳ Processing...';
    aiBtn.classList.add('ai-processing');
    aiBtn.disabled = true;
    
    // Prepare the data
    const requestData = {
        action: 'ai_rewrite',
        text: text,
        prompt: prompt,
        mode: mode,
        post_id: postId
    };
    
    fetch('api/ai_rewrite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // For selected text, apply immediately (fast workflow)
            if (mode === 'selection') {
                currentAiResult = {
                    original: data.original,
                    rewritten: data.rewritten,
                    mode
                };
                applyAiResult();
            } else {
                // For full-post rewrites, show the comparison modal first
                showAiResult(data.original, data.rewritten, title, mode);
            }
        } else {
            throw new Error(data.error || 'AI rewrite failed');
        }
    })
    .catch(error => {
        console.error('AI rewrite error:', error);
        alert('Error with AI rewrite: ' + error.message);
    })
    .finally(() => {
        // Restore button state
        aiBtn.innerHTML = originalText;
        aiBtn.classList.remove('ai-processing');
        aiBtn.disabled = false;
    });
}

function showAiResult(original, rewritten, title, mode) {
    currentAiResult = { original, rewritten, mode };
    
    document.getElementById('aiResultTitle').textContent = title;
    
    const content = document.getElementById('aiResultContent');
    content.innerHTML = `
        <div class="ai-comparison">
            <div class="ai-comparison-side">
                <div class="ai-comparison-header">📝 Original</div>
                <div class="ai-comparison-content">${escapeHtml(original)}</div>
            </div>
            <div class="ai-comparison-side">
                <div class="ai-comparison-header">✨ AI Rewritten</div>
                <div class="ai-comparison-content">${escapeHtml(rewritten)}</div>
            </div>
        </div>
        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 0.9em;">
            <strong>📊 Changes:</strong><br>
            Original: ${original.length} characters, ${original.split(' ').length} words<br>
            Rewritten: ${rewritten.length} characters, ${rewritten.split(' ').length} words
        </div>
    `;
    
    document.getElementById('aiResultModal').style.display = 'block';
}

function closeAiModal() {
    document.getElementById('aiResultModal').style.display = 'none';
    currentAiResult = null;
}

function applyAiResult() {
    if (!currentAiResult) return;
    
    const editor = document.getElementById('contentEditor');
    
    if (currentAiResult.mode === 'selection') {
        // Replace selected text
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const before = editor.value.substring(0, start);
        const after = editor.value.substring(end);
        editor.value = before + currentAiResult.rewritten + after;
        
        // Set cursor position after the new text
        const newCursorPos = start + currentAiResult.rewritten.length;
        editor.setSelectionRange(newCursorPos, newCursorPos);
    } else {
        // Replace entire content
        editor.value = currentAiResult.rewritten;
    }
    
    // Update preview and stats
    updatePreview();
    updateStats();
    scheduleAutoSave();
    
    closeAiModal();
    editor.focus();
    
    // Show success message
    const status = document.getElementById('autoSaveStatus');
    status.textContent = '✨ AI rewrite applied!';
    status.className = 'auto-save-indicator';
}



function toggleWordWrap() {
    const editor = document.getElementById('contentEditor');
    if (editor.style.whiteSpace === 'nowrap') {
        editor.style.whiteSpace = 'pre-wrap';
    } else {
        editor.style.whiteSpace = 'nowrap';
    }
}

function refreshPreview() {
    updatePreview();
}

function previewInNewTab() {
    const content = document.getElementById('contentEditor').value;
    const newWindow = window.open('', '_blank');
    newWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Blog Post Preview</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; line-height: 1.6; }
                h1, h2, h3 { color: #333; }
                p { margin-bottom: 1em; }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);
    newWindow.document.close();
}

function viewVersionHistory() {
    window.open(`/admin.php?company_id=${companyId}#post-${postId}`, '_blank');
}

function closeEditor() {
    if (document.getElementById('contentEditor').value !== document.getElementById('contentEditor').defaultValue) {
        if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
            return;
        }
    }
    window.close();
    
    // If window.close() doesn't work (popup blockers), redirect back
    setTimeout(() => {
        window.location.href = `/admin.php?company_id=${companyId}`;
    }, 100);
}

function toggleFullscreen(panel) {
    const editorPanel = document.getElementById('editorPanel');
    const previewPanel = document.getElementById('previewPanel');
    const resizeHandle = document.getElementById('resizeHandle');
    
    if (panel === 'editor') {
        if (previewPanel.style.display === 'none') {
            // Restore split view
            editorPanel.style.flex = '1';
            previewPanel.style.display = 'flex';
            resizeHandle.style.display = 'block';
        } else {
            // Hide preview, show only editor
            previewPanel.style.display = 'none';
            resizeHandle.style.display = 'none';
            editorPanel.style.flex = '1';
        }
    } else if (panel === 'preview') {
        if (editorPanel.style.display === 'none') {
            // Restore split view
            editorPanel.style.display = 'flex';
            previewPanel.style.flex = '1';
            resizeHandle.style.display = 'block';
        } else {
            // Hide editor, show only preview
            editorPanel.style.display = 'none';
            resizeHandle.style.display = 'none';
            previewPanel.style.flex = '1';
        }
    }
}

function initializeResize() {
    const resizeHandle = document.getElementById('resizeHandle');
    const editorPanel = document.getElementById('editorPanel');
    const previewPanel = document.getElementById('previewPanel');
    
    resizeHandle.addEventListener('mousedown', function(e) {
        isResizing = true;
        document.addEventListener('mousemove', handleResize);
        document.addEventListener('mouseup', stopResize);
        e.preventDefault();
    });
    
    function handleResize(e) {
        if (!isResizing) return;
        
        const containerWidth = document.querySelector('.editor-container').offsetWidth;
        const newEditorWidth = (e.clientX / containerWidth) * 100;
        
        if (newEditorWidth > 20 && newEditorWidth < 80) {
            editorPanel.style.flex = `0 0 ${newEditorWidth}%`;
            previewPanel.style.flex = `0 0 ${100 - newEditorWidth}%`;
        }
    }
    
    function stopResize() {
        isResizing = false;
        document.removeEventListener('mousemove', handleResize);
        document.removeEventListener('mouseup', stopResize);
    }
}

// Close dropdown when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('.format-btn')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
    
    // Close AI modal when clicking outside
    const aiModal = document.getElementById('aiResultModal');
    if (event.target === aiModal) {
        closeAiModal();
    }
};
</script>

</body>
</html>