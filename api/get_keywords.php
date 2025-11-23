<?php

require_once __DIR__ . "/../config.php";

// Set execution timeout and error handling
ini_set('max_execution_time', 45); // 45 second hard limit
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize error tracking
$errors = [];
$warnings = [];

header('Content-Type: application/json');

// Read ZIP from request
$zip = isset($_GET['zip']) ? trim($_GET['zip']) : '';
if ($zip === '') {
    echo json_encode(['error' => 'ZIP code missing']);
    exit;
}

/**
 * ZIP → Location data from database
 * Gets comprehensive location information including IP for Google autocomplete
 */
function getLocationDataForZip($zip) {
    global $pdo, $errors;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM zip_codes WHERE zip_code = ?");
        $stmt->execute([$zip]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                'ip' => $result['suggested_ip'] ?? '67.191.100.22',
                'city' => $result['city'],
                'county' => $result['county'],
                'state' => $result['state'],
                'state_code' => $result['state_code'],
                'area' => $result['area_description'],
                'climate_zone' => $result['climate_zone'],
                'metro_area' => $result['metro_area']
            ];
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
        // Fall back to default if database error
    }
    
    // Default fallback for unknown ZIP codes
    return [
        'ip' => '67.191.100.22',
        'city' => 'Your Local Area', 
        'county' => 'Your County',
        'state' => 'Your State',
        'state_code' => '',
        'area' => 'your neighborhood',
        'climate_zone' => 'Mixed',
        'metro_area' => 'Your Metro Area'
    ];
}

$locationData = getLocationDataForZip($zip);
$localIp = $locationData['ip'];
$climateZone = $locationData['climate_zone'] ?? 'Mixed';

// Advanced climate-based prioritization
$month = (int) date('n'); // 1–12
$isSummer = in_array($month, [5,6,7,8,9]);   // May–Sept
$isWinter = in_array($month, [11,12,1,2,3]); // Nov–Mar

// Climate-based weighting factors
$coolingPriority = 0; // Base cooling weight
$heatingPriority = 0; // Base heating weight

switch ($climateZone) {
    case 'Very-Hot-Humid':     // FL, South TX, LA, South AZ
        $coolingPriority = 40;  // Heavy cooling emphasis year-round
        $heatingPriority = -10; // Minimal heating needs
        if ($isSummer) $coolingPriority += 20; // Extra summer boost
        break;
        
    case 'Hot-Humid':          // Most of TX, AR, parts of LA
        $coolingPriority = 25;
        $heatingPriority = $isWinter ? 15 : 5;
        if ($isSummer) $coolingPriority += 15;
        break;
        
    case 'Hot-Dry':            // AZ, NV, CA inland, parts of TX
        $coolingPriority = 30;
        $heatingPriority = $isWinter ? 10 : 0;
        if ($isSummer) $coolingPriority += 20;
        break;
        
    case 'Mixed':              // Mid-Atlantic, parts of South
    case 'Mixed-Humid':
        $coolingPriority = $isSummer ? 20 : 5;
        $heatingPriority = $isWinter ? 20 : 5;
        break;
        
    case 'Mixed-Cold':         // Northeast, parts of Midwest
        $coolingPriority = $isSummer ? 15 : 0;
        $heatingPriority = $isWinter ? 25 : 10;
        break;
        
    case 'Cold':               // Northern states, mountain regions
        $coolingPriority = $isSummer ? 10 : -5;
        $heatingPriority = $isWinter ? 35 : 15;
        break;
        
    case 'Marine':             // West Coast
        $coolingPriority = $isSummer ? 10 : 0;
        $heatingPriority = $isWinter ? 15 : 5;
        break;
        
    default: // 'Mixed' fallback
        $coolingPriority = $isSummer ? 15 : 5;
        $heatingPriority = $isWinter ? 15 : 5;
        break;
}

/**
 * BASE HVAC PHRASES (core problems)
 */
