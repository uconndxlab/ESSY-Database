<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ReportData;
use App\Constants\ReportDataFields;

class FieldNameValidator
{
    private array $modelFields;
    private array $foundFields = [];
    private array $errors = [];
    private array $warnings = [];

    public function __construct()
    {
        $this->modelFields = (new ReportData())->getFillable();
    }

    /**
     * Validate field names across the entire codebase
     */
    public function validateCodebase(): array
    {
        echo "Starting field name validation...\n";

        // Scan PHP files
        $this->scanDirectory(__DIR__ . '/../app', '*.php');
        
        // Scan Blade templates
        $this->scanDirectory(__DIR__ . '/../resources/views', '*.blade.php');

        // Validate found fields
        $this->validateFoundFields();

        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'found_fields' => $this->foundFields,
            'model_fields' => $this->modelFields
        ];
    }

    /**
     * Scan directory for field references
     */
    private function scanDirectory(string $directory, string $pattern): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->matchesPattern($file->getFilename(), $pattern)) {
                $this->scanFile($file->getPathname());
            }
        }
    }

    /**
     * Check if filename matches pattern
     */
    private function matchesPattern(string $filename, string $pattern): bool
    {
        $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/';
        return preg_match($regex, $filename);
    }

    /**
     * Scan individual file for field references
     */
    private function scanFile(string $filepath): void
    {
        $content = file_get_contents($filepath);
        if ($content === false) {
            return;
        }

        // Look for field references in various patterns
        $patterns = [
            // Direct field access: $report->FIELD_NAME
            '/\$\w+->([A-Z_][A-Z0-9_]*)/i',
            // Array access: $report['FIELD_NAME']
            '/\$\w+\[[\'"]([A-Z_][A-Z0-9_]*)[\'"]\]/i',
            // getAttribute calls: getAttribute('FIELD_NAME')
            '/getAttribute\([\'"]([A-Z_][A-Z0-9_]*)[\'"]\)/i',
            // Fillable array entries: 'FIELD_NAME'
            '/[\'"]([A-Z_][A-Z0-9_]*)[\'"],?\s*(?:\/\/|$)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $field) {
                    $field = strtoupper($field);
                    if ($this->looksLikeReportField($field)) {
                        $this->foundFields[$field] = ($this->foundFields[$field] ?? 0) + 1;
                    }
                }
            }
        }
    }

    /**
     * Check if field name looks like a ReportData field
     */
    private function looksLikeReportField(string $field): bool
    {
        // Skip common non-field names
        $skipPatterns = [
            'ID', 'CREATED_AT', 'UPDATED_AT', 'DELETED_AT',
            'CLASS', 'FUNCTION', 'METHOD', 'CONST', 'STATIC',
            'PUBLIC', 'PRIVATE', 'PROTECTED', 'NAMESPACE'
        ];

        if (in_array($field, $skipPatterns)) {
            return false;
        }

        // Look for patterns that suggest ReportData fields
        $reportFieldPatterns = [
            '/^[A-Z]_/', // Starts with letter_
            '/^DEM_/', // Demographics
            '/^COMMENTS_/', // Comments
            '/^TIMING_/', // Timing
            '/^SPEEDING_/', // Speeding
            '/_CL[0-9]$/', // Cross-loaded indicators
            '/_DOMAIN$/', // Domain fields
        ];

        foreach ($reportFieldPatterns as $pattern) {
            if (preg_match($pattern, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate found fields against model
     */
    private function validateFoundFields(): void
    {
        foreach ($this->foundFields as $field => $count) {
            if (!in_array($field, $this->modelFields)) {
                $this->errors[] = "Field '{$field}' not found in ReportData model (used {$count} times)";
            }
        }

        // Check for potential typos
        $this->checkForTypos();
    }

    /**
     * Check for potential field name typos
     */
    private function checkForTypos(): void
    {
        $knownTypos = [
            // Add any known field name variations here
        ];

        foreach ($knownTypos as $typo => $correct) {
            if (isset($this->foundFields[$typo]) && isset($this->foundFields[$correct])) {
                $this->warnings[] = "Potential spelling inconsistency: '{$typo}' vs '{$correct}'";
            }
        }

        // Check for similar field names that might be typos
        foreach ($this->foundFields as $field => $count) {
            if (!in_array($field, $this->modelFields)) {
                $similar = $this->findSimilarFields($field);
                if (!empty($similar)) {
                    $this->warnings[] = "Field '{$field}' not found, similar fields: " . implode(', ', $similar);
                }
            }
        }
    }

    /**
     * Find similar field names using Levenshtein distance
     */
    private function findSimilarFields(string $field): array
    {
        $similar = [];
        
        foreach ($this->modelFields as $modelField) {
            $distance = levenshtein($field, $modelField);
            if ($distance <= 2 && $distance > 0) { // Allow up to 2 character differences
                $similar[] = $modelField;
            }
        }

        return $similar;
    }

    /**
     * Generate report
     */
    public function generateReport(): void
    {
        $results = $this->validateCodebase();

        echo "\n=== FIELD NAME VALIDATION REPORT ===\n";
        echo "Total fields found: " . count($results['found_fields']) . "\n";
        echo "Model fields: " . count($results['model_fields']) . "\n";
        echo "Errors: " . count($results['errors']) . "\n";
        echo "Warnings: " . count($results['warnings']) . "\n\n";

        if (!empty($results['errors'])) {
            echo "ERRORS:\n";
            foreach ($results['errors'] as $error) {
                echo "  - {$error}\n";
            }
            echo "\n";
        }

        if (!empty($results['warnings'])) {
            echo "WARNINGS:\n";
            foreach ($results['warnings'] as $warning) {
                echo "  - {$warning}\n";
            }
            echo "\n";
        }

        echo "Most used fields:\n";
        arsort($results['found_fields']);
        $topFields = array_slice($results['found_fields'], 0, 10, true);
        foreach ($topFields as $field => $count) {
            $status = in_array($field, $results['model_fields']) ? '✓' : '✗';
            echo "  {$status} {$field}: {$count} times\n";
        }
    }
}

// Run the validator if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $validator = new FieldNameValidator();
    $validator->generateReport();
}