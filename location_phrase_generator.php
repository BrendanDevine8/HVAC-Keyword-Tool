<?php
/**
 * LOCATION-AWARE KEYWORD PHRASE GENERATOR
 * Generates climate and location-specific HVAC phrases using actual ZIP data
 */

function generateLocationSpecificPhrases($locationData, $climateZone) {
    $actualCity = $locationData['city'];
    $actualState = $locationData['state']; 
    $actualStateCode = $locationData['state_code'];
    $actualMetro = $locationData['metro_area'];
    
    // Clean city name - remove common suffixes
    $cleanCity = preg_replace('/\\s+(city|town|village|township)$/i', '', $actualCity);
    
    $climatePhrases = [];
    
    // LOCATION-SPECIFIC PHRASES BY CLIMATE ZONE
    switch ($climateZone) {
        case 'Hot-Dry':
        case 'Very Hot-Dry':
            $climatePhrases = [
                // AC and cooling focus with actual location
                "ac repair {$cleanCity}",
                "air conditioning {$cleanCity} {$actualStateCode}",
                "cooling system repair {$actualState}",
                \"ac service {$cleanCity}\",
                \"ac maintenance {$actualState}\",
                \"air conditioning repair {$cleanCity}\",
                \"hvac repair {$cleanCity}\",
                \"cooling costs {$actualState}\",
                \"ac not cooling {$actualState}\",
                \"desert hvac {$cleanCity}\",
                \"air conditioning service {$actualState}\",
                \"ac compressor repair {$cleanCity}\",
                \"cooling repair near {$cleanCity}\",
                \"hvac service {$actualState}\",
                \"central air {$cleanCity}\",
                \"ac installation {$actualState}\",
                \"cooling system {$actualMetro}\",
                \"ac repair near me\",
                \"air conditioning companies {$actualState}\",
                \"hvac contractors {$cleanCity}\"
            ];
            break;
            
        case 'Hot-Humid':
        case 'Very Hot-Humid':
            $climatePhrases = [
                // AC + humidity control with actual location
                \"ac repair {$cleanCity}\",
                \"air conditioning {$cleanCity} {$actualStateCode}\",
                \"humidity control {$actualState}\",
                \"dehumidifier service {$cleanCity}\",
                \"ac service {$actualState}\",
                \"air conditioning repair {$cleanCity}\",
                \"hvac repair {$actualState}\",
                \"cooling system {$cleanCity}\",
                \"ac maintenance {$actualState}\",
                \"humid climate hvac {$cleanCity}\",
                \"mold prevention {$actualState}\",\n                \"air conditioning service {$cleanCity}\",
                \"dehumidification {$actualState}\",
                \"ac repair near {$cleanCity}\",
                \"hvac service {$actualState}\",
                \"cooling costs {$cleanCity}\",
                \"humidity problems {$actualState}\",
                \"air quality {$cleanCity}\",
                \"ac not cooling humid {$actualState}\",
                \"hvac contractors {$cleanCity}\"
            ];
            break;
            
        case 'Cold':
        case 'Very Cold':
        case 'Subarctic':
            $climatePhrases = [
                // Heating focus with actual location
                \"furnace repair {$cleanCity}\",
                \"heating system {$cleanCity} {$actualStateCode}\",
                \"boiler repair {$actualState}\",
                \"furnace service {$cleanCity}\",
                \"heating repair {$actualState}\",
                \"gas furnace {$cleanCity}\",
                \"heating maintenance {$actualState}\",
                \"boiler service {$cleanCity}\",
                \"heating costs {$actualState}\",
                \"furnace not working {$cleanCity}\",
                \"heating system repair {$actualState}\",
                \"cold weather heating {$cleanCity}\",
                \"winter heating {$actualState}\",
                \"furnace replacement {$cleanCity}\",
                \"heating contractors {$actualState}\",
                \"gas heating {$cleanCity}\",
                \"heating bills {$actualState}\",
                \"heating service {$cleanCity}\",
                \"furnace installation {$actualState}\",
                \"heating companies {$cleanCity}\"
            ];
            break;
            
        case 'Mixed-Cold':
            $climatePhrases = [
                // Heating priority with some cooling
                \"heat pump repair {$cleanCity}\",
                \"hvac service {$cleanCity} {$actualStateCode}\",
                \"heating cooling {$actualState}\",
                \"furnace repair {$cleanCity}\",
                \"heat pump service {$actualState}\",
                \"hvac repair {$cleanCity}\",
                \"seasonal hvac {$actualState}\",
                \"heat pump maintenance {$cleanCity}\",
                \"hvac contractors {$actualState}\",
                \"heating system {$cleanCity}\",
                \"heat pump vs furnace {$actualState}\",
                \"hvac maintenance {$cleanCity}\",
                \"heating repair {$actualState}\",
                \"hvac companies {$cleanCity}\",
                \"seasonal maintenance {$actualState}\",
                \"heat pump installation {$cleanCity}\",
                \"hvac service companies {$actualState}\",
                \"heating cooling repair {$cleanCity}\",
                \"hvac emergency {$actualState}\",
                \"heat pump cold weather {$cleanCity}\"
            ];
            break;
            
        case 'Mixed':
        case 'Mixed-Humid':
            $climatePhrases = [
                // Balanced heating and cooling
                \"heat pump repair {$cleanCity}\",
                \"hvac service {$cleanCity} {$actualStateCode}\",
                \"heating cooling {$actualState}\",
                \"hvac repair {$cleanCity}\",
                \"heat pump service {$actualState}\",
                \"hvac contractors {$cleanCity}\",
                \"seasonal hvac {$actualState}\",
                \"hvac maintenance {$cleanCity}\",
                \"heat pump vs ac {$actualState}\",
                \"hvac companies {$cleanCity}\",
                \"heating cooling repair {$actualState}\",
                \"hvac installation {$cleanCity}\",
                \"seasonal maintenance {$actualState}\",
                \"hvac service companies {$cleanCity}\",
                \"heat pump maintenance {$actualState}\",
                \"hvac emergency service {$cleanCity}\",
                \"year round hvac {$actualState}\",
                \"dual fuel systems {$cleanCity}\",
                \"hvac zoning {$actualState}\",
                \"indoor air quality {$cleanCity}\"
            ];
            break;
            
        case 'Marine':
            $climatePhrases = [
                // Mild climate with heat pump focus
                \"heat pump repair {$cleanCity}\",
                \"mini split {$cleanCity} {$actualStateCode}\",
                \"ductless heating {$actualState}\",
                \"hvac service {$cleanCity}\",
                \"heat pump service {$actualState}\",
                \"ductless hvac {$cleanCity}\",
                \"mini split installation {$actualState}\",
                \"hvac repair {$cleanCity}\",
                \"heat pump maintenance {$actualState}\",
                \"ductless mini split {$cleanCity}\",
                \"mild climate hvac {$actualState}\",
                \"energy efficient heating {$cleanCity}\",
                \"ductless heating cooling {$actualState}\",
                \"heat pump installation {$cleanCity}\",
                \"mini split service {$actualState}\",
                \"coastal hvac {$cleanCity}\",
                \"ventilation systems {$actualState}\",
                \"radiant heating {$cleanCity}\",
                \"hvac maintenance {$actualState}\",
                \"heat pump vs furnace {$cleanCity}\"
            ];
            break;
            
        default:
            // Generic location-based phrases
            $climatePhrases = [
                \"hvac repair {$cleanCity}\",
                \"ac repair {$actualState}\",
                \"heating cooling {$cleanCity}\",
                \"hvac service {$actualState}\",
                \"furnace repair {$cleanCity}\",
                \"ac service {$actualState}\",
                \"hvac contractors {$cleanCity}\",
                \"heating repair {$actualState}\",
                \"hvac maintenance {$cleanCity}\",
                \"hvac companies {$actualState}\"
            ];
    }
    
    return $climatePhrases;
}

