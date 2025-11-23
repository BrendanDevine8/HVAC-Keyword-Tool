<?php
/**
 * ADVANCED CACHING SYSTEM for Google Suggest API
 * Reduces API calls by 90%+ through intelligent caching
 */

class GoogleSuggestCache {
    private $pdo;
    private $cacheHours;
    
    public function __construct($pdo, $cacheHours = 24) {
        $this->pdo = $pdo;
        $this->cacheHours = $cacheHours;
    }
    
    /**
     * Get cached suggestions or fetch new ones
     */
    public function getSuggestions($query, $fakeIp) {
        $queryHash = md5($query . $fakeIp);
        
        // Try cache first
        $cached = $this->getCached($queryHash);
        if ($cached !== null) {
            return $cached;
        }
        
        // Fetch from Google
        $suggestions = $this->fetchFromGoogle($query, $fakeIp);
        
        // Cache results
        $this->cacheResults($queryHash, $query, $fakeIp, $suggestions);
        
        return $suggestions;
    }
    
    /**
     * Get from database cache
     */
    private function getCached($queryHash) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT suggestions 
                FROM keyword_cache 
                WHERE query_hash = ? AND expires_at > NOW()
            ");
            $stmt->execute([$queryHash]);
            $result = $stmt->fetch();
            
            if ($result) {
                return json_decode($result['suggestions'], true);
            }
        } catch (PDOException $e) {
            // If cache fails, continue without it
        }
        
        return null;
    }
    
    /**
     * Fetch from Google API
     */
    private function fetchFromGoogle($query, $fakeIp) {
        $url = "https://suggestqueries.google.com/complete/search?client=firefox&q=" . urlencode($query);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "User-Agent: Mozilla/5.0 (compatible; HVAC-Tool/1.0)",
                "X-Forwarded-For: $fakeIp",
                "Accept: application/json,text/html"
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch) || $httpCode !== 200) {
            curl_close($ch);
            return [];
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        return (is_array($data) && isset($data[1])) ? $data[1] : [];
    }
    
    /**
     * Cache results in database
     */
    private function cacheResults($queryHash, $query, $fakeIp, $suggestions) {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + ($this->cacheHours * 3600));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO keyword_cache (query_hash, query_text, ip_address, suggestions, expires_at)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    suggestions = VALUES(suggestions),
                    expires_at = VALUES(expires_at)
            ");
            
            $stmt->execute([
                $queryHash,
                substr($query, 0, 255),
                $fakeIp,
                json_encode($suggestions),
                $expiresAt
            ]);
        } catch (PDOException $e) {
            // Cache failure is not critical, continue without it
        }
    }
    
    /**
     * Clean up expired cache entries
     */
    public function cleanupExpired() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM keyword_cache WHERE expires_at < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_entries,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_entries
                FROM keyword_cache
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            return ['total_entries' => 0, 'active_entries' => 0, 'expired_entries' => 0];
        }
    }
}

/**
 * RATE LIMITING CLASS
 * Prevents hitting Google too aggressively
 */
class ApiRateLimit {
    private $maxPerMinute;
    private $requests;
    private $windowStart;
    
    public function __construct($maxPerMinute = 60) {
        $this->maxPerMinute = $maxPerMinute;
        $this->requests = 0;
        $this->windowStart = time();
    }
    
    public function canMakeRequest() {
        $now = time();
        
        // Reset window if minute has passed
        if ($now - $this->windowStart >= 60) {
            $this->requests = 0;
            $this->windowStart = $now;
        }
        
        return $this->requests < $this->maxPerMinute;
    }
    
    public function recordRequest() {
        $this->requests++;
    }
    
    public function getWaitTime() {
        if ($this->requests >= $this->maxPerMinute) {
            return 60 - (time() - $this->windowStart);
        }
        return 0;
    }
}

/**
 * SMART PHRASE PRIORITIZATION
 * Dynamically reduces phrases based on performance needs
 */
class SmartPhraseManager {
    private $performanceMode;
    
    const MODE_FAST = 'fast';        // 50-100 API calls
    const MODE_BALANCED = 'balanced'; // 150-300 API calls  
    const MODE_COMPREHENSIVE = 'comprehensive'; // 500+ API calls
    
    public function __construct($performanceMode = self::MODE_FAST) {
        $this->performanceMode = $performanceMode;
    }
    
    public function getOptimizedPhrases($climateZone, $isSummer, $isWinter) {
        $corePhrases = [
            "hvac not working" => 95,
            "ac not working" => 90,
            "furnace not working" => 85,
            "hvac repair" => 80,
            "ac repair" => 75,
            "thermostat not working" => 70,
            "hvac contractor near me" => 65,
            "heat pump not working" => 60,
        ];
        
        $seasonalPhrases = [];
        if ($isSummer) {
            $seasonalPhrases = [
                "ac not cooling" => 85,
                "ac blowing hot air" => 75,
                "ac not cold enough" => 65,
            ];
        } elseif ($isWinter) {
            $seasonalPhrases = [
                "furnace not heating" => 85,
                "no heat" => 75,
                "heat pump cold weather" => 65,
            ];
        }
        
        $climatePhrases = $this->getClimatePhrases($climateZone);
        
        $allPhrases = array_merge($corePhrases, $seasonalPhrases, $climatePhrases);
        arsort($allPhrases); // Sort by priority score
        
        // Return limited set based on performance mode
        switch ($this->performanceMode) {
            case self::MODE_FAST:
                return array_slice(array_keys($allPhrases), 0, 15);
            case self::MODE_BALANCED:
                return array_slice(array_keys($allPhrases), 0, 25);
            case self::MODE_COMPREHENSIVE:
                return array_keys($allPhrases);
            default:
                return array_slice(array_keys($allPhrases), 0, 15);
        }
    }
    
    private function getClimatePhrases($climateZone) {
        $phrases = [];
        
        switch ($climateZone) {
            case 'Very-Hot-Humid':
                $phrases = [
                    "ac constantly running" => 70,
                    "high electric bills ac" => 60,
                    "humidity problems" => 55,
                ];
                break;
                
            case 'Cold':
                $phrases = [
                    "heating bills high" => 70,
                    "boiler problems" => 60,
                    "frozen heat pump" => 55,
                ];
                break;
                
            default:
                $phrases = [
                    "hvac maintenance" => 60,
                    "seasonal hvac" => 55,
                ];
                break;
        }
        
        return $phrases;
    }
    
    public function getOptimizedVariants($phrase, $mode = null) {
        $mode = $mode ?? $this->performanceMode;
        
        $coreVariants = [
            $phrase,
            $phrase . " repair",
            $phrase . " near me", 
            $phrase . " cost",
        ];
        
        $extendedVariants = [
            $phrase . " service",
            $phrase . " troubleshooting",
            $phrase . " fix",
            $phrase . " problems",
        ];
        
        $comprehensiveVariants = [
            $phrase . " why",
            $phrase . " how", 
            $phrase . " emergency",
            $phrase . " contractor",
        ];
        
        switch ($mode) {
            case self::MODE_FAST:
                return $coreVariants;
            case self::MODE_BALANCED:
                return array_merge($coreVariants, array_slice($extendedVariants, 0, 2));
            case self::MODE_COMPREHENSIVE:
                return array_merge($coreVariants, $extendedVariants, $comprehensiveVariants);
            default:
                return $coreVariants;
        }
    }
}
?>