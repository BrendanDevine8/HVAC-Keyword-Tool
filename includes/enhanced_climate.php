<?php
/**
 * ENHANCED CLIMATE ZONE DETECTION SYSTEM
 * Precise sub-zones with seasonal adjustments and micro-climate analysis
 */

class EnhancedClimateAnalyzer {
    
    /**
     * Detailed climate zones with sub-classifications
     */
    private static $climateZones = [
        // Hot-Dry Zones
        'Desert-Extreme' => ['min_cooling_days' => 4000, 'max_humidity' => 30, 'priority' => ['cooling', 'dust_filtration']],
        'Desert-Moderate' => ['min_cooling_days' => 2500, 'max_humidity' => 40, 'priority' => ['cooling', 'energy_efficiency']],
        'Semi-Arid' => ['min_cooling_days' => 2000, 'max_humidity' => 50, 'priority' => ['cooling', 'heat_pump']],
        
        // Hot-Humid Zones
        'Tropical-Humid' => ['min_cooling_days' => 3000, 'min_humidity' => 70, 'priority' => ['cooling', 'dehumidification', 'mold_prevention']],
        'Subtropical-Humid' => ['min_cooling_days' => 2000, 'min_humidity' => 60, 'priority' => ['cooling', 'humidity_control', 'air_quality']],
        
        // Cold Zones
        'Arctic' => ['min_heating_days' => 9000, 'priority' => ['heating', 'insulation', 'emergency_heat']],
        'Subarctic' => ['min_heating_days' => 7000, 'priority' => ['heating', 'dual_fuel', 'backup_systems']],
        'Continental-Cold' => ['min_heating_days' => 5000, 'priority' => ['heating', 'heat_pump', 'efficiency']],
        
        // Mixed Zones
        'Continental-Mixed' => ['heating_cooling_ratio' => [0.4, 0.6], 'priority' => ['heat_pump', 'seasonal_maintenance', 'zoning']],
        'Humid-Continental' => ['min_humidity' => 55, 'heating_cooling_ratio' => [0.45, 0.55], 'priority' => ['heat_pump', 'humidity_control', 'seasonal']],
        'Mediterranean' => ['mild_winters' => true, 'dry_summers' => true, 'priority' => ['heat_pump', 'energy_efficiency', 'smart_controls']],
        
        // Marine Zones
        'Marine-Cool' => ['temp_range' => [40, 75], 'priority' => ['heat_pump', 'ductless', 'ventilation']],
        'Marine-Mild' => ['temp_range' => [45, 80], 'priority' => ['mini_split', 'air_quality', 'mild_heating']]
    ];
    
    /**
     * Seasonal adjustment factors by month
     */
    private static $seasonalFactors = [
        1 => ['heating' => 1.5, 'cooling' => 0.3, 'maintenance' => 0.8], // January
        2 => ['heating' => 1.4, 'cooling' => 0.3, 'maintenance' => 0.9], // February  
        3 => ['heating' => 1.2, 'cooling' => 0.5, 'maintenance' => 1.2], // March
        4 => ['heating' => 0.8, 'cooling' => 0.7, 'maintenance' => 1.3], // April
        5 => ['heating' => 0.5, 'cooling' => 1.0, 'maintenance' => 1.4], // May
        6 => ['heating' => 0.3, 'cooling' => 1.4, 'maintenance' => 1.2], // June
        7 => ['heating' => 0.2, 'cooling' => 1.5, 'maintenance' => 1.0], // July
        8 => ['heating' => 0.2, 'cooling' => 1.5, 'maintenance' => 1.0], // August
        9 => ['heating' => 0.4, 'cooling' => 1.3, 'maintenance' => 1.2], // September
        10 => ['heating' => 0.7, 'cooling' => 1.0, 'maintenance' => 1.3], // October
        11 => ['heating' => 1.1, 'cooling' => 0.6, 'maintenance' => 1.1], // November
        12 => ['heating' => 1.4, 'cooling' => 0.4, 'maintenance' => 0.9]  // December
    ];
    
