<?php

require_once __DIR__ . "/../config.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    global $pdo;
    
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
    // Universal HVAC issues
    "hvac troubleshooting",
    "hvac repair",
    "thermostat not working",
    
    // AC/Cooling issues (prioritized by climate)
    "ac not working",
    "ac not blowing cold air",
    "ac repair",
    "ac making noise",
    "ac smells",
    "ac leaking water",
    "ac frozen",
    "weak airflow ac",
    "ac not cooling upstairs",
    "ac running but not cooling",
    "ac keeps freezing up",
    "ac short cycling",
    
    // Heating issues (prioritized by climate)
    "furnace not heating",
    "furnace repair",
    "furnace making noise", 
    "furnace troubleshooting",
    "furnace blowing cold air",
    "furnace keeps shutting off",
    "no heat coming from vents",
    
    // Heat pump (common in mild/hot climates)
    "heat pump not cooling",
    "heat pump not heating",
    "heat pump freezing",
    "heat pump not heating in cold weather",
];

// Climate-specific phrase emphasis
$climatePhrases = [];

// Hot climate areas - emphasize cooling
if (in_array($climateZone, ['Very-Hot-Humid', 'Hot-Humid', 'Hot-Dry'])) {
    $climatePhrases = array_merge($climatePhrases, [
        "ac not cold enough",
        "ac runs all day",
        "high electric bills ac",
        "ac compressor problems", 
        "central air not working",
        "hvac cooling issues",
        "ac maintenance",
    ]);
}

// Cold climate areas - emphasize heating  
if (in_array($climateZone, ['Cold', 'Mixed-Cold'])) {
    $climatePhrases = array_merge($climatePhrases, [
        "furnace pilot light",
        "boiler problems",
        "radiant heat issues",
        "heating system maintenance",
        "furnace filter replacement",
        "high gas bills heating",
    ]);
}

// Mixed climates - balance both
if (in_array($climateZone, ['Mixed', 'Mixed-Humid', 'Marine'])) {
    $climatePhrases = array_merge($climatePhrases, [
        "heat pump efficiency",
        "hvac system replacement", 
        "seasonal hvac maintenance",
        "duct cleaning",
    ]);
}

$basePhrases = array_merge($basePhrases, $climatePhrases);

/**
 * EXPAND A PHRASE INTO MANY QUERY VARIANTS
 * - adds long-tail modifiers
 * - letter-wheel (a..z)
 * - "why/how" style
 */
function expandQueries($phrase) {
    $queries = [];

    // Base
    $queries[] = $phrase;
    $queries[] = $phrase . " ";
    $queries[] = $phrase . " fix";
    $queries[] = $phrase . " repair";
    $queries[] = $phrase . " troubleshooting";
    $queries[] = $phrase . " why";
    $queries[] = $phrase . " symptoms";
    $queries[] = $phrase . " causes";
    $queries[] = $phrase . " near me";

    // Letter wheel – ac not working a, b, c...
    foreach (range('a', 'z') as $letter) {
        $queries[] = $phrase . " " . $letter;
    }

    return $queries;
}

/**
 * QUESTION-STYLE SEEDS (People-also-ask style)
 * These are used to build "people_also_ask" suggestions.
 */
$questionSeeds = [
    "why is my ac",
    "why is my furnace",
    "why is my heat pump",
    "how to tell if my ac",
    "how to tell if my furnace",
    "how to tell if my heat pump",
    "what to do when ac",
    "what to do when furnace",
    "is it normal for ac",
    "can i run my heat pump",
    "why does my hvac",
];

/**
 * GOOGLE AUTOCOMPLETE REQUEST
 */
function googleSuggest($query, $fakeIp) {
    $url = "https://suggestqueries.google.com/complete/search?client=firefox&q=" . urlencode($query);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            "User-Agent: Mozilla/5.0",
            "X-Forwarded-For: $fakeIp",
            "Accept: application/json,text/html"
        ]
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    return (is_array($data) && isset($data[1])) ? $data[1] : [];
}

/**
 * MASTER KEYWORD COLLECTION
 */
$rawKeywords = [];

// 1) Problem / service intent from base phrases
foreach ($basePhrases as $phrase) {
    $expandedQueries = expandQueries($phrase);

    // keep it from exploding too hard: cap to first 12 variants per phrase
    $expandedQueries = array_slice($expandedQueries, 0, 12);

    foreach ($expandedQueries as $q) {
        $suggestions = googleSuggest($q, $localIp);

        foreach ($suggestions as $s) {
            $clean = strtolower($s);

            // FILTER JUNK
            if (
                preg_match('/(show|episode|season|series)/', $clean) || // tv noise
                preg_match('/\b[0-9]{6,}\b/', $clean) ||                // long numbers/model numbers
                preg_match('/car|auto|truck|jeep/', $clean) ||         // auto AC noise
                strlen($clean) < 4
            ) {
                continue;
            }

            $rawKeywords[] = $s;
        }
    }
}

// 2) People-Also-Ask style questions
$peopleAlsoAsk = [];
foreach ($questionSeeds as $qSeed) {
    $suggestions = googleSuggest($qSeed, $localIp);

    foreach ($suggestions as $s) {
        $clean = strtolower($s);

        if (
            strlen($clean) < 8 ||
            preg_match('/(song|lyrics|movie|episode|season)/', $clean)
        ) {
            continue;
        }

        // We want these as questions if possible
        if (
            preg_match('/\?$/', $clean) ||
            preg_match('/^(why|how|what|can|is|when|should)\b/', $clean)
        ) {
            $peopleAlsoAsk[] = $s;
        }
    }
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
 * CATEGORY SORTING
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
];

foreach ($keywords as $kw) {
    $l = strtolower($kw);

    if (preg_match('/ac|air conditioner/', $l) && preg_match('/not|blowing|cold|warm|cool|turn/', $l)) {
        $categories['cooling_issues'][] = $kw;
    }

    if (preg_match('/furnace|heater/', $l)) {
        $categories['heating_issues'][] = $kw;
    }

    if (preg_match('/heat pump/', $l)) {
        $categories['heat_pump'][] = $kw;
    }

    if (preg_match('/thermostat/', $l)) {
        $categories['thermostat'][] = $kw;
    }

    if (preg_match('/noise|smell|smelly|banging|rattling|whistling/', $l)) {
        $categories['noise_smell'][] = $kw;
    }

    if (preg_match('/airflow|weak air|barely any air|low air/', $l)) {
        $categories['airflow'][] = $kw;
    }

    if (preg_match('/leak|water|drip|flood/', $l)) {
        $categories['leaks'][] = $kw;
    }

    if (preg_match('/breaker|power|won\'t turn on|no power|electrical/', $l)) {
        $categories['electrical'][] = $kw;
    }

    if (preg_match('/energy|efficiency|bill|bills/', $l)) {
        $categories['efficiency'][] = $kw;
    }

    if (preg_match('/repair|service|fix|company|near me/', $l)) {
        $categories['repair'][] = $kw;
    }

    if (preg_match('/troubleshoot|guide|checklist|flow chart|steps/', $l)) {
        $categories['troubleshooting'][] = $kw;
    }
}

// Build “top trends” – top 10 ranked keywords
$topTrends = array_slice(array_map(function($row) {
    return $row['keyword'];
}, $ranked), 0, 10);

// Limit people-also-ask to top 20
$peopleAlsoAskTop = array_slice($peopleAlsoAsk, 0, 20);

/**
 * FINAL OUTPUT
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
    "people_also_ask"  => $peopleAlsoAskTop
]);
