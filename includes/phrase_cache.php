<?php
/**
 * PHRASE TEMPLATE CACHE SYSTEM
 * High-performance caching for climate-specific phrase templates
 */

class PhraseCache {
    private static $cache = [];
    private static $cacheFile = __DIR__ . '/../cache/phrase_templates.json';
    private static $cacheExpiry = 3600; // 1 hour
    
    /**
     * Initialize cache system and load from disk if available
     */
    public static function init() {
        // Ensure cache directory exists
        $cacheDir = dirname(self::$cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        // Load existing cache if valid
        if (file_exists(self::$cacheFile)) {
            $cacheData = json_decode(file_get_contents(self::$cacheFile), true);
            if ($cacheData && isset($cacheData['timestamp'])) {
                $age = time() - $cacheData['timestamp'];
                if ($age < self::$cacheExpiry) {
                    self::$cache = $cacheData['data'] ?? [];
                    return true;
                }
            }
        }
        
        // Initialize empty cache
        self::$cache = [];
        return false;
    }
    
    /**
     * Get cached phrase templates for a climate zone
     */
    public static function getTemplates($climateZone) {
        $key = "templates_{$climateZone}";
        return self::$cache[$key] ?? null;
    }
    
    /**
     * Cache phrase templates for a climate zone
     */
    public static function setTemplates($climateZone, $templates) {
        $key = "templates_{$climateZone}";
        self::$cache[$key] = $templates;
        self::saveToDisk();
    }
    
    /**
     * Get pre-computed phrases for a specific location
     */
    public static function getLocationPhrases($zipCode, $climateZone) {
        $key = "location_{$zipCode}_{$climateZone}";
        return self::$cache[$key] ?? null;
    }
    
    /**
     * Cache pre-computed phrases for a location
     */
    public static function setLocationPhrases($zipCode, $climateZone, $phrases) {
        $key = "location_{$zipCode}_{$climateZone}";
        self::$cache[$key] = $phrases;
        self::saveToDisk();
    }
    
    /**
     * Save cache to disk for persistence
     */
    private static function saveToDisk() {
        $cacheData = [
            'timestamp' => time(),
            'data' => self::$cache
        ];
        file_put_contents(self::$cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Clear all cached data
     */
    public static function clear() {
        self::$cache = [];
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats() {
        return [
            'entries' => count(self::$cache),
            'size_kb' => round(strlen(json_encode(self::$cache)) / 1024, 2),
            'file_exists' => file_exists(self::$cacheFile),
            'last_updated' => file_exists(self::$cacheFile) ? filemtime(self::$cacheFile) : null
        ];
    }
}

/**
 * OPTIMIZED LOCATION PHRASE GENERATOR
 * Uses caching and templates for better performance
 */
class OptimizedPhraseGenerator {
    
    private static $climateTemplates = [
        'Hot-Dry' => [
            'primary' => [
                'ac repair {city}',
                'air conditioning {city} {state_code}',
                'cooling system {state}',
                'ac service {city}',
                'desert cooling {city}'
            ],
            'secondary' => [
                'ac maintenance {state}',
                'cooling costs {city}',
                'air conditioning companies {state}',
                'hvac contractors {city}',
                'ac not cooling {state}'
            ],
            'priority' => ['cooling', 'ac', 'refrigeration']
        ],
        
        'Hot-Humid' => [
            'primary' => [
                'ac repair {city}',
                'humidity control {state}',
                'dehumidifier {city}',
                'air conditioning {state}',
                'mold prevention {city}'
            ],
            'secondary' => [
                'hvac repair {state}',
                'ac service {city}',
                'air quality {state}',
                'cooling system {city}',
                'hvac contractors {state}'
            ],
            'priority' => ['humidity', 'cooling', 'air_quality']
        ],
        
        'Cold' => [
            'primary' => [
                'furnace repair {city}',
                'heating system {state}',
                'boiler repair {city}',
                'gas furnace {state}',
                'heating costs {city}'
            ],
            'secondary' => [
                'heating maintenance {state}',
                'furnace service {city}',
                'winter heating {state}',
                'heating contractors {city}',
                'heating bills {state}'
            ],
            'priority' => ['heating', 'furnace', 'boiler']
        ],
        
        'Mixed' => [
            'primary' => [
                'heat pump repair {city}',
                'hvac service {state}',
                'heating cooling {city}',
                'hvac repair {state}',
                'seasonal hvac {city}'
            ],
            'secondary' => [
                'hvac contractors {state}',
                'hvac maintenance {city}',
                'heat pump service {state}',
                'hvac companies {city}',
                'hvac installation {state}'
            ],
            'priority' => ['heat_pump', 'hvac', 'seasonal']
        ],
        
        'Marine' => [
            'primary' => [
                'heat pump repair {city}',
                'mini split {state}',
                'ductless heating {city}',
                'energy efficient {state}',
                'coastal hvac {city}'
            ],
            'secondary' => [
                'mini split installation {state}',
                'ductless hvac {city}',
                'ventilation systems {state}',
                'heat pump service {city}',
                'mild climate hvac {state}'
            ],
            'priority' => ['heat_pump', 'ductless', 'efficiency']
        ]
    ];
    
    /**
     * Generate optimized location-specific phrases using templates
     */
    public static function generateOptimizedPhrases($locationData, $climateZone, $limit = 50) {
        // Initialize cache
        PhraseCache::init();
        
        $zipCode = $locationData['zip_code'] ?? '';
        
        // Check cache first
        $cached = PhraseCache::getLocationPhrases($zipCode, $climateZone);
        if ($cached) {
            return array_slice($cached, 0, $limit);
        }
        
        // Generate phrases from templates
        $phrases = self::processTemplates($locationData, $climateZone);
        
        // Cache the result
        if ($zipCode) {
            PhraseCache::setLocationPhrases($zipCode, $climateZone, $phrases);
        }
        
        return array_slice($phrases, 0, $limit);
    }
    
    /**
     * Process templates with location data
     */
    private static function processTemplates($locationData, $climateZone) {
        $city = $locationData['city'] ?? '';
        $state = $locationData['state'] ?? '';
        $stateCode = $locationData['state_code'] ?? '';
        $metro = $locationData['metro_area'] ?? '';
        
        // Clean city name
        $cleanCity = preg_replace('/\s+(city|town|village|township)$/i', '', $city);
        
        $phrases = [];
        $templates = self::$climateTemplates[$climateZone] ?? self::$climateTemplates['Mixed'];
        
        // Process primary templates (higher priority)
        foreach ($templates['primary'] as $template) {
            $phrase = str_replace(
                ['{city}', '{state}', '{state_code}', '{metro}'],
                [$cleanCity, $state, $stateCode, $metro],
                $template
            );
            $phrases[] = [
                'phrase' => $phrase,
                'priority' => 'high',
                'template' => $template
            ];
        }
        
        // Process secondary templates
        foreach ($templates['secondary'] as $template) {
            $phrase = str_replace(
                ['{city}', '{state}', '{state_code}', '{metro}'],
                [$cleanCity, $state, $stateCode, $metro],
                $template
            );
            $phrases[] = [
                'phrase' => $phrase,
                'priority' => 'medium',
                'template' => $template
            ];
        }
        
        return $phrases;
    }
    
    /**
     * Get template priorities for a climate zone
     */
    public static function getClimatePriorities($climateZone) {
        $templates = self::$climateTemplates[$climateZone] ?? self::$climateTemplates['Mixed'];
        return $templates['priority'] ?? [];
    }
}

?>