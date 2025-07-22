<?php

namespace App\ValueObjects;

class DomainProcessingResult
{
    public function __construct(
        public readonly array $strengths,
        public readonly array $monitor,
        public readonly array $concerns,
        public readonly array $errors = []
    ) {}

    public static function create(
        array $strengths = [],
        array $monitor = [],
        array $concerns = [],
        array $errors = []
    ): self {
        return new self($strengths, $monitor, $concerns, $errors);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasItems(): bool
    {
        return !empty($this->strengths) || !empty($this->monitor) || !empty($this->concerns);
    }

    public function getTotalItemCount(): int
    {
        return count($this->strengths) + count($this->monitor) + count($this->concerns);
    }

    public function getAllItems(): array
    {
        return array_merge($this->strengths, $this->monitor, $this->concerns);
    }

    public function toArray(): array
    {
        return [
            'strengths' => array_map(fn($item) => $item instanceof ProcessedItem ? $item->toArray() : $item, $this->strengths),
            'monitor' => array_map(fn($item) => $item instanceof ProcessedItem ? $item->toArray() : $item, $this->monitor),
            'concerns' => array_map(fn($item) => $item instanceof ProcessedItem ? $item->toArray() : $item, $this->concerns),
            'errors' => $this->errors,
            'total_items' => $this->getTotalItemCount()
        ];
    }
}