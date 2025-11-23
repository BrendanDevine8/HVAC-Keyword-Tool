<?php
/**
 * ADVANCED KEYWORD SCORING ENGINE
 * Sophisticated algorithm considering search volume, competition, seasonality, and local factors
 */

class AdvancedKeywordScorer {
    
    /**
     * Industry competition levels by keyword type
     */
    private static $competitionLevels = [
        // High competition keywords (harder to rank)
        'high' => [
            'ac repair', 'hvac contractor', 'air conditioning repair', 'heating repair',
            'hvac service', 'furnace repair', 'ac installation', 'hvac companies'
        ],
        
        // Medium competition keywords
        'medium' => [
            'heat pump repair', 'ductless mini split', 'hvac maintenance', 'boiler repair',
            'ac tune up', 'heating maintenance', 'air duct cleaning', 'thermostat repair'
        ],
        
        // Lower competition keywords (easier to rank)
        'low' => [
            'hvac financing', 'energy audit', 'indoor air quality', 'smart thermostat',
            'geothermal system', 'radiant heating', 'hvac zoning', 'heat recovery'
        ]
    ];
    
    /**
     * Seasonal trend multipliers by month and keyword type
     */
    private static $seasonalTrends = [
        'heating' => [
            1 => 1.5, 2 => 1.4, 3 => 1.2, 4 => 0.8, 5 => 0.5, 6 => 0.3,
            7 => 0.2, 8 => 0.3, 9 => 0.6, 10 => 1.0, 11 => 1.3, 12 => 1.5
        ],
        'cooling' => [
            1 => 0.3, 2 => 0.4, 3 => 0.6, 4 => 0.9, 5 => 1.2, 6 => 1.5,
            7 => 1.5, 8 => 1.4, 9 => 1.2, 10 => 0.8, 11 => 0.5, 12 => 0.3
        ],
        'maintenance' => [
            1 => 0.8, 2 => 0.9, 3 => 1.3, 4 => 1.4, 5 => 1.2, 6 => 1.0,
            7 => 0.9, 8 => 0.9, 9 => 1.2, 10 => 1.3, 11 => 1.1, 12 => 0.8
        ]
    ];
    
    /**
     * Local market factors by state (search volume multipliers)
     */
    private static $marketFactors = [
        // High search volume states
        'CA' => 1.3, 'TX' => 1.3, 'FL' => 1.2, 'NY' => 1.2, 'IL' => 1.1,
        
        // Medium search volume states  
        'PA' => 1.0, 'OH' => 1.0, 'GA' => 1.0, 'NC' => 1.0, 'MI' => 1.0,
        'NJ' => 1.0, 'VA' => 1.0, 'WA' => 1.0, 'AZ' => 1.1, 'MA' => 1.0,
        
        // Lower search volume states (but less competition)
        'WY' => 0.7, 'VT' => 0.7, 'ND' => 0.7, 'AK' => 0.6, 'DE' => 0.8,
        'MT' => 0.8, 'RI' => 0.8, 'SD' => 0.8, 'NH' => 0.8, 'ME' => 0.8
    ];
    