    /**
     * Analyze location for precise climate zone with seasonal adjustments
     */
    public static function analyzeClimate($locationData, $includeSeasonalFactors = true) {
        $heatingDays = (float)($locationData['heating_degree_days'] ?? 0);
        $coolingDays = (float)($locationData['cooling_degree_days'] ?? 0);
        $humidity = (float)($locationData['humidity_index'] ?? 50);
        $latitude = (float)($locationData['latitude'] ?? 0);
        $state = $locationData['state_code'] ?? '';
        
        // Determine base climate zone
        $baseZone = self::determineBaseClimateZone($heatingDays, $coolingDays, $humidity, $latitude, $state);
        
        // Get climate characteristics
        $characteristics = self::getClimateCharacteristics($baseZone, $locationData);
        
        // Apply seasonal adjustments
        $seasonalAdjustments = $includeSeasonalFactors ? self::calculateSeasonalAdjustments($baseZone) : [];
        
        // Calculate dynamic priorities
        $priorities = self::calculateDynamicPriorities($baseZone, $locationData, $seasonalAdjustments);
        
        return [
            'primary_zone' => $baseZone,
            'sub_zone' => self::determineSubZone($baseZone, $locationData),
            'characteristics' => $characteristics,
            'seasonal_factors' => $seasonalAdjustments,
            'current_priorities' => $priorities,
            'hvac_recommendations' => self::getHVACRecommendations($baseZone, $priorities),
            'seasonal_tips' => self::getSeasonalTips($baseZone)
        ];
    }
    
    /**
     * Determine base climate zone using enhanced criteria
     */
    private static function determineBaseClimateZone($heatingDays, $coolingDays, $humidity, $latitude, $state) {
        // Arctic/Subarctic regions
        if ($heatingDays > 9000 || $latitude > 60) {
            return 'Arctic';
        }
        
        if ($heatingDays > 7000 || ($latitude > 50 && $heatingDays > 5000)) {
            return 'Subarctic';
        }
        
        // Desert zones (low humidity + high cooling demand)
        if ($humidity < 30 && $coolingDays > 4000) {
            return 'Desert-Extreme';
        }
        
        if ($humidity < 40 && $coolingDays > 2500) {
            return 'Desert-Moderate';
        }
        
        if ($humidity < 50 && $coolingDays > 2000) {
            return 'Semi-Arid';
        }
        
        // Tropical/Subtropical zones
        if ($humidity > 70 && $coolingDays > 3000) {
            return 'Tropical-Humid';
        }
        
        if ($humidity > 60 && $coolingDays > 2000) {
            return 'Subtropical-Humid';
        }
        
        // Cold continental zones
        if ($heatingDays > 5000) {
            return 'Continental-Cold';
        }
        
        // Marine climates (moderate temperatures, coastal)
        if (self::isCoastalLocation($state) && $coolingDays < 1500 && $heatingDays < 4000) {
            if ($coolingDays < 1000) {
                return 'Marine-Cool';
            }
            return 'Marine-Mild';
        }
        
        // Mediterranean climate
        if (in_array($state, ['CA', 'OR']) && $coolingDays < 2000 && $heatingDays < 3000) {
            return 'Mediterranean';
        }
        
        // Mixed continental zones
        $heatingCoolingRatio = $coolingDays > 0 ? $heatingDays / ($heatingDays + $coolingDays) : 1;
        
        if ($humidity > 55 && $heatingCoolingRatio > 0.4 && $heatingCoolingRatio < 0.6) {
            return 'Humid-Continental';
        }
        
        if ($heatingCoolingRatio > 0.3 && $heatingCoolingRatio < 0.7) {
            return 'Continental-Mixed';
        }
        
        // Default fallback
        return 'Continental-Mixed';
    }
    
    /**
     * Determine sub-zone classification
     */
    private static function determineSubZone($baseZone, $locationData) {
        $population = (float)($locationData['population'] ?? 0);
        $income = (float)($locationData['median_income'] ?? 0);
        $urbanDensity = $population > 100000 ? 'Urban' : ($population > 25000 ? 'Suburban' : 'Rural');
        
        $economicLevel = $income > 80000 ? 'High' : ($income > 50000 ? 'Medium' : 'Low');
        
        return "{$baseZone}-{$urbanDensity}-{$economicLevel}";
    }
    
