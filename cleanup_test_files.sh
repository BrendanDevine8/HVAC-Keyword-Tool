#!/bin/bash

# HVAC Keyword Tool - Test File Cleanup Script
# This script removes temporary test, debug, and diagnostic files
# Created: November 23, 2025

echo "🧹 HVAC Keyword Tool - Test File Cleanup"
echo "========================================"
echo ""

# Store the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd "$SCRIPT_DIR"

# Files to be deleted (organized by category)
declare -a DEBUG_FILES=(
    "debug_05560.php"
    "debug_output.html" 
    "debug_keyword_issue.php"
    "diagnostic.html"
)

declare -a API_TEST_FILES=(
    "test_api_integration.php"
    "test_final_api.php"
    "test_direct_api.php"
    "test_multi_api.php"
    "test-api.php"
    "api/test_keywords_debug.php"
    "api/test_models.php"
)

declare -a FEATURE_TEST_FILES=(
    "test_keywords_simple.php"
    "test_enhanced_climate.php"
    "test_advanced_scoring.php"
    "test_full_generation.php"
    "test_keyword_diversity.php"
    "test_climate.php"
    "test_integration.php"
    "test_enhanced_diversity.php"
    "test_location_aware_keywords.php"
    "test_competitor_analysis.php"
    "test_optimization.php"
    "test_dashboard_flow.php"
    "test_patterns.php"
    "test_noaa_api.php"
)

declare -a PERFORMANCE_TEST_FILES=(
    "simple_optimization_test.php"
    "multi_location_test.php"
    "test-speed.html"
    "test_simple.html"
)

declare -a UTILITY_CHECK_FILES=(
    "check_climate_zones.php"
    "check_zips.php"
    "check_zip_codes.php"
    "check_enrichment_status.php"
)

declare -a MISC_TEST_FILES=(
    "test_zip_codes.php"
    "test-file.php"
)

# Function to delete files in a category
delete_category() {
    local category_name="$1"
    local -n file_array=$2
    
    echo "📁 Cleaning $category_name..."
    
    for file in "${file_array[@]}"; do
        if [ -f "$file" ]; then
            echo "   ✅ Deleting: $file"
            rm "$file"
        else
            echo "   ⚠️  Not found: $file"
        fi
    done
    echo ""
}

# Confirmation prompt
echo "This script will delete the following categories of files:"
echo "• Debug Files (${#DEBUG_FILES[@]} files)"
echo "• API Test Files (${#API_TEST_FILES[@]} files)" 
echo "• Feature Test Files (${#FEATURE_TEST_FILES[@]} files)"
echo "• Performance Test Files (${#PERFORMANCE_TEST_FILES[@]} files)"
echo "• Utility Check Files (${#UTILITY_CHECK_FILES[@]} files)"
echo "• Miscellaneous Test Files (${#MISC_TEST_FILES[@]} files)"
echo ""
echo "⚠️  IMPORTANT: debug_automation.php will NOT be deleted (core monitoring tool)"
echo ""

read -p "Do you want to proceed? (y/N): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Cleanup cancelled"
    exit 1
fi

echo ""
echo "🚀 Starting cleanup..."
echo ""

# Delete files by category
delete_category "Debug Files" DEBUG_FILES
delete_category "API Test Files" API_TEST_FILES
delete_category "Feature Test Files" FEATURE_TEST_FILES
delete_category "Performance Test Files" PERFORMANCE_TEST_FILES
delete_category "Utility Check Files" UTILITY_CHECK_FILES
delete_category "Miscellaneous Test Files" MISC_TEST_FILES

# Count remaining files
REMAINING_TEST_FILES=$(find . -name "test_*" -o -name "debug_*" -o -name "check_*" | wc -l)

echo "🎉 Cleanup Complete!"
echo ""
echo "📊 Summary:"
echo "   • Total categories processed: 6"
echo "   • Remaining test-like files: $REMAINING_TEST_FILES"
echo "   • Core monitoring tools preserved: debug_automation.php"
echo ""
echo "✅ Your HVAC Keyword Tool workspace is now cleaner!"

# Optional: Show what's left
echo ""
read -p "Show remaining test-like files? (y/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "📋 Remaining files with 'test', 'debug', or 'check' in name:"
    find . -name "*test*" -o -name "*debug*" -o -name "*check*" | sort
fi

echo ""
echo "Done! 🏁"