    /**
     * Calculate advanced keyword score
     */
    public static function calculateAdvancedScore($keyword, $locationData, $climateAnalysis) {
        $baseScore = 50; // Starting score
        
        // 1. Search Volume Score (35% weight)
        $searchVolumeScore = self::calculateSearchVolumeScore($keyword, $locationData);
        
        // 2. Competition Score (25% weight)  
        $competitionScore = self::calculateCompetitionScore($keyword);
        
        // 3. Seasonal Relevance Score (20% weight)
        $seasonalScore = self::calculateSeasonalScore($keyword);
        
        // 4. Local Relevance Score (15% weight)
        $localScore = self::calculateLocalRelevanceScore($keyword, $locationData);
        
        // 5. Climate Appropriateness Score (5% weight)
        $climateScore = self::calculateClimateScore($keyword, $climateAnalysis);
        
        // Weighted final score
        $finalScore = (
            ($searchVolumeScore * 0.35) +
            ($competitionScore * 0.25) + 
            ($seasonalScore * 0.20) +
            ($localScore * 0.15) +
            ($climateScore * 0.05)
        );
        
        // Apply modifiers
        $finalScore = self::applyModifiers($finalScore, $keyword, $locationData, $climateAnalysis);
        
        return [
            'final_score' => round($finalScore, 1),
            'breakdown' => [
                'search_volume' => round($searchVolumeScore, 1),
                'competition' => round($competitionScore, 1),
                'seasonal' => round($seasonalScore, 1),
                'local_relevance' => round($localScore, 1),
                'climate_fit' => round($climateScore, 1)
            ],
            'difficulty' => self::getDifficultyRating($competitionScore),
            'opportunity_score' => self::calculateOpportunityScore($searchVolumeScore, $competitionScore)
        ];
    }
    
    /**
     * Calculate search volume score based on keyword popularity and local factors
     */
    private static function calculateSearchVolumeScore($keyword, $locationData) {
        $baseVolume = 50;
        
        // High-volume keyword patterns
        $highVolumeTerms = ['repair', 'service', 'installation', 'replacement', 'cost', 'near me'];
        $mediumVolumeTerms = ['maintenance', 'tune up', 'inspection', 'cleaning', 'emergency'];
        $lowVolumeTerms = ['financing', 'warranty', 'consultation', 'estimate', 'quote'];
        
        $volumeScore = 30; // Base volume
        
        foreach ($highVolumeTerms as $term) {
            if (stripos($keyword, $term) !== false) {
                $volumeScore += 25;
                break;
            }
        }
        
        foreach ($mediumVolumeTerms as $term) {
            if (stripos($keyword, $term) !== false) {
                $volumeScore += 15;
                break;
            }
        }
        
        foreach ($lowVolumeTerms as $term) {
            if (stripos($keyword, $term) !== false) {
                $volumeScore += 5;
                break;
            }
        }
        
        // Location-specific adjustments
        $state = $locationData['state_code'] ?? '';
        $marketMultiplier = self::$marketFactors[$state] ?? 1.0;
        $volumeScore *= $marketMultiplier;
        
        // Population density bonus
        $population = (float)($locationData['population'] ?? 10000);
        $densityBonus = min($population / 100000 * 10, 15);
        $volumeScore += $densityBonus;
        
        return min($volumeScore, 100);
    }
    
    /**
     * Calculate competition score (lower competition = higher score)
     */
    private static function calculateCompetitionScore($keyword) {
        $competitionScore = 70; // Default medium competition
        
        // Check competition level
        foreach (self::$competitionLevels['high'] as $highCompTerm) {
            if (stripos($keyword, $highCompTerm) !== false) {
                $competitionScore = 40; // High competition = lower score
                break;
            }
        }
        
        foreach (self::$competitionLevels['medium'] as $medCompTerm) {
            if (stripos($keyword, $medCompTerm) !== false) {
                $competitionScore = 65; // Medium competition
                break;
            }
        }
        
        foreach (self::$competitionLevels['low'] as $lowCompTerm) {
            if (stripos($keyword, $lowCompTerm) !== false) {
                $competitionScore = 85; // Low competition = higher score
                break;
            }
        }
        
        // Long-tail keywords (4+ words) typically have less competition
        $wordCount = str_word_count($keyword);
        if ($wordCount >= 4) {
            $competitionScore += 10;
        } elseif ($wordCount >= 3) {
            $competitionScore += 5;
        }
        
        // Local modifiers reduce competition
        if (preg_match('/\b(near me|in [A-Z]{2}|[A-Za-z]+ [A-Z]{2})\b/', $keyword)) {
            $competitionScore += 15;
        }
        
        return min($competitionScore, 100);
    }
    