$basePhrases = [
    // Universal HVAC System Issues
    "hvac troubleshooting",
    "hvac repair",
    "hvac not working",
    "hvac system problems",
    "hvac maintenance",
    "hvac installation",
    "hvac replacement",
    "hvac unit not working",
    "hvac system failure",
    "hvac emergency repair",
    "hvac contractor near me",
    "hvac service cost",
    
    // Thermostat Issues
    "thermostat not working",
    "thermostat problems",
    "thermostat not turning on ac",
    "thermostat not turning on heat",
    "thermostat blank screen",
    "thermostat battery replacement",
    "thermostat wiring",
    "programmable thermostat issues",
    "smart thermostat problems",
    "thermostat calibration",
    "thermostat won't change temperature",
    "thermostat keeps resetting",
    
    // AC/Cooling Issues - Comprehensive
    "ac not working",
    "ac not blowing cold air",
    "ac not cooling",
    "ac repair",
    "ac making noise",
    "ac smells bad",
    "ac leaking water",
    "ac frozen",
    "ac freezing up",
    "weak airflow ac",
    "ac not cooling upstairs",
    "ac running but not cooling",
    "ac keeps freezing up",
    "ac short cycling",
    "ac won't turn on",
    "ac blowing hot air",
    "ac compressor not working",
    "ac fan not working",
    "ac condenser problems",
    "ac evaporator coil frozen",
    "ac refrigerant leak",
    "ac filter dirty",
    "ac ductwork problems",
    "ac installation cost",
    "ac replacement",
    "central air conditioning problems",
    "window ac not working",
    "portable ac not cooling",
    "ac maintenance",
    "ac tune up",
    "ac freon leak",
    "ac electrical problems",
    "ac capacitor replacement",
    "ac contactor problems",
    
    // Heating Issues - Comprehensive
    "furnace not heating",
    "furnace not working",
    "furnace repair",
    "furnace making noise",
    "furnace troubleshooting",
    "furnace blowing cold air",
    "furnace keeps shutting off",
    "furnace won't turn on",
    "no heat coming from vents",
    "furnace pilot light out",
    "furnace igniter problems",
    "furnace filter replacement",
    "furnace maintenance",
    "furnace installation",
    "furnace replacement cost",
    "gas furnace problems",
    "electric furnace issues",
    "oil furnace repair",
    "furnace blower motor problems",
    "furnace heat exchanger",
    "furnace flame sensor",
    "furnace pressure switch",
    "furnace thermocouple",
    "furnace ductwork",
    "boiler not working",
    "boiler repair",
    "boiler maintenance",
    "radiant heating problems",
    "baseboard heating issues",
    "heating system repair",
    "heating contractor near me",
    
    // Heat Pump Issues - Comprehensive
    "heat pump not cooling",
    "heat pump not heating",
    "heat pump freezing",
    "heat pump not heating in cold weather",
    "heat pump not working",
    "heat pump repair",
    "heat pump making noise",
    "heat pump blowing cold air",
    "heat pump short cycling",
    "heat pump defrost problems",
    "heat pump refrigerant leak",
    "heat pump compressor problems",
    "heat pump reversing valve",
    "heat pump auxiliary heat",
    "heat pump installation",
    "heat pump replacement",
    "heat pump maintenance",
    "heat pump efficiency problems",
    "mini split not working",
    "mini split repair",
    "ductless heat pump problems",
    
    // Ductwork and Ventilation
    "ductwork repair",
    "duct cleaning",
    "ductwork replacement",
    "air duct problems",
    "ductwork leaks",
    "duct insulation",
    "ventilation problems",
    "exhaust fan not working",
    "bathroom fan repair",
    "whole house fan problems",
    
    // Air Quality Issues
    "indoor air quality",
    "air purifier problems",
    "humidifier not working",
    "dehumidifier repair",
    "air filtration system",
    "hvac allergies",
    "dust in house",
    "musty smell hvac",
    "hvac mold problems",
    
    // Energy Efficiency
    "high electric bill hvac",
    "high gas bill heating",
    "hvac energy efficiency",
    "hvac cost to run",
    "energy efficient hvac",
    "hvac insulation problems",
    "hvac zoning system",
    "programmable thermostat savings",
];

// Climate-specific phrase emphasis
$climatePhrases = [];

// Very Hot Humid (FL, South TX, LA, South AZ) - Heavy cooling focus
if ($climateZone === 'Very-Hot-Humid') {
    $climatePhrases = array_merge($climatePhrases, [
        "ac not cold enough",
        "ac runs all day",
        "ac constantly running",
        "high electric bills ac",
        "ac compressor overheating",
        "ac refrigerant problems",
        "central air not working",
        "hvac cooling issues",
        "ac maintenance summer",
        "ac coil cleaning",
        "ac condenser cleaning",
        "humidity problems indoor",
        "dehumidifier with ac",
        "mold in ac unit",
        "ac mold smell",
        "ac drain clogged",
        "ac condensate problems",
        "swamp cooler vs ac",
        "ac efficiency hot weather",
        "ac unit size calculator",
        "oversized ac problems",
        "undersized ac unit",
        "ac zoning hot climate",
        "ductless ac hot weather",
        "window ac hot climate",
        "portable ac effectiveness",
        "ac insulation hot weather",
    ]);
}

// Hot Humid (Most of TX, AR, parts of LA) - Strong cooling, some heating
if ($climateZone === 'Hot-Humid') {
    $climatePhrases = array_merge($climatePhrases, [
        "ac not cold enough",
        "ac runs all day",
        "high electric bills summer",
        "ac compressor problems", 
        "central air issues",
        "hvac cooling problems",
        "ac maintenance hot weather",
        "humidity control hvac",
        "dehumidifier problems",
        "heat pump summer problems",
        "heat pump vs ac efficiency",
        "furnace maintenance fall",
        "heating system check",
        "dual fuel heat pump",
        "backup heat problems",
        "seasonal hvac transition",
    ]);
}

