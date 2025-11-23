<?php
require_once __DIR__ . "/config.php";

header('Content-Type: application/json');

try {
    // Check current ZIP code count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM zip_codes");
    $count = $stmt->fetch()['total'];
    
    // Get some sample ZIP codes
    $stmt = $pdo->query("SELECT zip_code, city, state, climate_zone FROM zip_codes LIMIT 10");
    $samples = $stmt->fetchAll();
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE zip_codes");
    $structure = $stmt->fetchAll();
    
    echo json_encode([
        'current_count' => $count,
        'sample_zip_codes' => $samples,
        'table_structure' => $structure,
        'status' => 'success'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
}
?>