    /**
     * Get climate characteristics
     */
    private static function getClimateCharacteristics($zone, $locationData) {
        $zoneConfig = self::$climateZones[$zone] ?? [];
        
        return [
            'cooling_demand' => self::calculateCoolingDemand($locationData),
            'heating_demand' => self::calculateHeatingDemand($locationData),
            'humidity_concerns' => self::calculateHumidityConcerns($locationData),
            'energy_efficiency_importance' => self::calculateEfficiencyImportance($locationData),
            'equipment_priorities' => $zoneConfig['priority'] ?? [],
            'maintenance_schedule' => self::getMaintenanceSchedule($zone)
        ];
    }
    
    /**
     * Calculate seasonal adjustments for current time
     */
    private static function calculateSeasonalAdjustments($zone) {
        $currentMonth = (int)date('n');
        $baseFactor = self::$seasonalFactors[$currentMonth] ?? ['heating' => 1.0, 'cooling' => 1.0, 'maintenance' => 1.0];
        
        // Zone-specific seasonal adjustments
        $zoneMultipliers = [
            'Desert-Extreme' => ['cooling' => 1.2, 'heating' => 0.8],
            'Arctic' => ['heating' => 1.3, 'cooling' => 0.5],
            'Tropical-Humid' => ['cooling' => 1.1, 'humidity' => 1.2],
            'Marine-Cool' => ['heating' => 1.1, 'cooling' => 0.9]
        ];
        
        $multiplier = $zoneMultipliers[$zone] ?? ['heating' => 1.0, 'cooling' => 1.0];
        
        return [
            'heating_factor' => $baseFactor['heating'] * ($multiplier['heating'] ?? 1.0),
            'cooling_factor' => $baseFactor['cooling'] * ($multiplier['cooling'] ?? 1.0),
            'maintenance_factor' => $baseFactor['maintenance'],
            'current_month' => $currentMonth,
            'season' => self::getCurrentSeason(),
            'peak_demand_period' => self::isPeakDemandPeriod($zone, $currentMonth)
        ];
    }
    
    /**
     * Calculate dynamic priorities based on current conditions
     */
    private static function calculateDynamicPriorities($zone, $locationData, $seasonalFactors) {
        $basePriorities = self::$climateZones[$zone]['priority'] ?? ['hvac'];
        $currentPriorities = [];
        
        foreach ($basePriorities as $priority) {
            $weight = self::calculatePriorityWeight($priority, $locationData, $seasonalFactors);
            $currentPriorities[$priority] = $weight;
        }
        
        // Sort by weight
        arsort($currentPriorities);
        
        return $currentPriorities;
    }
    
    /**
     * Calculate priority weight for specific equipment/service type
     */
    private static function calculatePriorityWeight($priority, $locationData, $seasonalFactors) {
        $baseWeight = 50;
        
        switch ($priority) {
            case 'cooling':
                $baseWeight += ($locationData['cooling_degree_days'] ?? 0) / 50;
                $baseWeight *= $seasonalFactors['cooling_factor'] ?? 1.0;
                break;
                
            case 'heating':
                $baseWeight += ($locationData['heating_degree_days'] ?? 0) / 100;
                $baseWeight *= $seasonalFactors['heating_factor'] ?? 1.0;
                break;
                
            case 'humidity_control':
            case 'dehumidification':
                $humidity = $locationData['humidity_index'] ?? 50;
                $baseWeight += ($humidity > 60) ? ($humidity - 60) * 2 : 0;
                break;
                
            case 'heat_pump':
                $mixed_climate_bonus = (abs(($locationData['heating_degree_days'] ?? 0) - ($locationData['cooling_degree_days'] ?? 0)) < 1000) ? 20 : 0;
                $baseWeight += $mixed_climate_bonus;
                break;
                
            case 'energy_efficiency':
                $income = $locationData['median_income'] ?? 50000;
                $baseWeight += ($income > 70000) ? 15 : 0;
                break;
        }
        
        return round($baseWeight, 1);
    }
    