// Hot Dry (AZ, NV, CA inland, parts of TX) - Cooling focus, dust issues
if ($climateZone === 'Hot-Dry') {
    $climatePhrases = array_merge($climatePhrases, [
        "ac not cooling desert",
        "ac efficiency dry heat",
        "evaporative cooler problems",
        "swamp cooler vs ac",
        "evaporative cooler repair",
        "ac dust problems",
        "air filter replacement frequent",
        "hvac dust control",
        "ac coil dirty",
        "condenser coil cleaning",
        "ac airflow problems dust",
        "hvac air purification",
        "static electricity hvac",
        "dry air heating problems",
        "humidifier for dry climate",
        "heat pump dry climate",
        "ac refrigerant desert",
        "high altitude hvac",
    ]);
}

// Cold climate areas (Northern states, mountain regions) - Heavy heating focus
if ($climateZone === 'Cold') {
    $climatePhrases = array_merge($climatePhrases, [
        "furnace not heating cold weather",
        "boiler problems winter",
        "radiant heat not working",
        "heating system maintenance",
        "furnace filter replacement winter",
        "high gas bills heating",
        "furnace pilot light problems",
        "boiler pressure problems",
        "radiator not heating",
        "baseboard heat problems",
        "heat pump cold weather",
        "heat pump auxiliary heat",
        "backup heating system",
        "emergency heat problems",
        "furnace short cycling winter",
        "frozen heat pump",
        "ice on heat pump",
        "heating efficiency cold",
        "insulation heating problems",
        "ductwork freezing",
        "condensation heating ducts",
        "chimney problems heating",
        "venting issues furnace",
        "carbon monoxide heating",
        "heating system safety",
    ]);
}

// Mixed-Cold climate (Northeast, parts of Midwest) - Balanced with heating priority
if ($climateZone === 'Mixed-Cold') {
    $climatePhrases = array_merge($climatePhrases, [
        "seasonal hvac maintenance",
        "furnace filter replacement",
        "heat pump efficiency cold",
        "dual fuel system problems",
        "heating cooling transition",
        "fall furnace maintenance",
        "spring ac maintenance",
        "ductwork insulation",
        "hvac zoning system",
        "programmable thermostat winter",
        "heating bills high winter",
        "ac problems humid summer",
        "dehumidifier summer",
        "humidifier winter",
        "indoor air quality winter",
    ]);
}

// Mixed climates (Mid-Atlantic, parts of South) - True balance
if (in_array($climateZone, ['Mixed', 'Mixed-Humid'])) {
    $climatePhrases = array_merge($climatePhrases, [
        "heat pump efficiency",
        "hvac system replacement", 
        "seasonal hvac maintenance",
        "duct cleaning",
        "heat pump vs furnace",
        "heat pump vs ac",
        "dual fuel heat pump",
        "hvac zoning benefits",
        "year round hvac maintenance",
        "humidity control system",
        "indoor air quality",
        "hvac air filtration",
        "energy efficient hvac",
        "hvac cost comparison",
        "seasonal thermostat settings",
    ]);
}

// Marine climate (West Coast) - Mild, minimal heating/cooling
if ($climateZone === 'Marine') {
    $climatePhrases = array_merge($climatePhrases, [
        "heat pump mild climate",
        "mini split systems",
        "ductless heating cooling",
        "ventilation marine climate",
        "humidity control mild climate",
        "heat pump maintenance",
        "energy efficient heating",
        "radiant floor heating",
        "hvac mild weather",
        "air quality coastal",
        "salt air hvac problems",
        "corrosion hvac coastal",
    ]);
}

// SMART PHRASE PRIORITIZATION - Climate-aware ordering
$prioritizedPhrases = [];

// Always include these core high-value phrases
$corePhrases = [
    "ac not working",
    "ac not cooling", 
    "furnace not working",
    "heat pump not working",
    "thermostat not working",
    "hvac repair",
    "ac repair",
    "furnace repair",
    "hvac not working"
];

// Add climate-prioritized phrases
foreach ($basePhrases as $phrase) {
    $l = strtolower($phrase);
    
    // Cooling phrases get priority in hot climates
    if (($coolingPriority > 15) && preg_match('/\b(ac|air\s+conditioner|cooling|cool)\b/', $l)) {
        array_unshift($prioritizedPhrases, $phrase);
    }
    // Heating phrases get priority in cold climates  
    elseif (($heatingPriority > 15) && preg_match('/\b(furnace|heater|heating|boiler)\b/', $l)) {
        array_unshift($prioritizedPhrases, $phrase);
    }
    // Heat pump phrases for mixed climates
    elseif (in_array($climateZone, ['Mixed', 'Mixed-Humid', 'Hot-Humid', 'Marine']) && preg_match('/\bheat\s+pump\b/', $l)) {
        array_unshift($prioritizedPhrases, $phrase);
    }
    // Everything else goes to the end
    else {
        $prioritizedPhrases[] = $phrase;
    }
}