    /**
     * Calculate seasonal relevance score
     */
    private static function calculateSeasonalScore($keyword) {
        $currentMonth = (int)date('n');
        $baseSeasonalScore = 50;
        
        // Determine keyword category
        $keywordType = self::categorizeKeyword($keyword);
        
        // Apply seasonal trend
        if (isset(self::$seasonalTrends[$keywordType][$currentMonth])) {
            $seasonalMultiplier = self::$seasonalTrends[$keywordType][$currentMonth];
            $baseSeasonalScore *= $seasonalMultiplier;
        }
        
        // Urgent/emergency keywords get season boost during peak times
        if (stripos($keyword, 'emergency') !== false || stripos($keyword, 'urgent') !== false) {
            if (($keywordType === 'heating' && $currentMonth <= 3) || 
                ($keywordType === 'heating' && $currentMonth >= 11) ||
                ($keywordType === 'cooling' && $currentMonth >= 6 && $currentMonth <= 8)) {
                $baseSeasonalScore += 20;
            }
        }
        
        return min($baseSeasonalScore, 100);
    }
    
    /**
     * Calculate local relevance score
     */
    private static function calculateLocalRelevanceScore($keyword, $locationData) {
        $localScore = 50;
        
        $city = strtolower($locationData['city'] ?? '');
        $state = strtolower($locationData['state'] ?? '');
        $stateCode = strtolower($locationData['state_code'] ?? '');
        
        // Check for location mentions in keyword
        if (stripos($keyword, $city) !== false) {
            $localScore += 30;
        }
        
        if (stripos($keyword, $state) !== false || stripos($keyword, $stateCode) !== false) {
            $localScore += 20;
        }
        
        if (stripos($keyword, 'near me') !== false) {
            $localScore += 25;
        }
        
        // Metro area bonus
        $metro = $locationData['metro_area'] ?? '';
        if (!empty($metro) && stripos($keyword, strtolower($metro)) !== false) {
            $localScore += 15;
        }
        
        return min($localScore, 100);
    }
    
    /**
     * Calculate climate appropriateness score
     */
    private static function calculateClimateScore($keyword, $climateAnalysis) {
        $climateScore = 50;
        
        $primaryZone = $climateAnalysis['primary_zone'] ?? '';
        $currentPriorities = $climateAnalysis['current_priorities'] ?? [];
        
        // Check if keyword matches climate priorities
        foreach ($currentPriorities as $priority => $weight) {
            if (stripos($keyword, str_replace('_', ' ', $priority)) !== false) {
                $climateScore += ($weight / 100) * 30;
                break;
            }
        }
        
        // Zone-specific keyword bonuses
        if (strpos($primaryZone, 'Desert') !== false && 
            (stripos($keyword, 'cooling') !== false || stripos($keyword, 'ac') !== false)) {
            $climateScore += 20;
        }
        
        if (strpos($primaryZone, 'Arctic') !== false && 
            (stripos($keyword, 'heating') !== false || stripos($keyword, 'furnace') !== false)) {
            $climateScore += 20;
        }
        
        return min($climateScore, 100);
    }
    
    /**
     * Apply final score modifiers
     */
    private static function applyModifiers($baseScore, $keyword, $locationData, $climateAnalysis) {
        $modifiedScore = $baseScore;
        
        // Commercial intent boost
        if (preg_match('/\b(cost|price|estimate|quote|cheap|affordable)\b/i', $keyword)) {
            $modifiedScore *= 1.1;
        }
        
        // Brand exclusion penalty
        if (preg_match('/\b(carrier|trane|lennox|rheem|goodman)\b/i', $keyword)) {
            $modifiedScore *= 0.9;
        }
        
        // Question keywords boost (easier to rank)
        if (preg_match('/^(how|what|why|when|where|which)\b/i', $keyword)) {
            $modifiedScore *= 1.05;
        }
        
        // Very long keywords penalty
        if (str_word_count($keyword) > 6) {
            $modifiedScore *= 0.95;
        }
        
        return $modifiedScore;
    }
    
