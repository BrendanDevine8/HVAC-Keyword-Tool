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

// Determine season for basic weighting
$month = (int) date('n'); // 1–12
$isSummer = in_array($month, [5,6,7,8,9]);   // May–Sept
$isWinter = in_array($month, [11,12,1,2,3]); // Nov–Mar

/**
 * BASE HVAC PHRASES (core problems)
 */
$basePhrases = [
    "ac not working",
    "ac not blowing cold air",
    "ac repair",
    "ac making noise",
    "ac smells",
    "ac leaking water",
    "ac frozen",
    "weak airflow ac",
    "furnace not heating",
    "furnace repair",
    "furnace making noise",
    "furnace troubleshooting",
    "heat pump not cooling",
    "heat pump not heating",
    "heat pump freezing",
    "hvac troubleshooting",
    "hvac repair",
    "thermostat not working",
];

// Seasonal emphasis – add more cooling or heating phrases
if ($isSummer) {
    $basePhrases = array_merge($basePhrases, [
        "ac not cooling upstairs",
        "ac running but not cooling",
        "ac keeps freezing up",
        "ac short cycling",
    ]);
}
if ($isWinter) {
    $basePhrases = array_merge($basePhrases, [
        "furnace blowing cold air",
        "furnace keeps shutting off",
        "no heat coming from vents",
        "heat pump not heating in cold weather",
    ]);
}

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
 * RANK EACH KEYWORD (TREND + INTENT SCORE)
 */
$ranked = [];
foreach ($keywords as $k) {
    $l = strtolower($k);
    $score = 50;

    // Urgency / problem indicators
    if (preg_match('/not|won\'t|no |stop|never|can\'t|doesn\'t/', $l)) $score += 20;

    // Repair intent
    if (preg_match('/repair|fix|service|replace|technician/', $l)) $score += 15;

    // Symptom intent
    if (preg_match('/smell|noise|leak|water|frozen|freeze|ice/', $l)) $score += 10;

    // AC / cooling vs heating weighting
    if (preg_match('/ac|air conditioner/', $l)) {
        $score += 8;
        if ($isSummer) $score += 10; // more weight in hot months
    }
    if (preg_match('/furnace|heater/', $l)) {
        $score += 8;
        if ($isWinter) $score += 10; // more weight in cold months
    }
    if (preg_match('/heat pump/', $l)) $score += 8;

    // Symptom keywords
    if (preg_match('/blowing|cold|warm|weak|airflow|turning|starting|short cycling|keeps/', $l)) {
        $score += 5;
    }

    $ranked[] = [
        "keyword" => $k,
        "score"   => $score
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
    "keyword_count"    => count($keywords),
    "ranked_keywords"  => $ranked,
    "categories"       => $categories,
    "top_trends"       => $topTrends,
    "people_also_ask"  => $peopleAlsoAskTop
]);
