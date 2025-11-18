<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../config.php";

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Create new company
    $input = json_decode(file_get_contents('php://input'), true);
    
    $company_name = trim($input['company_name'] ?? '');
    $location = trim($input['location'] ?? '');
    $hours = trim($input['hours'] ?? '');
    $company_type = trim($input['company_type'] ?? '');
    
    // Validate required fields
    if (empty($company_name) || empty($location) || empty($hours) || empty($company_type)) {
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }
    
    // Validate company type
    $valid_types = ['HVAC', 'Plumbing', 'Electric', 'Commercial HVAC'];
    if (!in_array($company_type, $valid_types)) {
        echo json_encode(['error' => 'Invalid company type']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO companies (company_name, location, hours, company_type) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$company_name, $location, $hours, $company_type]);
        
        $company_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'company_id' => $company_id,
            'message' => 'Company created successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    
} elseif ($method === 'GET') {
    // Get all companies or specific company
    
    $company_id = $_GET['id'] ?? null;
    
    try {
        if ($company_id) {
            // Get specific company
            $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            $company = $stmt->fetch();
            
            if (!$company) {
                echo json_encode(['error' => 'Company not found']);
                exit;
            }
            
            // Get blog posts for this company
            $posts_stmt = $pdo->prepare("
                SELECT id, zip_code, keyword, title, word_count, generated_at,
                       LEFT(content, 150) as content_preview
                FROM blog_posts 
                WHERE company_id = ? 
                ORDER BY generated_at DESC 
                LIMIT 10
            ");
            $posts_stmt->execute([$company_id]);
            $blog_posts = $posts_stmt->fetchAll();
            
            // Get post count
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as post_count FROM blog_posts WHERE company_id = ?");
            $count_stmt->execute([$company_id]);
            $post_count = $count_stmt->fetch()['post_count'];
            
            echo json_encode([
                'company' => $company,
                'blog_posts' => $blog_posts,
                'post_count' => $post_count
            ]);
            
        } else {
            // Get all companies with post counts
            $stmt = $pdo->query("
                SELECT c.id, c.company_name, c.location, c.company_type, c.created_at,
                       COUNT(bp.id) as post_count
                FROM companies c 
                LEFT JOIN blog_posts bp ON c.id = bp.company_id
                GROUP BY c.id, c.company_name, c.location, c.company_type, c.created_at
                ORDER BY c.created_at DESC
            ");
            
            $companies = $stmt->fetchAll();
            
            echo json_encode(['companies' => $companies]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['error' => 'Method not allowed']);
}