// Add climate-specific phrases
$prioritizedPhrases = array_merge($prioritizedPhrases, $climatePhrases);

// Remove duplicates and ensure core phrases are first
$prioritizedPhrases = array_unique(array_merge($corePhrases, $prioritizedPhrases));

// INCREASED: Allow more phrases for robust data (was 60, now 80)
$basePhrases = array_slice($prioritizedPhrases, 0, 80);

// Progress tracking
$totalExpectedCalls = count($basePhrases) * 10; // Rough estimate

/**
 * EXPAND A PHRASE INTO OPTIMIZED QUERY VARIANTS
 * - Focused on high-value modifiers
 * - Reduced API calls while maintaining coverage
 * - Prioritized based on search volume patterns
 */
function expandQueries($phrase) {
    $queries = [];

    // Base phrase variants
    $queries[] = $phrase;
    $queries[] = $phrase . " ";
    
    // High-impact problem/solution modifiers (most searched)
    $queries[] = $phrase . " repair";
    $queries[] = $phrase . " fix";
    $queries[] = $phrase . " troubleshooting";
    $queries[] = $phrase . " problems";
    $queries[] = $phrase . " help";
    $queries[] = $phrase . " issues";
    
    // Location and service (high commercial intent)
    $queries[] = $phrase . " near me";
    $queries[] = $phrase . " service";
    $queries[] = $phrase . " cost";
    $queries[] = $phrase . " contractor";
    
    // Question starters (People Also Ask optimization)
    $queries[] = $phrase . " why";
    $queries[] = $phrase . " how";
    $queries[] = $phrase . " what";
    
    // Urgency modifiers (high conversion)
    $queries[] = $phrase . " emergency";
    $queries[] = $phrase . " same day";
    
    // Seasonal context (only current season)
    $currentMonth = (int) date('n');
    if (in_array($currentMonth, [6,7,8])) { // Summer
        $queries[] = $phrase . " summer";
    } elseif (in_array($currentMonth, [12,1,2])) { // Winter
        $queries[] = $phrase . " winter";
    }
    
    // Strategic letter wheel (highest volume letters only)
    $highVolLetters = ['a', 'c', 'h', 'n', 'p', 's'];
    foreach ($highVolLetters as $letter) {
        $queries[] = $phrase . " " . $letter;
    }

    return $queries;
}

/**
 * QUESTION-STYLE SEEDS (People-also-ask style)
 * These are used to build "people_also_ask" suggestions.
 */
$questionSeeds = [
    // Why Questions - Problems
    "why is my ac",
    "why is my furnace",
    "why is my heat pump",
    "why is my hvac",
    "why is my thermostat",
    "why is my air conditioner",
    "why is my heating",
    "why is my cooling",
    "why won't my ac",
    "why won't my furnace",
    "why won't my heat pump",
    "why won't my hvac",
    "why does my ac",
    "why does my furnace",
    "why does my heat pump",
    "why does my hvac",
    
    // How Questions - Diagnostics
    "how to tell if my ac",
    "how to tell if my furnace",
    "how to tell if my heat pump",
    "how to tell if my hvac",
    "how to know if my ac",
    "how to know if my furnace",
    "how to check if my hvac",
    "how to diagnose ac",
    "how to diagnose furnace",
    "how to diagnose heat pump",
    "how to troubleshoot ac",
    "how to troubleshoot furnace",
    "how to troubleshoot hvac",
    "how to fix my ac",
    "how to fix my furnace",
    "how to fix my heat pump",
    "how to repair hvac",
    
    // What Questions - Solutions
    "what to do when ac",
    "what to do when furnace",
    "what to do when hvac",
    "what to do when heat pump",
    "what causes ac",
    "what causes furnace",
    "what causes hvac",
    "what makes ac",
    "what makes furnace",
    "what happens when ac",
    "what happens when furnace",
    "what should i do if my ac",
    "what should i do if my furnace",
    "what should i do if my hvac",
    
    // Is it normal Questions
    "is it normal for ac",
    "is it normal for furnace",
    "is it normal for heat pump",
    "is it normal for hvac",
    "is it bad if my ac",
    "is it bad if my furnace",
    "is it safe when ac",
    "is it safe when furnace",
    "is it expensive to",
    
    // Can/Should Questions - Usage
    "can i run my heat pump",
    "can i run my ac",
    "can i use my furnace",
    "should i turn off my ac",
    "should i turn off my heat",
    "should i replace my ac",
    "should i replace my furnace",
    "should i repair or replace",
    "can i fix my own ac",
    "can i fix my own furnace",
    
    // When Questions - Timing
    "when should i replace my ac",
    "when should i replace my furnace",
    "when should i service my hvac",
    "when should i change my filter",
    "when should i call hvac",
    "when to repair vs replace",
    "when does hvac need",
    
    // How much/How long - Cost and Duration
    "how much does it cost",
    "how much to repair ac",
    "how much to replace furnace",
    "how much hvac repair",
    "how long should ac",
    "how long should furnace",
    "how long does hvac",
    "how often should i",
    
    // Where Questions - Location issues
    "where is my hvac",
    "where is my ac unit",
    "where is my furnace",
    "where should i place",
    "where do i find",
    
    // Which Questions - Choices
    "which is better heat pump",
    "which hvac system",
    "which ac unit",
    "which furnace type",
    "which thermostat",
    
    // Specific Problem Queries
    "my ac is making",
    "my furnace is making",
    "my hvac is making",
    "my ac stopped",
    "my furnace stopped",
    "my heat pump stopped",
    "my ac won't",
    "my furnace won't",
    "my hvac won't",
    "my thermostat won't",
    "my air conditioner is",
    "my heating system is",
    
    // Cost and Efficiency Queries
    "how much electricity does",
    "how much gas does",
    "how efficient is my",
    "how to save money on",
    "how to reduce hvac",
    "how to lower electric bill",
    "how to lower gas bill",
    
    // Maintenance Questions
    "how often should hvac",
    "how often change filter",
    "how often service ac",
    "how often service furnace",
    "do i need annual",
    "do i need maintenance",
];

