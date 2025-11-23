<?php
/**
 * OPTIMIZED KEYWORD GENERATION API
 * High-performance version with caching and improved algorithms
 */

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/phrase_cache.php";
require_once __DIR__ . "/../includes/optimized_location.php";

// Performance and error handling
ini_set('max_execution_time', 30); // Reduced from 45 seconds
ini_set('memory_limit', '256M');
set_time_limit(30);

$startTime = microtime(true);
$errors = [];
$warnings = [];

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600'); // 1-hour cache

// Input validation and sanitization
$zipCode = isset($_GET['zip']) ? preg_replace('/[^0-9]/', '', $_GET['zip']) : '';
$limit = min(max((int)($_GET['limit'] ?? 100), 1), 500); // Reasonable limits
$includeCategories = filter_var($_GET['categories'] ?? true, FILTER_VALIDATE_BOOLEAN);
$includeCompetitor = filter_var($_GET['competitor'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (strlen($zipCode) !== 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ZIP code format', 'code' => 'INVALID_ZIP']);
    exit;
}

try {
    // Initialize performance tracking
    $performanceMetrics = [
        'start_time' => $startTime,
        'database_queries' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'api_calls_made' => 0
    ];
    
    // Get optimized location data
    $locationData = OptimizedLocationData::getLocationData($zipCode);
    $performanceMetrics['database_queries']++;
    
    if (!$locationData) {
        http_response_code(404);
        echo json_encode([
            'error' => 'ZIP code not found in database',
            'code' => 'ZIP_NOT_FOUND',
            'zip_code' => $zipCode
        ]);
        exit;
    }
    
    // Determine climate zone and priorities
    $climateZone = $locationData['climate_zone'];
    $coolingPriority = $locationData['cooling_priority'];
    $heatingPriority = $locationData['heating_priority'];
    $currentSeason = $locationData['current_season'];
    
    // Generate optimized location-specific phrases
    PhraseCache::init();
    $locationPhrases = OptimizedPhraseGenerator::generateOptimizedPhrases($locationData, $climateZone, $limit);
    
    if (empty($locationPhrases)) {
        $performanceMetrics['cache_misses']++;
        $warnings[] = "No phrases generated for climate zone: {$climateZone}";
    } else {
        $performanceMetrics['cache_hits']++;
    }
    
    // Process phrases with Google Autocomplete (optimized batch processing)
    $keywords = [];
    $processed = 0;
    $maxProcessingTime = 25; // Leave 5 seconds buffer
    $batchSize = 10;
    
    for ($i = 0; $i < count($locationPhrases) && $processed < $limit; $i += $batchSize) {
        if ((microtime(true) - $startTime) > $maxProcessingTime) {
            $warnings[] = "Processing stopped due to time limit";
            break;
        }
        
        $batch = array_slice($locationPhrases, $i, $batchSize);
        $batchResults = processPhraseBatch($batch, $locationData, $performanceMetrics);
        $keywords = array_merge($keywords, $batchResults);
        $processed += count($batch);
    }
    
    // Apply climate-aware scoring
    $keywords = applyOptimizedScoring($keywords, $locationData, $currentSeason);
    
    // Sort by score and apply limit
    usort($keywords, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    $keywords = array_slice($keywords, 0, $limit);
    
    // Generate categories if requested
    $categories = $includeCategories ? generateOptimizedCategories($keywords, $climateZone) : [];
    
    // Calculate final metrics
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    $performanceMetrics['execution_time_seconds'] = $executionTime;
    $performanceMetrics['keywords_generated'] = count($keywords);
    $performanceMetrics['phrases_processed'] = $processed;
    $performanceMetrics['memory_usage_mb'] = round(memory_get_usage() / 1024 / 1024, 2);
    $performanceMetrics['peak_memory_mb'] = round(memory_get_peak_usage() / 1024 / 1024, 2);
    
    // Response structure
    $response = [
        'success' => true,
        'zip_code' => $zipCode,
        'location_data' => [
            'city' => $locationData['city'],
            'state' => $locationData['state'],
            'state_code' => $locationData['state_code'],
            'metro_area' => $locationData['metro_area'],
            'county' => $locationData['county']
        ],
        'climate_analysis' => [
            'zone' => $climateZone,
            'cooling_priority' => $coolingPriority,
            'heating_priority' => $heatingPriority,
            'current_season' => $currentSeason,
            'market_opportunity' => $locationData['market_opportunity']
        ],
        'keyword_count' => count($keywords),
        'keywords' => $keywords,
        'performance' => $performanceMetrics
    ];
    
    if ($includeCategories) {
        $response['categories'] = $categories;
    }
    
    if (!empty($warnings)) {
        $response['warnings'] = $warnings;
    }
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    // Add cache info
    $response['cache_stats'] = PhraseCache::getStats();
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    $errorResponse = [
        'error' => 'Internal server error',
        'code' => 'INTERNAL_ERROR',
        'message' => $e->getMessage(),
        'execution_time' => round((microtime(true) - $startTime), 2)
    ];
    
    if (!empty($errors)) {
        $errorResponse['errors'] = $errors;
    }
    
    echo json_encode($errorResponse);
    error_log("Keyword API Error: " . $e->getMessage());
}

/**
 * Process a batch of phrases efficiently
 */
function processPhraseBatch($phraseBatch, $locationData, &$performanceMetrics) {
    $results = [];
    $googleApiDelay = 50; // 50ms between calls
    
    foreach ($phraseBatch as $phraseData) {
        $phrase = $phraseData['phrase'];
        $priority = $phraseData['priority'];
        
        // Simulate Google Autocomplete call (optimized)
        $autocompleteResults = getOptimizedAutocomplete($phrase, $locationData);
        $performanceMetrics['api_calls_made']++;
        
        foreach ($autocompleteResults as $keyword) {
            $results[] = [
                'keyword' => $keyword,
                'base_phrase' => $phrase,
                'priority' => $priority,
                'template' => $phraseData['template'] ?? '',
                'raw_score' => 50 // Base score, will be adjusted
            ];
        }
        
        // Small delay to respect API limits
        usleep($googleApiDelay * 1000);
    }
    
    return $results;
}

/**
 * Optimized autocomplete simulation
 */
function getOptimizedAutocomplete($phrase, $locationData) {
    // For demo - return relevant variations
    $city = strtolower($locationData['city']);
    $state = strtolower($locationData['state_code']);
    
    $variations = [
        $phrase,
        $phrase . " near me",
        $phrase . " cost",
        $phrase . " companies",
        str_replace($city, $city . " " . $state, $phrase)
    ];
    
    return array_slice($variations, 0, 3); // Limit variations
}

/**
 * Apply optimized climate-aware scoring
 */
function applyOptimizedScoring($keywords, $locationData, $currentSeason) {
    $climateZone = $locationData['climate_zone'];
    $coolingPriority = $locationData['cooling_priority'];
    $heatingPriority = $locationData['heating_priority'];
    $marketOpportunity = $locationData['market_opportunity'];
    
    foreach ($keywords as &$keyword) {
        $phrase = strtolower($keyword['keyword']);
        $baseScore = $keyword['raw_score'];
        
        // Climate relevance scoring
        $climateScore = 0;
        if (strpos($phrase, 'ac ') !== false || strpos($phrase, 'air condition') !== false || strpos($phrase, 'cooling') !== false) {
            $climateScore = $coolingPriority;
        } elseif (strpos($phrase, 'heat') !== false || strpos($phrase, 'furnace') !== false || strpos($phrase, 'boiler') !== false) {
            $climateScore = $heatingPriority;
        } elseif (strpos($phrase, 'hvac') !== false || strpos($phrase, 'heat pump') !== false) {
            $climateScore = ($coolingPriority + $heatingPriority) / 2;
        }
        
        // Seasonal adjustments
        $seasonalMultiplier = 1.0;
        if ($currentSeason === 'Summer' && strpos($phrase, 'cool') !== false) {
            $seasonalMultiplier = 1.3;
        } elseif ($currentSeason === 'Winter' && strpos($phrase, 'heat') !== false) {
            $seasonalMultiplier = 1.3;
        }
        
        // Priority adjustments
        $priorityMultiplier = match($keyword['priority']) {
            'high' => 1.2,
            'medium' => 1.0,
            'low' => 0.8,
            default => 1.0
        };
        
        // Location specificity bonus
        $city = strtolower($locationData['city']);
        $state = strtolower($locationData['state_code']);
        $locationBonus = 0;
        if (strpos($phrase, $city) !== false) $locationBonus += 20;
        if (strpos($phrase, $state) !== false) $locationBonus += 10;
        
        // Calculate final score
        $finalScore = ($baseScore + $climateScore + $locationBonus) * $seasonalMultiplier * $priorityMultiplier;
        $finalScore *= ($marketOpportunity / 100); // Market opportunity adjustment
        
        $keyword['score'] = round($finalScore, 1);
        $keyword['climate_zone'] = $climateZone;
        $keyword['cooling_priority'] = $coolingPriority;
        $keyword['heating_priority'] = $heatingPriority;
    }
    
    return $keywords;
}

/**
 * Generate optimized categories
 */
function generateOptimizedCategories($keywords, $climateZone) {
    $categories = [
        'cooling_issues' => [],
        'heating_issues' => [],
        'heat_pump' => [],
        'maintenance' => [],
        'installation' => [],
        'repair' => [],
        'emergency' => [],
        'efficiency' => []
    ];
    
    foreach ($keywords as $keyword) {
        $phrase = strtolower($keyword['keyword']);
        
        if (strpos($phrase, 'ac ') !== false || strpos($phrase, 'air condition') !== false || strpos($phrase, 'cooling') !== false) {
            $categories['cooling_issues'][] = $keyword['keyword'];
        } elseif (strpos($phrase, 'furnace') !== false || strpos($phrase, 'boiler') !== false || strpos($phrase, 'heating') !== false) {
            $categories['heating_issues'][] = $keyword['keyword'];
        } elseif (strpos($phrase, 'heat pump') !== false) {
            $categories['heat_pump'][] = $keyword['keyword'];
        } elseif (strpos($phrase, 'maintenance') !== false || strpos($phrase, 'service') !== false) {
            $categories['maintenance'][] = $keyword['keyword'];
        } elseif (strpos($phrase, 'install') !== false) {
            $categories['installation'][] = $keyword['keyword'];
        } elseif (strpos($phrase, 'repair') !== false) {
            $categories['repair'][] = $keyword['keyword'];
        } elseif (strpos($phrase, 'emergency') !== false || strpos($phrase, 'urgent') !== false) {
            $categories['emergency'][] = $keyword['keyword'];
        } elseif (strpos($phrase, 'efficiency') !== false || strpos($phrase, 'energy') !== false) {
            $categories['efficiency'][] = $keyword['keyword'];
        }
    }
    
    // Remove empty categories and limit items
    foreach ($categories as $category => $items) {
        if (empty($items)) {
            unset($categories[$category]);
        } else {
            $categories[$category] = array_slice(array_unique($items), 0, 20);
        }
    }
    
    return $categories;
}

?>