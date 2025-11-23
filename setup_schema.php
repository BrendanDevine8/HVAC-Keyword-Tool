<?php
require_once __DIR__ . "/config.php";

header('Content-Type: application/json');

// Simple schema enhancement - add columns one by one with error handling
$columns = [
    'heating_degree_days' => 'INT DEFAULT NULL',
    'cooling_degree_days' => 'INT DEFAULT NULL', 
    'humidity_index' => 'INT DEFAULT NULL',
    'median_income' => 'INT DEFAULT NULL',
    'population_density' => 'INT DEFAULT NULL',
    'housing_median_age' => 'INT DEFAULT NULL',
    'electricity_rate' => 'DECIMAL(6,4) DEFAULT NULL',
    'hvac_opportunity_score' => 'INT DEFAULT 50',
    'last_enriched' => 'TIMESTAMP NULL DEFAULT NULL'
];

$results = [];
$successCount = 0;

foreach ($columns as $columnName => $definition) {
    try {
        // Check if column exists first
        $checkStmt = $pdo->prepare("SHOW COLUMNS FROM zip_codes LIKE ?");
        $checkStmt->execute([$columnName]);
        
        if ($checkStmt->rowCount() == 0) {
            // Column doesn't exist, add it
            $sql = "ALTER TABLE zip_codes ADD COLUMN $columnName $definition";
            $pdo->exec($sql);
            $results[$columnName] = "Added successfully";
            $successCount++;
        } else {
            $results[$columnName] = "Already exists";
        }
    } catch (Exception $e) {
        $results[$columnName] = "Error: " . $e->getMessage();
    }
}

echo json_encode([
    'status' => 'success',
    'columns_processed' => count($columns),
    'new_columns_added' => $successCount,
    'details' => $results
], JSON_PRETTY_PRINT);
?>