/**
 * GOOGLE AUTOCOMPLETE REQUEST WITH CACHING
 */
$apiCache = []; // Simple session-based cache

function googleSuggest($query, $fakeIp) {
    global $apiCache, $errors, $warnings;
    
    try {
        // Check cache first
        $cacheKey = md5($query . $fakeIp);
        if (isset($apiCache[$cacheKey])) {
            return $apiCache[$cacheKey];
        }
        
        $url = "https://suggestqueries.google.com/complete/search?client=firefox&q=" . urlencode($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4, // Slightly increased for stability
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "User-Agent: Mozilla/5.0",
                "X-Forwarded-For: $fakeIp",
                "Accept: application/json,text/html"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $warnings[] = "CURL error for query '$query': $error";
            curl_close($ch);
            $apiCache[$cacheKey] = []; // Cache empty results too
            return [];
        }
        
        if ($httpCode !== 200) {
            $warnings[] = "HTTP $httpCode for query '$query'";
        }
        
        curl_close($ch);

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $warnings[] = "JSON decode error for query '$query': " . json_last_error_msg();
            $apiCache[$cacheKey] = [];
            return [];
        }
        
        $result = (is_array($data) && isset($data[1])) ? $data[1] : [];
        
        // Cache the result
        $apiCache[$cacheKey] = $result;
        
        return $result;
        
    } catch (Exception $e) {
        $errors[] = "Exception in googleSuggest: " . $e->getMessage();
        return [];
    }
}

/**
 * MASTER KEYWORD COLLECTION WITH PERFORMANCE TRACKING
 */
$rawKeywords = [];
$apiCallCount = 0;
$successfulCalls = 0;
$failedCalls = 0;
$startTime = microtime(true);
$timeoutBuffer = 5; // Reserve 5 seconds for processing