    /**
     * Get HVAC equipment recommendations
     */
    private static function getHVACRecommendations($zone, $priorities) {
        $recommendations = [];
        
        $topPriority = array_key_first($priorities);
        
        switch ($topPriority) {
            case 'cooling':
                $recommendations[] = 'High-efficiency central air conditioning';
                $recommendations[] = 'Variable-speed compressor systems';
                $recommendations[] = 'Smart thermostats with cooling optimization';
                break;
                
            case 'heating':
                $recommendations[] = 'High-efficiency furnace or boiler';
                $recommendations[] = 'Backup heating systems';
                $recommendations[] = 'Improved insulation and air sealing';
                break;
                
            case 'heat_pump':
                $recommendations[] = 'Air-source or ground-source heat pump';
                $recommendations[] = 'Dual-fuel systems for extreme weather';
                $recommendations[] = 'Zoned HVAC for efficiency';
                break;
                
            case 'humidity_control':
                $recommendations[] = 'Whole-house dehumidification';
                $recommendations[] = 'Enhanced air filtration';
                $recommendations[] = 'Mold prevention systems';
                break;
        }
        
        return $recommendations;
    }
    
    /**
     * Get seasonal maintenance tips
     */
    private static function getSeasonalTips($zone) {
        $season = self::getCurrentSeason();
        
        $tips = [
            'Spring' => ['Replace air filters', 'Schedule AC tune-up', 'Check ductwork'],
            'Summer' => ['Monitor cooling efficiency', 'Clean condenser coils', 'Check refrigerant levels'],
            'Fall' => ['Schedule heating tune-up', 'Replace filters', 'Check insulation'],
            'Winter' => ['Monitor heating efficiency', 'Check for air leaks', 'Emergency preparedness']
        ];
        
        return $tips[$season] ?? [];
    }
    
    // Helper methods
    private static function isCoastalLocation($state) {
        $coastalStates = ['CA', 'OR', 'WA', 'FL', 'GA', 'SC', 'NC', 'VA', 'MD', 'DE', 'NJ', 'NY', 'CT', 'RI', 'MA', 'NH', 'ME', 'TX', 'LA', 'MS', 'AL'];
        return in_array($state, $coastalStates);
    }
    
    private static function getCurrentSeason() {
        $month = (int)date('n');
        if ($month >= 12 || $month <= 2) return 'Winter';
        if ($month >= 3 && $month <= 5) return 'Spring';
        if ($month >= 6 && $month <= 8) return 'Summer';
        return 'Fall';
    }
    
    private static function isPeakDemandPeriod($zone, $month) {
        // Hot zones: June-August peak cooling
        if (strpos($zone, 'Desert') !== false || strpos($zone, 'Tropical') !== false) {
            return $month >= 6 && $month <= 8;
        }
        
        // Cold zones: December-February peak heating  
        if (strpos($zone, 'Arctic') !== false || strpos($zone, 'Continental-Cold') !== false) {
            return $month >= 12 || $month <= 2;
        }
        
        return false;
    }
    
    private static function calculateCoolingDemand($locationData) {
        return min(($locationData['cooling_degree_days'] ?? 0) / 4000 * 100, 100);
    }
    
    private static function calculateHeatingDemand($locationData) {
        return min(($locationData['heating_degree_days'] ?? 0) / 8000 * 100, 100);
    }
    
    private static function calculateHumidityConcerns($locationData) {
        $humidity = $locationData['humidity_index'] ?? 50;
        return max(0, ($humidity - 50) * 2);
    }
    
    private static function calculateEfficiencyImportance($locationData) {
        $income = $locationData['median_income'] ?? 50000;
        return min($income / 80000 * 100, 100);
    }
    
    private static function getMaintenanceSchedule($zone) {
        if (strpos($zone, 'Desert') !== false) {
            return ['filter_change_months' => 2, 'annual_service' => 'Spring', 'coil_cleaning' => 'Monthly'];
        }
        
        if (strpos($zone, 'Humid') !== false) {
            return ['filter_change_months' => 1, 'annual_service' => 'Spring+Fall', 'humidity_check' => 'Monthly'];
        }
        
        return ['filter_change_months' => 3, 'annual_service' => 'Spring', 'general_check' => 'Seasonal'];
    }
}

?>