<?php
require_once __DIR__ . "/config.php";

/**
 * DATABASE SCHEMA ENHANCEMENT
 * Adds columns for climate and market intelligence data
 */

header('Content-Type: application/json');

try {
    // Add new columns for enhanced ZIP code data
    $alterQueries = [
        // Climate data columns
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS heating_degree_days INT DEFAULT NULL COMMENT 'Annual heating degree days'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS cooling_degree_days INT DEFAULT NULL COMMENT 'Annual cooling degree days'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS avg_winter_temp DECIMAL(5,2) DEFAULT NULL COMMENT 'Average winter temperature'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS avg_summer_temp DECIMAL(5,2) DEFAULT NULL COMMENT 'Average summer temperature'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS humidity_index INT DEFAULT NULL COMMENT 'Average humidity percentage'",
        
        // Market intelligence columns  
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS total_housing_units INT DEFAULT NULL COMMENT 'Total housing units from Census'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS occupied_housing_units INT DEFAULT NULL COMMENT 'Occupied housing units'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS median_rent DECIMAL(8,2) DEFAULT NULL COMMENT 'Median rent from Census'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS median_year_built INT DEFAULT NULL COMMENT 'Median year built for housing'",
        
        // Energy market data
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS avg_electric_rate DECIMAL(6,4) DEFAULT NULL COMMENT 'Average residential electric rate per kWh'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS avg_gas_rate DECIMAL(6,2) DEFAULT NULL COMMENT 'Average natural gas rate per therm'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS primary_heating_fuel VARCHAR(50) DEFAULT NULL COMMENT 'Primary heating fuel type'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS energy_efficiency_programs VARCHAR(20) DEFAULT 'Medium' COMMENT 'State efficiency program level'",
        
        // Enhanced HVAC targeting
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS hvac_market_score INT DEFAULT 50 COMMENT 'Comprehensive HVAC market opportunity score 1-100'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS climate_severity_index INT DEFAULT 50 COMMENT 'Climate extremes requiring HVAC'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS market_maturity ENUM('Emerging','Growing','Mature','Saturated') DEFAULT 'Growing' COMMENT 'Market development stage'",
        
        // Data tracking
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS data_enriched_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When enrichment data was last updated'",
        "ALTER TABLE zip_codes ADD COLUMN IF NOT EXISTS data_sources TEXT DEFAULT NULL COMMENT 'JSON of data sources used'",
    ];
    
    $results = [];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            $results[] = [
                'query' => substr($query, 0, 100) . '...',
                'status' => 'success'
            ];
        } catch (Exception $e) {
            $results[] = [
                'query' => substr($query, 0, 100) . '...',
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Add indexes for performance
    $indexQueries = [
        "CREATE INDEX IF NOT EXISTS idx_heating_degree_days ON zip_codes(heating_degree_days)",
        "CREATE INDEX IF NOT EXISTS idx_cooling_degree_days ON zip_codes(cooling_degree_days)",  
        "CREATE INDEX IF NOT EXISTS idx_hvac_market_score ON zip_codes(hvac_market_score)",
        "CREATE INDEX IF NOT EXISTS idx_median_income_enhanced ON zip_codes(median_income)",
        "CREATE INDEX IF NOT EXISTS idx_climate_severity ON zip_codes(climate_severity_index)"
    ];
    
    foreach ($indexQueries as $query) {
        try {
            $pdo->exec($query);
            $results[] = [
                'query' => $query,
                'status' => 'index_created'
            ];
        } catch (Exception $e) {
            // Indexes might already exist, ignore errors
        }
    }
    
    // Get updated table structure
    $stmt = $pdo->query("DESCRIBE zip_codes");
    $tableStructure = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database schema enhanced for climate and market intelligence',
        'schema_changes' => $results,
        'total_columns' => count($tableStructure),
        'new_capabilities' => [
            'Climate Intelligence' => [
                'Heating/cooling degree days',
                'Temperature averages',
                'Humidity tracking',
                'Climate severity scoring'
            ],
            'Market Intelligence' => [
                'Housing demographics',
                'Energy market data', 
                'HVAC opportunity scoring',
                'Market maturity assessment'
            ],
            'API Integration Ready' => [
                'NOAA Climate Data',
                'US Census Demographics',
                'EIA Energy Data',
                'OpenWeatherMap'
            ]
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>