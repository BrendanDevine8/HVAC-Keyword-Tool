<?php
// Quick Database Analysis - Check ZIP Code Enrichment Status

require_once('config.php');

echo "=== HVAC Tool Database Analysis ===\n\n";

// Check total ZIP codes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM zip_codes");
$total = $stmt->fetch()['total'];
echo "📊 **Total ZIP Codes**: " . number_format($total) . "\n\n";

// Check climate zone distribution
echo "🌡️ **Climate Zone Distribution**:\n";
$stmt = $pdo->query("
    SELECT climate_zone, COUNT(*) as count 
    FROM zip_codes 
    WHERE climate_zone IS NOT NULL 
    GROUP BY climate_zone 
    ORDER BY climate_zone
");

while($row = $stmt->fetch()) {
    $percentage = round(($row['count'] / $total) * 100, 1);
    echo "   - Zone {$row['climate_zone']}: " . number_format($row['count']) . " ({$percentage}%)\n";
}

// Check which new columns need enrichment
echo "\n📋 **Enrichment Status**:\n";

$columns_to_check = [
    'heating_degree_days' => 'Heating Degree Days',
    'cooling_degree_days' => 'Cooling Degree Days', 
    'humidity_index' => 'Humidity Index',
    'median_income' => 'Median Income',
    'population_density' => 'Population Density',
    'housing_median_age' => 'Housing Age',
    'electricity_rate' => 'Electricity Rates',
    'hvac_opportunity_score' => 'HVAC Opportunity Score'
];

foreach($columns_to_check as $column => $label) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as populated 
        FROM zip_codes 
        WHERE $column IS NOT NULL AND $column > 0
    ");
    $stmt->execute();
    $populated = $stmt->fetch()['populated'];
    $percentage = round(($populated / $total) * 100, 1);
    
    $status = $percentage > 50 ? "✅" : ($percentage > 0 ? "⚠️" : "❌");
    echo "   $status **$label**: " . number_format($populated) . " / " . number_format($total) . " ($percentage%)\n";
}

// Show sample ZIP codes that need enrichment
echo "\n🎯 **Sample ZIP Codes Ready for Enrichment**:\n";
$stmt = $pdo->query("
    SELECT zip_code, city, state, climate_zone
    FROM zip_codes 
    WHERE heating_degree_days IS NULL 
    ORDER BY RAND() 
    LIMIT 10
");

while($row = $stmt->fetch()) {
    echo "   - {$row['zip_code']} ({$row['city']}, {$row['state']}) - Zone {$row['climate_zone']}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📝 **Next Steps**:\n";
echo "1. Get API keys from the integration guide\n";
echo "2. Test with 10-20 ZIP codes first\n";
echo "3. Run full enrichment on all " . number_format($total) . " ZIP codes\n";
echo "4. Enhanced climate targeting will dramatically improve keyword relevance!\n";
?>