<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;

// Get fillable fields from ReportData model
$reportData = new ReportData();
$fillableFields = $reportData->getFillable();

// Get configured fields from CrossLoadedDomainService
$crossLoadedService = new CrossLoadedDomainService();
$fieldMessages = $crossLoadedService->getFieldMessages();
$configuredFields = array_keys($fieldMessages);

echo "=== Field Name Validation Report ===\n\n";

// Check for fields in configuration that are missing from model
$missingFromModel = array_diff($configuredFields, $fillableFields);
if (!empty($missingFromModel)) {
    echo "❌ Fields configured in CrossLoadedDomainService but MISSING from ReportData model:\n";
    foreach ($missingFromModel as $field) {
        echo "  - $field\n";
    }
    echo "\n";
} else {
    echo "✅ All configured fields exist in ReportData model\n\n";
}

// Check for old field names that might still be in the model
$oldFieldNames = [
    'A_DIRECTIONS',
    'BEH_VERBAGGRESS', 
    'BEH_PHYSAGGRESS',
    'A_ORAL',
    'A_PHYS',
    'O_P_HYGIENE_CL1',
    'S_COMMCONN'
];

$oldFieldsStillPresent = array_intersect($oldFieldNames, $fillableFields);
if (!empty($oldFieldsStillPresent)) {
    echo "⚠️  Old field names still present in ReportData model (should be updated):\n";
    foreach ($oldFieldsStillPresent as $field) {
        echo "  - $field\n";
    }
    echo "\n";
}

// Show summary
echo "=== Summary ===\n";
echo "Total configured fields: " . count($configuredFields) . "\n";
echo "Total fillable fields: " . count($fillableFields) . "\n";
echo "Missing from model: " . count($missingFromModel) . "\n";
echo "Old fields still present: " . count($oldFieldsStillPresent) . "\n";

// Show all configured fields for reference
echo "\n=== All Configured Fields ===\n";
sort($configuredFields);
foreach ($configuredFields as $field) {
    $status = in_array($field, $fillableFields) ? '✅' : '❌';
    echo "$status $field\n";
}