    /**
     * Categorize keyword for seasonal analysis
     */
    private static function categorizeKeyword($keyword) {
        if (preg_match('/\b(heat|heating|furnace|boiler|warm)\b/i', $keyword)) {
            return 'heating';
        }
        
        if (preg_match('/\b(cool|cooling|ac|air condition|cold)\b/i', $keyword)) {
            return 'cooling';
        }
        
        if (preg_match('/\b(maintenance|service|tune|clean|inspect)\b/i', $keyword)) {
            return 'maintenance';
        }
        
        return 'general';
    }
    
    /**
     * Get difficulty rating
     */
    private static function getDifficultyRating($competitionScore) {
        if ($competitionScore >= 80) return 'Easy';
        if ($competitionScore >= 65) return 'Medium';
        if ($competitionScore >= 45) return 'Hard';
        return 'Very Hard';
    }
    
    /**
     * Calculate opportunity score (high volume + low competition = high opportunity)
     */
    private static function calculateOpportunityScore($volumeScore, $competitionScore) {
        return round(($volumeScore + $competitionScore) / 2, 1);
    }
    
    /**
     * Batch score multiple keywords efficiently
     */
    public static function batchScoreKeywords($keywords, $locationData, $climateAnalysis) {
        $scoredKeywords = [];
        
        foreach ($keywords as $keyword) {
            $keywordText = is_array($keyword) ? $keyword['keyword'] : $keyword;
            $scoringResult = self::calculateAdvancedScore($keywordText, $locationData, $climateAnalysis);
            
            $scoredKeywords[] = array_merge(
                is_array($keyword) ? $keyword : ['keyword' => $keywordText],
                [
                    'advanced_score' => $scoringResult['final_score'],
                    'score_breakdown' => $scoringResult['breakdown'],
                    'difficulty' => $scoringResult['difficulty'],
                    'opportunity_score' => $scoringResult['opportunity_score']
                ]
            );
        }
        
        // Sort by opportunity score (high volume + low competition)
        usort($scoredKeywords, function($a, $b) {
            return $b['opportunity_score'] <=> $a['opportunity_score'];
        });
        
        return $scoredKeywords;
    }
    
    /**
     * Get scoring insights and recommendations
     */
    public static function getKeywordInsights($scoredKeywords) {
        $insights = [
            'total_keywords' => count($scoredKeywords),
            'avg_opportunity_score' => 0,
            'difficulty_distribution' => ['Easy' => 0, 'Medium' => 0, 'Hard' => 0, 'Very Hard' => 0],
            'top_opportunities' => [],
            'low_competition_gems' => [],
            'seasonal_winners' => []
        ];
        
        $totalOpportunity = 0;
        
        foreach ($scoredKeywords as $keyword) {
            $totalOpportunity += $keyword['opportunity_score'];
            $insights['difficulty_distribution'][$keyword['difficulty']]++;
            
            // Top opportunities (high overall score)
            if ($keyword['opportunity_score'] >= 75) {
                $insights['top_opportunities'][] = $keyword;
            }
            
            // Low competition gems (high competition score)
            if ($keyword['score_breakdown']['competition'] >= 75) {
                $insights['low_competition_gems'][] = $keyword;
            }
            
            // Seasonal winners (high seasonal score)
            if ($keyword['score_breakdown']['seasonal'] >= 75) {
                $insights['seasonal_winners'][] = $keyword;
            }
        }
        
        $insights['avg_opportunity_score'] = round($totalOpportunity / count($scoredKeywords), 1);
        
        // Limit arrays to top 10
        $insights['top_opportunities'] = array_slice($insights['top_opportunities'], 0, 10);
        $insights['low_competition_gems'] = array_slice($insights['low_competition_gems'], 0, 10);
        $insights['seasonal_winners'] = array_slice($insights['seasonal_winners'], 0, 10);
        
        return $insights;
    }
}

?>