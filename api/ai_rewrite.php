<?php
/**
 * AI Rewrite API
 * Handles content rewriting using Claude and ChatGPT APIs
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'No input data']);
        exit;
    }
    
    try {
        $action = $input['action'] ?? '';
        
        if ($action === 'ai_rewrite') {
            handleAiRewrite($input);
        } elseif ($action === 'get_presets') {
            echo json_encode(['presets' => getRewritePrompts()]);
        } else {
            echo json_encode(['error' => 'Unknown action']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['error' => 'Only POST method allowed']);
}

/**
 * Get predefined rewrite prompts
 */
function getRewritePrompts() {
    return [
        'improve' => 'Improve the writing quality, grammar, and flow of this text while maintaining its core message and tone.',
        'professional' => 'Rewrite this text in a professional, business-appropriate tone suitable for HVAC industry communications.',
        'casual' => 'Rewrite this text in a casual, friendly tone that feels approachable and conversational.',
        'technical' => 'Rewrite this text with more technical detail and industry-specific terminology for HVAC professionals.',
        'simplify' => 'Simplify this text to make it easier to understand for general homeowners and property managers.',
        'seo' => 'Rewrite this text to be more SEO-friendly while maintaining readability and including relevant HVAC keywords.',
        'expand' => 'Expand this text with more detail, examples, and comprehensive information while maintaining accuracy.',
        'shorten' => 'Make this text more concise and to-the-point while preserving all essential information.',
        'engaging' => 'Rewrite this text to be more engaging, interesting, and compelling for readers.'
    ];
}

/**
 * Handle AI rewrite request
 */
function handleAiRewrite($input) {
    $text = $input['text'] ?? '';
    $prompt = $input['prompt'] ?? '';
    $mode = $input['mode'] ?? 'full';
    $post_id = $input['post_id'] ?? 0;
    $preset = $input['preset'] ?? '';
    
    // Use preset prompt if provided
    if (!empty($preset)) {
        $presets = getRewritePrompts();
        if (isset($presets[$preset])) {
            $prompt = $presets[$preset];
        }
    }
    
    if (empty($text) || empty($prompt)) {
        echo json_encode(['error' => 'Text and prompt are required']);
        return;
    }
    
    // Try Claude first, fallback to ChatGPT if needed
    $result = tryClaudeRewrite($text, $prompt);
    
    if (!$result['success']) {
        // Fallback to ChatGPT (if you have API key)
        $result = tryChatGPTRewrite($text, $prompt);
    }
    
    if ($result['success']) {
        // Log the rewrite for tracking
        logAiRewrite($post_id, $mode, $text, $result['rewritten'], $result['provider']);
        
        echo json_encode([
            'success' => true,
            'original' => $text,
            'rewritten' => $result['rewritten'],
            'provider' => $result['provider'],
            'mode' => $mode
        ]);
    } else {
        echo json_encode(['error' => $result['error']]);
    }
}

/**
 * Try rewriting with Claude API
 */
function tryClaudeRewrite($text, $prompt) {
    global $CLAUDE_API_KEY;
    
    if (empty($CLAUDE_API_KEY)) {
        return ['success' => false, 'error' => 'Claude API key not configured'];
    }
    
    $systemPrompt = "You are an expert content writer specializing in HVAC industry content. You help rewrite and improve content for HVAC businesses. Always maintain accuracy and professionalism while following the user's specific instructions.";
    
    $userMessage = $prompt . "\n\nText to rewrite:\n" . $text;
    
    $data = [
        'model' => 'claude-3-haiku-20240307',
        'max_tokens' => 4000,
        'system' => $systemPrompt,
        'messages' => [
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ]
    ];
    
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Curl error: ' . $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Claude API error: HTTP ' . $httpCode];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['content'][0]['text'])) {
        return [
            'success' => true,
            'rewritten' => trim($result['content'][0]['text']),
            'provider' => 'Claude'
        ];
    } else {
        return ['success' => false, 'error' => 'Invalid response from Claude API'];
    }
}

/**
 * Try rewriting with ChatGPT API (fallback)
 */
function tryChatGPTRewrite($text, $prompt) {
    // You can add your OpenAI API key here if you have one
    $openai_api_key = ''; // Add your OpenAI API key here
    
    if (empty($openai_api_key)) {
        return ['success' => false, 'error' => 'No AI APIs available'];
    }
    
    $systemPrompt = "You are an expert content writer specializing in HVAC industry content. You help rewrite and improve content for HVAC businesses. Always maintain accuracy and professionalism while following the user's specific instructions.";
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $prompt . "\n\nText to rewrite:\n" . $text
            ]
        ],
        'max_tokens' => 4000,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_api_key
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Curl error: ' . $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'OpenAI API error: HTTP ' . $httpCode];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return [
            'success' => true,
            'rewritten' => trim($result['choices'][0]['message']['content']),
            'provider' => 'ChatGPT'
        ];
    } else {
        return ['success' => false, 'error' => 'Invalid response from OpenAI API'];
    }
}

/**
 * Log AI rewrite activity
 */
function logAiRewrite($post_id, $mode, $original_text, $rewritten_text, $provider) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ai_rewrite_log (
                post_id, 
                mode, 
                original_length, 
                rewritten_length, 
                provider, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $post_id,
            $mode,
            strlen($original_text),
            strlen($rewritten_text),
            $provider
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the rewrite
        error_log("AI rewrite log error: " . $e->getMessage());
    }
}

/**
 * Create AI rewrite log table if it doesn't exist
 */
function createAiRewriteLogTable($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ai_rewrite_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NULL,
                mode ENUM('full', 'selection') NOT NULL,
                original_length INT NOT NULL,
                rewritten_length INT NOT NULL,
                provider VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE SET NULL,
                INDEX idx_post_id (post_id),
                INDEX idx_provider (provider),
                INDEX idx_created_at (created_at)
            )
        ");
    } catch (Exception $e) {
        // Table creation failed, but continue
        error_log("AI rewrite table creation error: " . $e->getMessage());
    }
}

// Create the log table on first use
createAiRewriteLogTable($pdo);
?>