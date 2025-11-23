<?php
/**
 * OPTIMIZED DATABASE QUERY SYSTEM
 * Reduces database calls and improves query performance
 */

class OptimizedLocationData {
    private static $locationCache = [];
    private static $batchSize = 100;
    
    /**
     * Get location data with intelligent caching
     */
    public static function getLocationData($zipCode) {
        global $pdo;
        
        // Check memory cache first
        if (isset(self::$locationCache[$zipCode])) {
            return self::$locationCache[$zipCode];
        }
        
        try {
            // Optimized single query with all needed data
            $stmt = $pdo->prepare("
                SELECT 
                    zip_code,
                    city,
                    state,
                    state_code,
                    metro_area,
                    county,
                    heating_degree_days,
                    cooling_degree_days,
                    humidity_index,
                    climate_zone,
                    hvac_opportunity_score,
                    latitude,
                    longitude,
                    population,
                    median_income
                FROM zip_codes 
                WHERE zip_code = ? 
                LIMIT 1
            ");
            
            $stmt->execute([$zipCode]);
            $locationData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($locationData) {
                // Enhance with computed climate data
                $locationData = self::enhanceClimateData($locationData);
                
                // Cache the result
                self::$locationCache[$zipCode] = $locationData;
                
                return $locationData;
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Database error in getLocationData: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Batch load multiple ZIP codes efficiently
     */
    public static function batchLoadZipCodes($zipCodes) {
        global $pdo;
        
        $uncachedZips = array_diff($zipCodes, array_keys(self::$locationCache));
        
        if (empty($uncachedZips)) {
            return; // All data already cached
        }
        
        try {
            $placeholders = str_repeat('?,', count($uncachedZips) - 1) . '?';
            
            $stmt = $pdo->prepare("
                SELECT 
                    zip_code,
                    city,
                    state,
                    state_code,
                    metro_area,
                    county,
                    heating_degree_days,
                    cooling_degree_days,
                    humidity_index,
                    climate_zone,
                    hvac_opportunity_score,
                    latitude,
                    longitude,
                    population,
                    median_income
                FROM zip_codes 
                WHERE zip_code IN ($placeholders)
            ");
            
            $stmt->execute(array_values($uncachedZips));
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $enhanced = self::enhanceClimateData($row);
                self::$locationCache[$row['zip_code']] = $enhanced;
            }
            
        } catch (PDOException $e) {
            error_log("Database error in batchLoadZipCodes: " . $e->getMessage());
        }
    }
    
    /**
     * Enhance location data with computed climate metrics
     */
    private static function enhanceClimateData($locationData) {
        $heatingDays = (float)($locationData['heating_degree_days'] ?? 0);
        $coolingDays = (float)($locationData['cooling_degree_days'] ?? 0);
        $humidity = (float)($locationData['humidity_index'] ?? 50);
        
        // Determine precise climate zone if not set
        if (empty($locationData['climate_zone'])) {
            $locationData['climate_zone'] = self::determineClimateZone($heatingDays, $coolingDays, $humidity);
        }
        
        // Calculate HVAC priorities
        $locationData['cooling_priority'] = self::calculateCoolingPriority($coolingDays, $humidity);
        $locationData['heating_priority'] = self::calculateHeatingPriority($heatingDays);
        $locationData['current_season'] = self::getCurrentSeason();
        
        // Calculate opportunity scores
        $locationData['market_opportunity'] = self::calculateMarketOpportunity($locationData);
        
        return $locationData;
    }
    
    /**
     * Determine climate zone based on degree days and humidity
     */
    private static function determineClimateZone($heatingDays, $coolingDays, $humidity) {
        // Very Cold: >7000 heating degree days
        if ($heatingDays > 7000) {
            return 'Very Cold';
        }
        
        // Cold: 4000-7000 heating degree days
        if ($heatingDays > 4000) {
            return 'Cold';
        }
        
        // Very Hot: >3500 cooling degree days
        if ($coolingDays > 3500) {
            return $humidity > 60 ? 'Very Hot-Humid' : 'Very Hot-Dry';
        }
        
        // Hot: 2000-3500 cooling degree days
        if ($coolingDays > 2000) {
            return $humidity > 60 ? 'Hot-Humid' : 'Hot-Dry';
        }
        
        // Mixed zones: balanced heating/cooling needs
        if ($heatingDays > 2000) {
            return 'Mixed-Cold';
        }
        
        // Marine: mild temperatures year-round
        if ($coolingDays < 1000 && $heatingDays < 3000) {
            return 'Marine';
        }
        
        // Default mixed climate
        return $humidity > 60 ? 'Mixed-Humid' : 'Mixed';
    }
    
    /**
     * Calculate cooling priority (1-100 scale)
     */
    private static function calculateCoolingPriority($coolingDays, $humidity) {
        $base = min(($coolingDays / 4000) * 70, 70); // Base cooling need
        $humidityBonus = $humidity > 60 ? 15 : 0;    // Humidity adjustment
        $seasonBonus = self::isCoollingSeason() ? 15 : 0; // Seasonal adjustment
        
        return min($base + $humidityBonus + $seasonBonus, 100);
    }
    
    /**
     * Calculate heating priority (1-100 scale)
     */
    private static function calculateHeatingPriority($heatingDays) {
        $base = min(($heatingDays / 7000) * 70, 70); // Base heating need
        $seasonBonus = self::isHeatingSeason() ? 30 : 0; // Seasonal adjustment
        
        return min($base + $seasonBonus, 100);
    }
    
    /**
     * Get current season for seasonal adjustments
     */
    private static function getCurrentSeason() {
        $month = (int)date('n'); // 1-12
        
        if ($month >= 12 || $month <= 2) return 'Winter';
        if ($month >= 3 && $month <= 5) return 'Spring';
        if ($month >= 6 && $month <= 8) return 'Summer';
        return 'Fall';
    }
    
    /**
     * Check if it's cooling season (May-September)
     */
    private static function isCoollingSeason() {
        $month = (int)date('n');
        return $month >= 5 && $month <= 9;
    }
    
    /**
     * Check if it's heating season (October-April)
     */
    private static function isHeatingSeason() {
        $month = (int)date('n');
        return $month >= 10 || $month <= 4;
    }
    
    /**
     * Calculate market opportunity score
     */
    private static function calculateMarketOpportunity($locationData) {
        $population = (float)($locationData['population'] ?? 1000);
        $income = (float)($locationData['median_income'] ?? 50000);
        $climateScore = self::getClimateOpportunityScore($locationData['climate_zone']);
        
        // Normalize and weight factors
        $popScore = min($population / 100000, 1) * 30; // Population factor (0-30)
        $incomeScore = min($income / 80000, 1) * 40;   // Income factor (0-40)
        $climateWeight = $climateScore * 30;           // Climate factor (0-30)
        
        return round($popScore + $incomeScore + $climateWeight, 1);
    }
    
    /**
     * Get climate-based opportunity multiplier
     */
    private static function getClimateOpportunityScore($climateZone) {
        $scores = [
            'Very Hot-Dry' => 0.9,    // High AC demand
            'Very Hot-Humid' => 1.0,  // Highest HVAC demand
            'Hot-Dry' => 0.8,
            'Hot-Humid' => 0.9,
            'Cold' => 0.8,            // High heating demand
            'Very Cold' => 0.9,       // Very high heating demand
            'Mixed' => 1.0,           // Year-round demand
            'Mixed-Cold' => 0.9,
            'Mixed-Humid' => 1.0,
            'Marine' => 0.6           // Lower overall demand
        ];
        
        return $scores[$climateZone] ?? 0.7;
    }
    
    /**
     * Clear location cache
     */
    public static function clearCache() {
        self::$locationCache = [];
    }
    
    /**
     * Get cache statistics
     */
    public static function getCacheStats() {
        return [
            'cached_locations' => count(self::$locationCache),
            'memory_usage_kb' => round(memory_get_usage() / 1024, 2),
            'cache_keys' => array_keys(self::$locationCache)
        ];
    }
}

?>