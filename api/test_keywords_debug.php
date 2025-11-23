<?php
require_once __DIR__ . "/../config.php";

header('Content-Type: application/json');

$zip = isset($_GET['zip']) ? trim($_GET['zip']) : '90210';

echo json_encode([
    'status' => 'testing',
    'zip' => $zip,
    'timestamp' => date('Y-m-d H:i:s'),
    'memory_usage' => memory_get_usage(),
    'database_test' => 'about to test...'
]);

// Test database connection
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM zip_codes WHERE zip_code = ?");
    $stmt->execute([$zip]);
    $count = $stmt->fetchColumn();
    
    echo json_encode([
        'database_success' => true,
        'zip_found' => $count > 0,
        'ready_for_api_test' => true
    ]);
} catch (Exception $e) {
    echo json_encode([
        'database_error' => $e->getMessage()
    ]);
}
?>