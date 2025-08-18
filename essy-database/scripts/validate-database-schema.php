<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CrossLoadedDomainService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Get configured fields from CrossLoadedDomainService
$crossLoadedService = new CrossLoadedDomainService();
$fieldMessages = $crossLoadedService->getFieldMessages();
$configuredFields = array_keys($fieldMessages);

// Get actual database columns
$columns = Schema::getColumnListing('report_data');

echo "=== Database Schema Validation Report ===\n\n";

// Check for fields in configuration that are missing from database
$missingFromDatabase = array_diff($configuredFields, $columns);
if (!empty($missingFromDatabase)) {
    echo "❌ Fields configured in CrossLoadedDomainService but MISSING from database:\n";
    foreach ($missingFromDatabase as $field) {
        echo "  - $field\n";
    }
    echo "\n";
} else {
    echo "✅ All configured fields exist in database schema\n\n";
}

// Check for old field names that might still be in the database
$oldFieldNames = [
    'A_DIRECTIONS',
    'BEH_VERBAGGRESS', 
    'BEH_PHYSAGGRESS',
    'A_ORAL',
    'A_PHYS',
    'O_P_HYGIENE_CL1',  // Note: correct spelling vs Excel spelling
    'O_P_HYGIENE_CL2',  // Note: correct spelling vs Excel spelling
    'S_COMMCONN'
];

$oldFieldsStillInDatabase = array_intersect($oldFieldNames, $columns);
if (!empty($oldFieldsStillInDatabase)) {
    echo "⚠️  Old field names still present in database (should be updated):\n";
    foreach ($oldFieldsStillInDatabase as $field) {
        echo "  - $field\n";
    }
    echo "\n";
}

// Show summary
echo "=== Summary ===\n";
echo "Total configured fields: " . count($configuredFields) . "\n";
echo "Total database columns: " . count($columns) . "\n";
echo "Missing from database: " . count($missingFromDatabase) . "\n";
echo "Old fields still in database: " . count($oldFieldsStillInDatabase) . "\n";

// Show all configured fields and their database status
echo "\n=== All Configured Fields Database Status ===\n";
sort($configuredFields);
foreach ($configuredFields as $field) {
    $status = in_array($field, $columns) ? '✅' : '❌';
    echo "$status $field\n";
}

// Show fields that need to be added to database
if (!empty($missingFromDatabase)) {
    echo "\n=== Fields that need to be added to database ===\n";
    foreach ($missingFromDatabase as $field) {
        echo "\$table->string('$field')->nullable();\n";
    }
}

// Show fields that should be renamed in database
if (!empty($oldFieldsStillInDatabase)) {
    echo "\n=== Fields that should be renamed in database ===\n";
    $renameMap = [
        'A_DIRECTIONS' => ['A_B_DIRECTIONS_CL1', 'A_B_DIRECTIONS_CL2'],
        'BEH_VERBAGGRESS' => 'B_VERBAGGRESS',
        'BEH_PHYSAGGRESS' => 'B_PHYSAGGRESS',
        'A_ORAL' => 'P_ORAL',
        'A_PHYS' => 'P_PHYS',
        'O_P_HYGIENE_CL1' => 'O_P_hygiene_CL1',
        'O_P_HYGIENE_CL2' => 'O_P_hygiene_CL2',
        'S_COMMCONN' => ['S_O_COMMCONN_CL1', 'S_O_COMMCONN_CL2']
    ];
    
    foreach ($oldFieldsStillInDatabase as $oldField) {
        if (isset($renameMap[$oldField])) {
            $newFields = is_array($renameMap[$oldField]) ? $renameMap[$oldField] : [$renameMap[$oldField]];
            echo "Rename '$oldField' to: " . implode(', ', $newFields) . "\n";
        }
    }
}