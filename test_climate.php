<?php
require_once 'config.php';

echo "<h1>Climate Zone Test</h1>\n";

// Test specific ZIP codes
$testZips = ['70112', '70115', '70130', '33101', '90210', '60601', '85001'];

echo "<h2>Testing ZIP Code Climate Data:</h2>\n";

try {
    $stmt = $pdo->prepare("SELECT zip_code, city, state_code, climate_zone FROM zip_codes WHERE zip_code = ?");
    
    foreach ($testZips as $zip) {
        $stmt->execute([$zip]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "<p><strong>{$zip}</strong>: {$result['city']}, {$result['state_code']} - Climate: <strong>{$result['climate_zone']}</strong></p>\n";
        } else {
            echo "<p><strong>{$zip}</strong>: Not found in database</p>\n";
        }
    }
    
    // Show all Louisiana ZIP codes
    echo "<h2>All Louisiana ZIP Codes:</h2>\n";
    $stmt = $pdo->prepare("SELECT zip_code, city, climate_zone FROM zip_codes WHERE state_code = 'LA' ORDER BY zip_code");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    if (empty($results)) {
        echo "<p>No Louisiana ZIP codes found</p>\n";
    } else {
        foreach ($results as $row) {
            echo "<p>{$row['zip_code']} - {$row['city']} - {$row['climate_zone']}</p>\n";
        }
    }
    
    // Show climate zone distribution
    echo "<h2>Climate Zone Distribution:</h2>\n";
    $stmt = $pdo->query("SELECT climate_zone, COUNT(*) as count FROM zip_codes GROUP BY climate_zone ORDER BY count DESC");
    $zones = $stmt->fetchAll();
    
    foreach ($zones as $zone) {
        echo "<p><strong>{$zone['climate_zone']}</strong>: {$zone['count']} ZIP codes</p>\n";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>