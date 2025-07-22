<?php

namespace Tests\Unit;

use App\ValueObjects\ProcessedItem;
use PHPUnit\Framework\TestCase;

class ProcessedItemTest extends TestCase
{
    public function test_create_basic_item(): void
    {
        $item = ProcessedItem::create('Test item', 'strengths');
        
        $this->assertEquals('Test item', $item->text);
        $this->assertEquals('strengths', $item->category);
        $this->assertFalse($item->hasConfidence);
        $this->assertFalse($item->hasDagger);
    }

    public function test_create_item_with_confidence_and_dagger(): void
    {
        $item = ProcessedItem::create('Test item', 'concerns', true, true);
        
        $this->assertEquals('Test item', $item->text);
        $this->assertEquals('concerns', $item->category);
        $this->assertTrue($item->hasConfidence);
        $this->assertTrue($item->hasDagger);
    }

    public function test_with_dagger_method(): void
    {
        $original = ProcessedItem::create('Test item', 'monitor');
        $withDagger = $original->withDagger();
        
        $this->assertFalse($original->hasDagger);
        $this->assertTrue($withDagger->hasDagger);
        $this->assertEquals($original->text, $withDagger->text);
        $this->assertEquals($original->category, $withDagger->category);
        $this->assertEquals($original->hasConfidence, $withDagger->hasConfidence);
    }

    public function test_with_confidence_method(): void
    {
        $original = ProcessedItem::create('Test item', 'strengths');
        $withConfidence = $original->withConfidence();
        
        $this->assertFalse($original->hasConfidence);
        $this->assertTrue($withConfidence->hasConfidence);
        $this->assertEquals($original->text, $withConfidence->text);
        $this->assertEquals($original->category, $withConfidence->category);
        $this->assertEquals($original->hasDagger, $withConfidence->hasDagger);
    }

    public function test_get_formatted_text_plain(): void
    {
        $item = ProcessedItem::create('Test item', 'strengths');
        
        $this->assertEquals('Test item', $item->getFormattedText());
    }

    public function test_get_formatted_text_with_dagger(): void
    {
        $item = ProcessedItem::create('Test item', 'strengths', false, true);
        
        $this->assertEquals('Test item†', $item->getFormattedText());
    }

    public function test_get_formatted_text_with_confidence(): void
    {
        $item = ProcessedItem::create('Test item', 'strengths', true, false);
        
        $this->assertEquals('Test item*', $item->getFormattedText());
    }

    public function test_get_formatted_text_with_both(): void
    {
        $item = ProcessedItem::create('Test item', 'strengths', true, true);
        
        $this->assertEquals('Test item†*', $item->getFormattedText());
    }

    public function test_to_array(): void
    {
        $item = ProcessedItem::create('Test item', 'concerns', true, true);
        $array = $item->toArray();
        
        $expected = [
            'text' => 'Test item',
            'category' => 'concerns',
            'has_confidence' => true,
            'has_dagger' => true,
            'formatted_text' => 'Test item†*'
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function test_readonly_properties(): void
    {
        $item = new ProcessedItem('Test', 'monitor', true, false);
        
        $this->assertEquals('Test', $item->text);
        $this->assertEquals('monitor', $item->category);
        $this->assertTrue($item->hasConfidence);
        $this->assertFalse($item->hasDagger);
    }

    public function test_valid_categories(): void
    {
        $validCategories = ['strengths', 'monitor', 'concerns'];
        
        foreach ($validCategories as $category) {
            $item = ProcessedItem::create('Test', $category);
            $this->assertEquals($category, $item->category);
        }
    }
}