// 1) Problem / service intent from base phrases
foreach ($basePhrases as $phraseIndex => $phrase) {
    // Check timeout before processing each phrase
    $currentTime = microtime(true);
    $elapsedTime = $currentTime - $startTime;
    
    if ($elapsedTime > (45 - $timeoutBuffer)) {
        $warnings[] = "Timeout approaching, stopped at phrase $phraseIndex of " . count($basePhrases);
        break;
    }
    
    try {
        $expandedQueries = expandQueries($phrase);

        // INCREASED: Allow more variants per phrase for robust data (was 8, now 12)
        $expandedQueries = array_slice($expandedQueries, 0, 12);
        
        $apiCallCount += count($expandedQueries);

        foreach ($expandedQueries as $q) {
            $suggestions = googleSuggest($q, $localIp);
            
            if (!empty($suggestions)) {
                $successfulCalls++;
            } else {
                $failedCalls++;
            }

            foreach ($suggestions as $s) {
                $clean = strtolower($s);

            // FILTER JUNK - Enhanced filtering
            if (
                // Media content
                preg_match('/(show|episode|season|series|movie|film|video|youtube|channel)/', $clean) ||
                // Non-HVAC automotive
                preg_match('/\b(car|auto|truck|jeep|vehicle|motorcycle|boat|rv|camper)\b/', $clean) ||
                // Appliances (non-HVAC)
                preg_match('/\b(refrigerator|fridge|dishwasher|washer|dryer|oven|stove|microwave)\b/', $clean) ||
                // Electronics
                preg_match('/\b(computer|laptop|phone|tv|xbox|playstation|nintendo)\b/', $clean) ||
                // Model numbers and serial numbers
                preg_match('/\b[0-9]{6,}\b/', $clean) ||
                // Too short to be useful
                strlen($clean) < 4 ||
                // Too long (likely spam)
                strlen($clean) > 100 ||
                // Contains multiple repeated words
                preg_match('/(\b\w+\b)\s+\1/', $clean) ||
                // Non-English characters or symbols
                preg_match('/[^a-z0-9\s\-\'\.]/', $clean) ||
                // Obvious spam patterns
                preg_match('/\b(buy|cheap|discount|sale|online|shop|store|amazon|ebay)\b/', $clean) ||
                // Generic/vague terms
                preg_match('/^(and|the|for|with|from|can|will|how|what|why|when)$/', $clean) ||
                // Adult content
                preg_match('/\b(adult|porn|sex|xxx)\b/', $clean)
            ) {
                continue;
            }
            
            // Additional quality checks - keep if it contains HVAC-related terms
            $hasHvacTerms = preg_match('/\b(hvac|ac|air\s+conditioner?|furnace|heat\s+pump|heating|cooling|thermostat|duct|filter|repair|service|maintenance|installation|replacement|troubleshoot|fix|problem|issue|not\s+working|broken)\b/', $clean);
            
            // If it doesn't have HVAC terms but has other indicators, still check
            $hasServiceTerms = preg_match('/\b(repair|fix|service|maintenance|installation|replacement|contractor|technician|cost|price|near\s+me)\b/', $clean);
            
            // Keep if it has HVAC terms OR service terms with reasonable length
            if ($hasHvacTerms || ($hasServiceTerms && strlen($clean) >= 8)) {
                $rawKeywords[] = $s;
            }
        }
    }
    } catch (Exception $e) {
        $errors[] = "Error processing phrase '$phrase': " . $e->getMessage();
        continue;
    }
}

// 2) People-Also-Ask style questions (optimized selection)
$peopleAlsoAsk = [];

// Check if we have time left for People Also Ask
$currentTime = microtime(true);
$elapsedTime = $currentTime - $startTime;

if ($elapsedTime < (45 - $timeoutBuffer - 10)) { // Need at least 10 seconds left
    
    // INCREASED: More question seeds for robust data (was 25, now 35)
    $priorityQuestionSeeds = [
        "why is my ac",
        "why is my furnace", 
        "why is my heat pump",
        "why won't my ac",
        "why won't my furnace",
        "how to fix my ac",
        "how to fix my furnace",
        "what to do when ac",
        "what to do when furnace",
        "how much does it cost",
        "how much to repair ac",
        "how much to replace furnace",
        "when should i replace my ac",
        "when should i service my hvac", 
        "should i repair or replace",
        "my ac won't",
        "my furnace won't",
        "my hvac is making",
        "how often should hvac",
        "is it normal for ac",
        "can i fix my own ac",
        "hvac contractor near me",
        "emergency hvac repair",
        "ac maintenance cost",
        "furnace maintenance cost",
        "hvac not working",
        "ac not blowing cold",
        "furnace not heating",
        "heat pump problems",
        "thermostat issues",
        "hvac system problems",
        "ac repair cost",
        "furnace repair cost",
        "hvac maintenance schedule",
        "energy efficient hvac"
    ];

    foreach ($priorityQuestionSeeds as $qIndex => $qSeed) {
        // Check timeout during question processing
        $currentTime = microtime(true);
        $elapsedTime = $currentTime - $startTime;
        
        if ($elapsedTime > (45 - $timeoutBuffer)) {
            $warnings[] = "Timeout during questions, stopped at question $qIndex";
            break;
        }
        
        try {
            $suggestions = googleSuggest($qSeed, $localIp);
            $apiCallCount++;
        } catch (Exception $e) {
            $errors[] = "Error processing question '$qSeed': " . $e->getMessage();
            continue;
        }

    foreach ($suggestions as $s) {
        $clean = strtolower($s);

        if (
            // Too short
            strlen($clean) < 8 ||
            // Media content filtering
            preg_match('/(song|lyrics|movie|episode|season|show|video|youtube)/', $clean) ||
            // Non-HVAC content
            preg_match('/\b(car|auto|truck|vehicle|computer|phone)\b/', $clean) ||
            // Spam patterns
            preg_match('/\b(buy|cheap|sale|shop|store)\b/', $clean)
        ) {
            continue;
        }

        // We want these as questions if possible, with better HVAC relevance
        $isQuestion = (
            preg_match('/\?$/', $clean) ||
            preg_match('/^(why|how|what|can|is|when|should|where|which|do|does|will|would)\b/', $clean)
        );
        
        // Check for HVAC relevance
        $hasHvacRelevance = preg_match('/\b(hvac|ac|air\s+conditioner?|furnace|heat\s+pump|heating|cooling|thermostat|repair|service|maintenance|filter|duct|installation|temperature|energy|bill|cost)\b/', $clean);
        
        if ($isQuestion && $hasHvacRelevance) {
            $peopleAlsoAsk[] = $s;
        }
    }
    }
} else {
    $warnings[] = "Insufficient time for People Also Ask processing";
}

