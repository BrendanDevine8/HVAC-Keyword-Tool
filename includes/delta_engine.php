<?php
/**
 * Delta Engine for Blog Post Version History
 * Implements character-level diff generation and application for efficient storage
 */

class DeltaEngine {
    
    /**
     * Generate a character-level delta between two strings
     * Uses Myers algorithm for efficient diff computation
     */
    public static function generateDelta($oldText, $newText) {
        $oldChars = str_split($oldText);
        $newChars = str_split($newText);
        
        $diff = self::myersDiff($oldChars, $newChars);
        
        // Convert to compact delta format
        $delta = [
            'operations' => [],
            'old_length' => strlen($oldText),
            'new_length' => strlen($newText),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $position = 0;
        
        foreach ($diff as $operation) {
            switch ($operation['type']) {
                case 'equal':
                    // Skip equal sections, just advance position
                    $position += count($operation['chars']);
                    break;
                    
                case 'delete':
                    $delta['operations'][] = [
                        'type' => 'delete',
                        'position' => $position,
                        'length' => count($operation['chars'])
                    ];
                    break;
                    
                case 'insert':
                    $delta['operations'][] = [
                        'type' => 'insert', 
                        'position' => $position,
                        'text' => implode('', $operation['chars'])
                    ];
                    break;
            }
        }
        
        return $delta;
    }
    
    /**
     * Apply a delta to reconstruct text
     */
    public static function applyDelta($originalText, $delta) {
        if (!$delta || !isset($delta['operations'])) {
            return $originalText;
        }
        
        $text = $originalText;
        $offset = 0;
        
        // Apply operations in order
        foreach ($delta['operations'] as $op) {
            $position = $op['position'] + $offset;
            
            switch ($op['type']) {
                case 'delete':
                    $text = substr($text, 0, $position) . substr($text, $position + $op['length']);
                    $offset -= $op['length'];
                    break;
                    
                case 'insert':
                    $text = substr($text, 0, $position) . $op['text'] . substr($text, $position);
                    $offset += strlen($op['text']);
                    break;
            }
        }
        
        return $text;
    }
    
    /**
     * Simplified Myers diff algorithm
     * Returns array of operations: equal, delete, insert
     */
    private static function myersDiff($a, $b) {
        $n = count($a);
        $m = count($b);
        
        if ($n === 0) {
            return [['type' => 'insert', 'chars' => $b]];
        }
        if ($m === 0) {
            return [['type' => 'delete', 'chars' => $a]];
        }
        
        // Build edit distance matrix
        $dp = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        
        // Initialize first row and column
        for ($i = 0; $i <= $n; $i++) {
            $dp[$i][0] = $i;
        }
        for ($j = 0; $j <= $m; $j++) {
            $dp[0][$j] = $j;
        }
        
        // Fill the matrix
        for ($i = 1; $i <= $n; $i++) {
            for ($j = 1; $j <= $m; $j++) {
                if ($a[$i-1] === $b[$j-1]) {
                    $dp[$i][$j] = $dp[$i-1][$j-1]; // No operation needed
                } else {
                    $dp[$i][$j] = 1 + min(
                        $dp[$i-1][$j],    // Delete
                        $dp[$i][$j-1],    // Insert
                        $dp[$i-1][$j-1]   // Substitute
                    );
                }
            }
        }
        
        // Backtrack to build operations
        $operations = [];
        $i = $n;
        $j = $m;
        
        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $a[$i-1] === $b[$j-1]) {
                // Equal - collect consecutive equal chars
                $equalChars = [];
                while ($i > 0 && $j > 0 && $a[$i-1] === $b[$j-1]) {
                    array_unshift($equalChars, $a[$i-1]);
                    $i--;
                    $j--;
                }
                if (!empty($equalChars)) {
                    array_unshift($operations, ['type' => 'equal', 'chars' => $equalChars]);
                }
            } elseif ($i > 0 && ($j === 0 || $dp[$i-1][$j] <= $dp[$i][$j-1])) {
                // Delete
                $deleteChars = [];
                while ($i > 0 && ($j === 0 || $dp[$i-1][$j] <= $dp[$i][$j-1])) {
                    if ($j > 0 && $dp[$i-1][$j-1] < $dp[$i-1][$j]) {
                        break; // This is actually a substitute, handle it next
                    }
                    array_unshift($deleteChars, $a[$i-1]);
                    $i--;
                }
                if (!empty($deleteChars)) {
                    array_unshift($operations, ['type' => 'delete', 'chars' => $deleteChars]);
                }
            } else {
                // Insert
                $insertChars = [];
                while ($j > 0 && ($i === 0 || $dp[$i][$j-1] < $dp[$i-1][$j])) {
                    array_unshift($insertChars, $b[$j-1]);
                    $j--;
                }
                if (!empty($insertChars)) {
                    array_unshift($operations, ['type' => 'insert', 'chars' => $insertChars]);
                }
            }
        }
        
        return $operations;
    }
    
    /**
     * Get human-readable summary of changes
     */
    public static function getChangeSummary($delta) {
        if (!$delta || !isset($delta['operations'])) {
            return 'No changes';
        }
        
        $inserts = 0;
        $deletes = 0;
        $insertChars = 0;
        $deleteChars = 0;
        
        foreach ($delta['operations'] as $op) {
            if ($op['type'] === 'insert') {
                $inserts++;
                $insertChars += strlen($op['text']);
            } elseif ($op['type'] === 'delete') {
                $deletes++;
                $deleteChars += $op['length'];
            }
        }
        
        $summary = [];
        if ($insertChars > 0) {
            $summary[] = "+{$insertChars} chars";
        }
        if ($deleteChars > 0) {
            $summary[] = "-{$deleteChars} chars";
        }
        
        if (empty($summary)) {
            return 'No changes';
        }
        
        $result = implode(', ', $summary);
        
        // Add word count estimate
        $wordDelta = $delta['word_count_delta'] ?? 0;
        if ($wordDelta !== 0) {
            $result .= sprintf(' (~%+d words)', $wordDelta);
        }
        
        return $result;
    }
    
    /**
     * Calculate storage efficiency of delta vs full content
     */
    public static function getCompressionRatio($originalSize, $deltaSize) {
        if ($originalSize === 0) return 0;
        return round((1 - ($deltaSize / $originalSize)) * 100, 1);
    }
    
    /**
     * Validate that a delta can be successfully applied
     */
    public static function validateDelta($originalText, $delta, $expectedResult = null) {
        try {
            $result = self::applyDelta($originalText, $delta);
            
            if ($expectedResult !== null) {
                return $result === $expectedResult;
            }
            
            // Basic validation - result should be different from original (unless no changes)
            $hasChanges = !empty($delta['operations']);
            return $hasChanges ? ($result !== $originalText) : ($result === $originalText);
            
        } catch (Exception $e) {
            return false;
        }
    }
}
?>