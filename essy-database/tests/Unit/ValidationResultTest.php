<?php

namespace Tests\Unit;

use App\ValueObjects\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function test_create_valid_result(): void
    {
        $result = ValidationResult::valid();
        
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
        $this->assertEmpty($result->warnings);
        $this->assertFalse($result->hasErrors());
        $this->assertFalse($result->hasWarnings());
    }

    public function test_create_valid_result_with_warnings(): void
    {
        $warnings = ['Warning 1', 'Warning 2'];
        $result = ValidationResult::valid($warnings);
        
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
        $this->assertEquals($warnings, $result->warnings);
        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
    }

    public function test_create_invalid_result(): void
    {
        $errors = ['Error 1', 'Error 2'];
        $result = ValidationResult::invalid($errors);
        
        $this->assertFalse($result->isValid);
        $this->assertEquals($errors, $result->errors);
        $this->assertEmpty($result->warnings);
        $this->assertTrue($result->hasErrors());
        $this->assertFalse($result->hasWarnings());
    }

    public function test_create_invalid_result_with_warnings(): void
    {
        $errors = ['Error 1'];
        $warnings = ['Warning 1'];
        $result = ValidationResult::invalid($errors, $warnings);
        
        $this->assertFalse($result->isValid);
        $this->assertEquals($errors, $result->errors);
        $this->assertEquals($warnings, $result->warnings);
        $this->assertTrue($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
    }

    public function test_to_array(): void
    {
        $errors = ['Error 1'];
        $warnings = ['Warning 1'];
        $result = new ValidationResult(false, $errors, $warnings);
        
        $expected = [
            'is_valid' => false,
            'errors' => $errors,
            'warnings' => $warnings
        ];
        
        $this->assertEquals($expected, $result->toArray());
    }

    public function test_constructor_with_all_parameters(): void
    {
        $errors = ['Error 1', 'Error 2'];
        $warnings = ['Warning 1'];
        $result = new ValidationResult(true, $errors, $warnings);
        
        $this->assertTrue($result->isValid);
        $this->assertEquals($errors, $result->errors);
        $this->assertEquals($warnings, $result->warnings);
    }

    public function test_readonly_properties(): void
    {
        $result = new ValidationResult(true, ['error'], ['warning']);
        
        // These should not cause errors since properties are readonly
        $this->assertTrue($result->isValid);
        $this->assertEquals(['error'], $result->errors);
        $this->assertEquals(['warning'], $result->warnings);
    }
}