// Deduplicate everything
$keywords = array_values(array_unique($rawKeywords));
$peopleAlsoAsk = array_values(array_unique($peopleAlsoAsk));

/**
 * RANK EACH KEYWORD (TREND + INTENT + CLIMATE SCORE)
 */
$ranked = [];
foreach ($keywords as $k) {
    $l = strtolower($k);
    $score = 50; // Base score

    // Urgency / problem indicators
    if (preg_match('/not|won\'t|no |stop|never|can\'t|doesn\'t/', $l)) $score += 20;

    // Repair intent
    if (preg_match('/repair|fix|service|replace|technician/', $l)) $score += 15;

    // Symptom intent
    if (preg_match('/smell|noise|leak|water|frozen|freeze|ice/', $l)) $score += 10;

    // Climate-based AC/cooling scoring
    if (preg_match('/ac|air conditioner|cooling|cool|cold air/', $l)) {
        $score += 8; // Base AC relevance
        $score += $coolingPriority; // Climate-based boost
    }
    
    // Climate-based heating scoring
    if (preg_match('/furnace|heater|heating|heat|warm/', $l)) {
        $score += 8; // Base heating relevance
        $score += $heatingPriority; // Climate-based boost
    }
    
    // Heat pump scoring (good for mixed climates)
    if (preg_match('/heat pump/', $l)) {
        $score += 8;
        // Heat pumps are especially relevant in mild to hot climates
        if (in_array($climateZone, ['Mixed', 'Mixed-Humid', 'Hot-Humid', 'Marine'])) {
            $score += 15;
        }
    }

    // Symptom keywords
    if (preg_match('/blowing|turning|starting|short cycling|keeps|compressor/', $l)) {
        $score += 5;
    }
    
    // Climate-specific boosts
    if ($climateZone === 'Very-Hot-Humid' && preg_match('/humid|moisture|mold/', $l)) {
        $score += 10; // Humidity issues common in very hot humid areas
    }
    
    if (in_array($climateZone, ['Hot-Dry', 'Cold']) && preg_match('/dry|dust|filter/', $l)) {
        $score += 8; // Dry climate air quality issues
    }

    // Ensure minimum score (don't go negative)
    $score = max($score, 5);

    $ranked[] = [
        "keyword" => $k,
        "score"   => $score,
        "climate_zone" => $climateZone, // For debugging
        "cooling_priority" => $coolingPriority,
        "heating_priority" => $heatingPriority
    ];
}

