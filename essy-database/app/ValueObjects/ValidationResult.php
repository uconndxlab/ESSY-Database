<?php

namespace App\ValueObjects;

class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly array $warnings = []
    ) {}

    public static function valid(array $warnings = []): self
    {
        return new self(true, [], $warnings);
    }

    public static function invalid(array $errors, array $warnings = []): self
    {
        return new self(false, $errors, $warnings);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }
}