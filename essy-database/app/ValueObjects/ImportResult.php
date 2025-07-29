<?php

namespace App\ValueObjects;

class ImportResult
{
    public function __construct(
        public readonly int $imported,
        public readonly int $updated,
        public readonly int $errors,
        public readonly array $errorMessages
    ) {}

    /**
     * Get total processed count (imported + updated + errors)
     */
    public function getTotalProcessed(): int
    {
        return $this->imported + $this->updated + $this->errors;
    }

    /**
     * Get success count (imported + updated)
     */
    public function getSuccessCount(): int
    {
        return $this->imported + $this->updated;
    }

    /**
     * Check if import was successful (no errors)
     */
    public function isSuccessful(): bool
    {
        return $this->errors === 0;
    }

    /**
     * Format summary output for console display
     */
    public function getSummary(): string
    {
        $summary = "Import Summary:\n";
        $summary .= "- Imported: {$this->imported}\n";
        $summary .= "- Updated: {$this->updated}\n";
        $summary .= "- Errors: {$this->errors}\n";
        $summary .= "- Total Processed: {$this->getTotalProcessed()}\n";

        if (!empty($this->errorMessages)) {
            $summary .= "\nErrors:\n";
            foreach ($this->errorMessages as $error) {
                $summary .= "- {$error}\n";
            }
        }

        return $summary;
    }

    /**
     * Get formatted error messages
     */
    public function getFormattedErrors(): array
    {
        return $this->errorMessages;
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return $this->errors > 0;
    }
}