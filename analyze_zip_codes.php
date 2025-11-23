<?php
require_once __DIR__ . "/config.php";

header('Content-Type: application/json');

try {
    // Get climate zone distribution
    $stmt = $pdo->query("
        SELECT 
            climate_zone, 
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM zip_codes), 1) as percentage
        FROM zip_codes 
        WHERE climate_zone IS NOT NULL
        GROUP BY climate_zone 
        ORDER BY count DESC
    ");
    $climateDistribution = $stmt->fetchAll();
    
    // Get state distribution (top 10)
    $stmt = $pdo->query("
        SELECT 
            state_code, 
            state,
            COUNT(*) as count
        FROM zip_codes 
        GROUP BY state_code, state
        ORDER BY count DESC 
        LIMIT 10
    ");
    $stateDistribution = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM zip_codes");
    $totalCount = $stmt->fetch()['total'];
    
    // Get some random samples
    $stmt = $pdo->query("
        SELECT zip_code, city, state_code, climate_zone, suggested_ip
        FROM zip_codes 
        ORDER BY RAND() 
        LIMIT 10
    ");
    $samples = $stmt->fetchAll();

    echo json_encode([
        'total_zip_codes' => $totalCount,
        'climate_distribution' => $climateDistribution,
        'top_states' => $stateDistribution,
        'random_samples' => $samples,
        'status' => 'success'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
}
?>