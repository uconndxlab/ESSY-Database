<?php

namespace Database\Seeders;

use App\Models\DecisionRule;
use Illuminate\Database\Seeder;

class DecisionRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = base_path('decision_rules_export.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->warn('Decision rules CSV file not found. Skipping decision rules import.');
            return;
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->command->error('Could not open decision rules CSV file.');
            return;
        }

        // Skip header row
        fgetcsv($handle);

        $imported = 0;
        $skipped = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 4) {
                $skipped++;
                continue;
            }

            $itemCode = trim($data[0], '"');
            $frequency = trim($data[1], '"');
            $decisionText = trim($data[2], '"');
            $domain = trim($data[3], '"');

            // Normalize item_code to uppercase for consistency (handles hygiene, HYGIENE, etc.)
            $itemCode = strtoupper($itemCode);

            // Skip invalid entries
            if (empty($itemCode) || empty($frequency) || empty($decisionText) || $itemCode === 'QUALTRICS COLUMN') {
                $skipped++;
                continue;
            }

            try {
                DecisionRule::updateOrCreate(
                    [
                        'item_code' => $itemCode,
                        'frequency' => $frequency,
                    ],
                    [
                        'decision_text' => $decisionText,
                        'domain' => $domain,
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        fclose($handle);

        $this->command->info("Imported {$imported} decision rules. Skipped {$skipped} entries.");
    }
} 