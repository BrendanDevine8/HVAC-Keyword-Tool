<?php
require_once 'config.php';

echo "<h1>Import Extended ZIP Codes</h1>\n";

try {
    // Read and execute the extended ZIP codes SQL
    $sql = file_get_contents('extended_zip_codes.sql');
    
    // Remove the USE statement since we're already connected to the right database
    $sql = preg_replace('/USE\s+hvac_keywords;\s*/', '', $sql);
    
    // Execute the SQL
    echo "<h2>Importing Extended ZIP Codes...</h2>\n";
    $pdo->exec($sql);
    echo "<p>✅ Extended ZIP codes imported successfully!</p>\n";
    
    // Test New Orleans ZIP codes
    echo "<h2>Testing New Orleans ZIP Codes:</h2>\n";
    $stmt = $pdo->prepare("SELECT zip_code, city, state_code, climate_zone FROM zip_codes WHERE state_code = 'LA' OR city LIKE '%New Orleans%'");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    if (empty($results)) {
        echo "<p>❌ No Louisiana/New Orleans ZIP codes found after import</p>\n";
    } else {
        foreach ($results as $row) {
            echo "<p>✅ {$row['zip_code']} - {$row['city']}, {$row['state_code']} - Climate: <strong>{$row['climate_zone']}</strong></p>\n";
        }
    }
    
    echo "<h2>Quick Keyword Test for New Orleans (70112):</h2>\n";
    echo "<p><a href='api/get_keywords.php?zip=70112' target='_blank'>Test ZIP 70112 Keywords</a></p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>