function generateLocationSpecificCorePhrases($locationData, $climateZone) {
    $actualCity = $locationData['city'];
    $actualState = $locationData['state'];
    $actualStateCode = $locationData['state_code'];
    
    // Clean city name
    $cleanCity = preg_replace('/\\s+(city|town|village|township)$/i', '', $actualCity);
    
    switch ($climateZone) {
        case 'Hot-Dry':
        case 'Very Hot-Dry':
            return [
                \"ac repair {$cleanCity}\",
                \"air conditioning {$actualState}\",
                \"cooling repair {$cleanCity}\",
                \"ac service {$actualState}\",
                \"hvac repair {$cleanCity}\",
                \"ac not cooling {$actualState}\",
                \"cooling system {$cleanCity}\",
                \"air conditioning repair {$actualState}\",
                \"ac maintenance {$cleanCity}\"
            ];
            
        case 'Hot-Humid':
        case 'Very Hot-Humid':
            return [
                \"ac repair {$cleanCity}\",
                \"humidity control {$actualState}\",
                \"dehumidifier {$cleanCity}\",
                \"air conditioning {$actualState}\",
                \"ac service {$cleanCity}\",
                \"hvac repair {$actualState}\",
                \"mold prevention {$cleanCity}\",
                \"cooling system {$actualState}\",
                \"ac maintenance {$cleanCity}\"
            ];
            
        case 'Cold':
        case 'Very Cold':
        case 'Subarctic':
            return [
                \"furnace repair {$cleanCity}\",
                \"heating system {$actualState}\",
                \"boiler repair {$cleanCity}\",
                \"furnace service {$actualState}\",
                \"heating repair {$cleanCity}\",
                \"gas furnace {$actualState}\",
                \"heating maintenance {$cleanCity}\",
                \"heating costs {$actualState}\",
                \"winter heating {$cleanCity}\"
            ];
            
        case 'Mixed-Cold':
            return [
                \"heat pump repair {$cleanCity}\",
                \"hvac service {$actualState}\",
                \"heating cooling {$cleanCity}\",
                \"furnace repair {$actualState}\",
                \"heat pump {$cleanCity}\",
                \"hvac repair {$actualState}\",
                \"seasonal hvac {$cleanCity}\",
                \"hvac contractors {$actualState}\",
                \"heating system {$cleanCity}\"
            ];
            
        case 'Mixed':
        case 'Mixed-Humid':
            return [
                \"heat pump repair {$cleanCity}\",
                \"hvac service {$actualState}\",
                \"heating cooling {$cleanCity}\",
                \"hvac repair {$actualState}\",
                \"heat pump service {$cleanCity}\",
                \"hvac contractors {$actualState}\",
                \"seasonal hvac {$cleanCity}\",
                \"hvac maintenance {$actualState}\",
                \"hvac companies {$cleanCity}\"
            ];
            
        case 'Marine':
            return [
                \"heat pump repair {$cleanCity}\",
                \"mini split {$actualState}\",
                \"ductless heating {$cleanCity}\",
                \"hvac service {$actualState}\",
                \"heat pump service {$cleanCity}\",
                \"ductless hvac {$actualState}\",
                \"mini split installation {$cleanCity}\",
                \"hvac repair {$actualState}\",
                \"mild climate hvac {$cleanCity}\"
            ];
            
        default:
            return [
                \"hvac repair {$cleanCity}\",
                \"ac repair {$actualState}\",
                \"furnace repair {$cleanCity}\",
                \"heating cooling {$actualState}\",
                \"hvac service {$cleanCity}\",
                \"hvac contractors {$actualState}\",
                \"hvac maintenance {$cleanCity}\",
                \"heating repair {$actualState}\",
                \"ac service {$cleanCity}\"
            ];
    }
}

?>