// Sort by score high → low
usort($ranked, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

/**
 * CATEGORY SORTING - Enhanced with better pattern matching
 */
$categories = [
    "cooling_issues"   => [],
    "heating_issues"   => [],
    "heat_pump"        => [],
    "thermostat"       => [],
    "noise_smell"      => [],
    "airflow"          => [],
    "leaks"            => [],
    "electrical"       => [],
    "efficiency"       => [],
    "repair"           => [],
    "troubleshooting"  => [],
    "maintenance"      => [],
    "installation"     => [],
];

foreach ($keywords as $kw) {
    $l = strtolower($kw);
    $categorized = false;

    // Cooling Issues - Enhanced patterns
    if (preg_match('/\b(ac|air\s+conditioner?|cooling|cool|cold|central\s+air|window\s+ac|portable\s+ac)\b/', $l) && 
        preg_match('/\b(not|won\'t|doesn\'t|stopped|broken|problem|issue|trouble|fail|blowing|turn|work|fix|repair)\b/', $l)) {
        $categories['cooling_issues'][] = $kw;
        $categorized = true;
    }

    // Heat Pump - Specific category
    if (preg_match('/\bheat\s+pump\b/', $l)) {
        $categories['heat_pump'][] = $kw;
        $categorized = true;
    }

    // Heating Issues - Enhanced patterns
    if (!$categorized && preg_match('/\b(furnace|heater|heating|boiler|radiant|baseboard|heat)\b/', $l) && 
        preg_match('/\b(not|won\'t|doesn\'t|stopped|broken|problem|issue|trouble|fail|blowing|turn|work|fix|repair|pilot|igniter)\b/', $l)) {
        $categories['heating_issues'][] = $kw;
        $categorized = true;
    }

    // Thermostat Issues
    if (!$categorized && preg_match('/\bthermostat\b/', $l)) {
        $categories['thermostat'][] = $kw;
        $categorized = true;
    }

    // Noise and Smell Issues
    if (!$categorized && preg_match('/\b(noise|sound|loud|quiet|smell|odor|stink|banging|rattling|whistling|grinding|squealing|humming|clicking)\b/', $l)) {
        $categories['noise_smell'][] = $kw;
        $categorized = true;
    }

    // Airflow Issues
    if (!$categorized && preg_match('/\b(airflow|air\s+flow|weak\s+air|no\s+air|barely\s+any\s+air|low\s+air|air\s+pressure|ventilation|circulation)\b/', $l)) {
        $categories['airflow'][] = $kw;
        $categorized = true;
    }

    // Water/Leak Issues
    if (!$categorized && preg_match('/\b(leak|water|drip|flood|moisture|wet|condensation|drain|humidity|mold|mildew)\b/', $l)) {
        $categories['leaks'][] = $kw;
        $categorized = true;
    }

    // Electrical Issues
    if (!$categorized && preg_match('/\b(electrical|electric|power|breaker|fuse|wiring|voltage|capacitor|contactor|motor|fan|compressor|won\'t\s+turn\s+on|no\s+power)\b/', $l)) {
        $categories['electrical'][] = $kw;
        $categorized = true;
    }

    // Energy Efficiency Issues
    if (!$categorized && preg_match('/\b(energy|efficiency|bill|bills|cost|expensive|save|saving|electric\s+bill|gas\s+bill|high\s+cost|utility)\b/', $l)) {
        $categories['efficiency'][] = $kw;
        $categorized = true;
    }

    // Installation Services
    if (!$categorized && preg_match('/\b(install|installation|replace|replacement|new|upgrade|size|sizing|duct\s+work|ductwork)\b/', $l)) {
        $categories['installation'][] = $kw;
        $categorized = true;
    }

    // Maintenance Services
    if (!$categorized && preg_match('/\b(maintenance|service|tune\s+up|cleaning|filter|annual|seasonal|inspect|check)\b/', $l)) {
        $categories['maintenance'][] = $kw;
        $categorized = true;
    }

    // Repair Services
    if (!$categorized && preg_match('/\b(repair|fix|service|company|contractor|technician|near\s+me|emergency|call)\b/', $l)) {
        $categories['repair'][] = $kw;
        $categorized = true;
    }

    // Troubleshooting - catch-all for diagnostic content
    if (!$categorized && preg_match('/\b(troubleshoot|troubleshooting|diagnose|diagnosis|guide|steps|how\s+to|why|what|check|test|diy)\b/', $l)) {
        $categories['troubleshooting'][] = $kw;
        $categorized = true;
    }
}

// Build “top trends” – top 10 ranked keywords
$topTrends = array_slice(array_map(function($row) {
    return $row['keyword'];
}, $ranked), 0, 10);

// Limit people-also-ask to top 20
$peopleAlsoAskTop = array_slice($peopleAlsoAsk, 0, 20);

// Performance metrics
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);
$cacheHitRate = $apiCallCount > 0 ? round((count($apiCache) / $apiCallCount) * 100, 1) : 0;

/**
 * FINAL OUTPUT WITH COMPREHENSIVE DEBUG DATA
 */
echo json_encode([
    "zip"              => $zip,
    "location_ip_used" => $localIp,
    "climate_zone"     => $climateZone,
    "cooling_priority" => $coolingPriority,
    "heating_priority" => $heatingPriority,
    "current_season"   => $isSummer ? 'Summer' : ($isWinter ? 'Winter' : 'Spring/Fall'),
    "keyword_count"    => count($keywords),
    "ranked_keywords"  => $ranked,
    "categories"       => $categories,
    "top_trends"       => $topTrends,
    "people_also_ask"  => $peopleAlsoAskTop,
    "performance"      => [
        "execution_time_seconds" => $executionTime,
        "api_calls_made" => $apiCallCount,
        "successful_calls" => $successfulCalls ?? 0,
        "failed_calls" => $failedCalls ?? 0,
        "phrases_processed" => count($basePhrases),
        "cache_hit_rate_percent" => $cacheHitRate,
        "cache_entries" => count($apiCache),
        "timeout_buffer_used" => $timeoutBuffer ?? 5
    ],
    "debug" => [
        "errors" => $errors,
        "warnings" => $warnings,
        "memory_usage_mb" => round(memory_get_usage() / 1024 / 1024, 2),
        "peak_memory_mb" => round(memory_get_peak_usage() / 1024 / 1024, 2)
    ]
]);
