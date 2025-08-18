<?php

/**
 * Test script to verify the unanswered items fix for S_O_COMMCONN_CL1
 * This simulates the logic from print.blade.php to test the fix
 */

// Simulate the cross-loaded groups configuration
$crossLoadItemGroups = [
    ['S_O_COMMCONN_CL1', 'S_O_COMMCONN_CL2'],     // Community connection - SEWB / SOS
    ['A_S_O_ACTIVITY_CL1', 'A_S_O_ACTIVITY_CL2', 'A_S_O_ACTIVITY_CL3'] // Extracurricular activity
];

// Simulate field to domain mapping
$fieldToDomainMap = [
    'S_O_COMMCONN_CL1' => 'Social & Emotional Well-Being',
    'S_O_COMMCONN_CL2' => 'Supports Outside of School',
    'A_S_O_ACTIVITY_CL1' => 'Academic Skills',
    'A_S_O_ACTIVITY_CL2' => 'Social & Emotional Well-Being',
    'A_S_O_ACTIVITY_CL3' => 'Supports Outside of School',
];

// Simulate field messages
$fieldMessages = [
    'S_O_COMMCONN_CL1' => 'appears to experience a sense of connection in their community.',
    'S_O_COMMCONN_CL2' => 'appears to experience a sense of connection in their community.',
    'A_S_O_ACTIVITY_CL1' => 'engaged in at least one extracurricular activity.',
    'A_S_O_ACTIVITY_CL2' => 'engaged in at least one extracurricular activity.',
    'A_S_O_ACTIVITY_CL3' => 'engaged in at least one extracurricular activity.',
];

// Test scenarios
$testScenarios = [
    'Scenario 1: Both SEWB and SOS are concerns, both S_O_COMMCONN fields are null (older dataset)' => [
        'concernDomains' => ['Social & Emotional Well-Being', 'Supports Outside of School'],
        'fieldValues' => [
            'S_O_COMMCONN_CL1' => null,
            'S_O_COMMCONN_CL2' => null,
            // Give activity fields values so they don't interfere with the test
            'A_S_O_ACTIVITY_CL1' => null,
            'A_S_O_ACTIVITY_CL2' => 'Sometimes',
            'A_S_O_ACTIVITY_CL3' => null,
        ],
        'expectedMissingCount' => 0, // Should NOT count as missing due to older dataset logic
    ],
    'Scenario 2: Only SEWB is concern, both S_O_COMMCONN fields are null (older dataset)' => [
        'concernDomains' => ['Social & Emotional Well-Being'],
        'fieldValues' => [
            'S_O_COMMCONN_CL1' => null,
            'S_O_COMMCONN_CL2' => null,
            // Give activity fields values so they don't interfere with the test
            'A_S_O_ACTIVITY_CL1' => null,
            'A_S_O_ACTIVITY_CL2' => 'Sometimes',
            'A_S_O_ACTIVITY_CL3' => null,
        ],
        'expectedMissingCount' => 0, // Should NOT count as missing due to older dataset logic
    ],
    'Scenario 3: Both SEWB and SOS are concerns, CL1 has value, CL2 is null' => [
        'concernDomains' => ['Social & Emotional Well-Being', 'Supports Outside of School'],
        'fieldValues' => [
            'S_O_COMMCONN_CL1' => 'Sometimes',
            'S_O_COMMCONN_CL2' => null,
            // Give activity fields values so they don't interfere with the test
            'A_S_O_ACTIVITY_CL1' => null,
            'A_S_O_ACTIVITY_CL2' => 'Sometimes',
            'A_S_O_ACTIVITY_CL3' => null,
        ],
        'expectedMissingCount' => 0, // Should NOT count as missing because group has a value
    ],
    'Scenario 4: Normal cross-loaded item with missing values' => [
        'concernDomains' => ['Academic Skills', 'Social & Emotional Well-Being'],
        'fieldValues' => [
            'S_O_COMMCONN_CL1' => 'Sometimes', // Give this a value so it doesn't interfere
            'S_O_COMMCONN_CL2' => null,
            'A_S_O_ACTIVITY_CL1' => null,
            'A_S_O_ACTIVITY_CL2' => null,
            'A_S_O_ACTIVITY_CL3' => null,
        ],
        'expectedMissingCount' => 1, // Should count as missing (normal behavior)
    ],
];

function simulateGetFieldValue($fieldValues, $field) {
    return $fieldValues[$field] ?? null;
}

