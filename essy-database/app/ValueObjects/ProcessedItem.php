<?php

namespace App\ValueObjects;

class ProcessedItem
{
    public function __construct(
        public readonly string $text,
        public readonly string $category, // 'strengths', 'monitor', 'concerns'
        public readonly bool $hasConfidence,
        public readonly bool $hasDagger
    ) {}

    public static function create(
        string $text,
        string $category,
        bool $hasConfidence = false,
        bool $hasDagger = false
    ): self {
        return new self($text, $category, $hasConfidence, $hasDagger);
    }

    public function withDagger(): self
    {
        return new self($this->text, $this->category, $this->hasConfidence, true);
    }

    public function withConfidence(): self
    {
        return new self($this->text, $this->category, true, $this->hasDagger);
    }

    public function getFormattedText(): string
    {
        $text = $this->text;
        
        if ($this->hasDagger) {
            $text .= '†';
        }
        
        if ($this->hasConfidence) {
            $text .= '*';
        }
        
        return $text;
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'category' => $this->category,
            'has_confidence' => $this->hasConfidence,
            'has_dagger' => $this->hasDagger,
            'formatted_text' => $this->getFormattedText()
        ];
    }
}