function testUnansweredItemsLogic($scenario, $concernDomains, $fieldValues, $expectedMissingCount) {
    global $crossLoadItemGroups, $fieldToDomainMap, $fieldMessages;
    
    echo "\n=== $scenario ===\n";
    echo "Concern Domains: " . implode(', ', $concernDomains) . "\n";
    echo "Field Values: " . json_encode($fieldValues) . "\n";
    
    $missingItems = [];
    $processedCrossLoadedGroups = [];
    
    foreach ($fieldMessages as $field => $message) {
        // Only count fields from concern domains
        $fieldDomain = $fieldToDomainMap[$field] ?? null;
        if (!$fieldDomain || !in_array($fieldDomain, $concernDomains)) {
            continue;
        }
        
        // Check if this field is part of a cross-loaded group
        $crossLoadedGroupIndex = null;
        foreach ($crossLoadItemGroups as $groupIndex => $group) {
            if (in_array($field, $group)) {
                $crossLoadedGroupIndex = $groupIndex;
                break;
            }
        }
        
        // If this field is part of a cross-loaded group
        if ($crossLoadedGroupIndex !== null) {
            // Skip if we've already processed this cross-loaded group
            if (isset($processedCrossLoadedGroups[$crossLoadedGroupIndex])) {
                continue;
            }
            
            // Check if ANY field in the cross-loaded group has a value
            $groupHasValue = false;
            $group = $crossLoadItemGroups[$crossLoadedGroupIndex];
            foreach ($group as $groupField) {
                $value = simulateGetFieldValue($fieldValues, $groupField);
                if ($value !== null) {
                    $groupHasValue = true;
                    break;
                }
            }
            
            // If no field in the group has a value, count it as missing
            // But only if we have fields from concern domains in this group
            if (!$groupHasValue) {
                // Find a field from a concern domain to use as representative
                $representativeField = null;
                $fieldsFromConcernDomains = [];
                
                foreach ($group as $groupField) {
                    $groupFieldDomain = $fieldToDomainMap[$groupField] ?? null;
                    if ($groupFieldDomain && in_array($groupFieldDomain, $concernDomains)) {
                        $fieldsFromConcernDomains[] = $groupField;
                    }
                }
                
                // Only count as missing if we have fields from concern domains in this group
                if (!empty($fieldsFromConcernDomains)) {
                    // Check if this cross-loaded group should be counted as missing
                    // For older datasets, some cross-loaded items might not have been presented
                    $shouldCountAsMissing = true;
                    
                    // Special handling for cross-loaded items that might not exist in older datasets
                    // Check for specific problematic cross-loaded groups
                    $isProblematicCrossLoadedGroup = false;
                    
                    // S_O_COMMCONN group: Community connection cross-loaded between SEWB and SOS
                    if (count($group) == 2 && 
                        in_array('S_O_COMMCONN_CL1', $group) && 
                        in_array('S_O_COMMCONN_CL2', $group)) {
                        
                        $cl1Value = simulateGetFieldValue($fieldValues, 'S_O_COMMCONN_CL1');
                        $cl2Value = simulateGetFieldValue($fieldValues, 'S_O_COMMCONN_CL2');
                        
                        // If both fields are null, this might be from an older dataset
                        // where this item wasn't cross-loaded yet (was just S_COMMCONN)
                        if ($cl1Value === null && $cl2Value === null) {
                            $isProblematicCrossLoadedGroup = true;
                        }
                    }
                    
                    // Don't count problematic cross-loaded groups as missing
                    // These are likely from older dataset versions where the item structure was different
                    if ($isProblematicCrossLoadedGroup) {
                        $shouldCountAsMissing = false;
                    }
                    
                    if ($shouldCountAsMissing) {
                        // Choose the best representative field
                        if (count($fieldsFromConcernDomains) > 1) {
                            // If multiple fields from concern domains, prefer CL2 over CL1 for newer format
                            $representativeField = end($fieldsFromConcernDomains);
                        } else {
                            $representativeField = $fieldsFromConcernDomains[0];
                        }
                        
                        $representativeMessage = $fieldMessages[$representativeField] ?? $message;
                        $missingItems[] = $representativeMessage;
                    }
                }
            }
            
            // Mark this group as processed
            $processedCrossLoadedGroups[$crossLoadedGroupIndex] = true;
        } else {
            // For non-cross-loaded fields, check the field directly
            $value = simulateGetFieldValue($fieldValues, $field);
            if ($value === null) {
                $missingItems[] = $message;
            }
        }
    }
    
    $actualMissingCount = count($missingItems);
    echo "Expected missing count: $expectedMissingCount\n";
    echo "Actual missing count: $actualMissingCount\n";
    echo "Missing items: " . implode(', ', $missingItems) . "\n";
    
    if ($actualMissingCount === $expectedMissingCount) {
        echo "✅ PASS\n";
        return true;
    } else {
        echo "❌ FAIL\n";
        return false;
    }
}

// Run all test scenarios
$allPassed = true;
foreach ($testScenarios as $scenario => $config) {
    $passed = testUnansweredItemsLogic(
        $scenario,
        $config['concernDomains'],
        $config['fieldValues'],
        $config['expectedMissingCount']
    );
    $allPassed = $allPassed && $passed;
}

echo "\n" . str_repeat('=', 50) . "\n";
if ($allPassed) {
    echo "🎉 ALL TESTS PASSED! The fix should work correctly.\n";
} else {
    echo "❌ SOME TESTS FAILED. The fix needs adjustment.\n";
}
echo str_repeat('=', 50) . "\n";