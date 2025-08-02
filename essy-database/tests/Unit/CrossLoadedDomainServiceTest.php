<?php

namespace Tests\Unit;

use App\Services\CrossLoadedDomainService;
use App\Models\ReportData;
use App\ValueObjects\ValidationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CrossLoadedDomainServiceTest extends TestCase
{
    use RefreshDatabase;

    private CrossLoadedDomainService $service;
    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockLogger = Mockery::mock(LoggerInterface::class);
        $this->service = new CrossLoadedDomainService($this->mockLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_cross_loaded_item_groups_returns_array(): void
    {
        $groups = $this->service->getCrossLoadedItemGroups();
        
        $this->assertIsArray($groups);
        $this->assertNotEmpty($groups);
        
        // Check that each group has at least 2 fields (cross-loaded)
        foreach ($groups as $group) {
            $this->assertIsArray($group);
            $this->assertGreaterThanOrEqual(2, count($group));
        }
    }

    public function test_get_field_to_domain_map_returns_array(): void
    {
        $mapping = $this->service->getFieldToDomainMap();
        
        $this->assertIsArray($mapping);
        $this->assertNotEmpty($mapping);
        
        // Check that all values are valid domain names
        $validDomains = [
            'Academic Skills',
            'Behavior',
            'Physical Health',
            'Social & Emotional Well-Being',
            'Supports Outside of School'
        ];
        
        foreach ($mapping as $field => $domain) {
            $this->assertIsString($field);
            $this->assertContains($domain, $validDomains);
        }
    }

    public function test_get_fields_requiring_dagger_with_no_concerns(): void
    {
        $concernDomains = [];
        $daggerFields = $this->service->getFieldsRequiringDagger($concernDomains);
        
        $this->assertIsArray($daggerFields);
        $this->assertEmpty($daggerFields);
    }

    public function test_get_fields_requiring_dagger_with_single_concern(): void
    {
        $concernDomains = ['Academic Skills'];
        $daggerFields = $this->service->getFieldsRequiringDagger($concernDomains);
        
        // Should be empty because cross-loaded items need multiple domains to be concerns
        $this->assertIsArray($daggerFields);
        $this->assertEmpty($daggerFields);
    }

    public function test_get_fields_requiring_dagger_with_multiple_concerns(): void
    {
        $concernDomains = ['Academic Skills', 'Physical Health'];
        $daggerFields = $this->service->getFieldsRequiringDagger($concernDomains);
        
        $this->assertIsArray($daggerFields);
        
        // Should include A_P_S_ARTICULATE_CL1 and A_P_S_ARTICULATE_CL2 since they're cross-loaded
        // between Academic Skills and Physical Health
        $this->assertArrayHasKey('A_P_S_ARTICULATE_CL1', $daggerFields);
        $this->assertArrayHasKey('A_P_S_ARTICULATE_CL2', $daggerFields);
    }

    public function test_safe_get_field_value_with_valid_field(): void
    {
        $report = new ReportData();
        $report->A_READ = 'Almost always';
        
        $value = $this->service->safeGetFieldValue($report, 'A_READ');
        
        $this->assertEquals('Almost always', $value);
    }

    public function test_safe_get_field_value_with_invalid_field(): void
    {
        $report = new ReportData();
        
        $this->mockLogger->shouldReceive('error')->once();
        
        $value = $this->service->safeGetFieldValue($report, 'INVALID_FIELD');
        
        $this->assertNull($value);
    }

    public function test_safe_get_field_value_with_null_value(): void
    {
        $report = new ReportData();
        $report->A_READ = null;
        
        $value = $this->service->safeGetFieldValue($report, 'A_READ');
        
        $this->assertNull($value);
    }

    public function test_safe_get_field_value_with_empty_value(): void
    {
        $report = new ReportData();
        $report->A_READ = '';
        
        $value = $this->service->safeGetFieldValue($report, 'A_READ');
        
        $this->assertNull($value);
    }

    public function test_safe_get_field_value_with_missing_data_indicator(): void
    {
        $report = new ReportData();
        $report->A_READ = '-99';
        
        $value = $this->service->safeGetFieldValue($report, 'A_READ');
        
        $this->assertNull($value);
    }

    public function test_validate_cross_loaded_configuration_success(): void
    {
        $result = $this->service->validateCrossLoadedConfiguration();
        
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    public function test_validate_database_fields_with_valid_fields(): void
    {
        $modelFields = (new ReportData())->getFillable();
        $result = $this->service->validateDatabaseFields($modelFields);
        
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    public function test_validate_database_fields_with_missing_fields(): void
    {
        $incompleteFields = ['A_READ', 'A_WRITE']; // Missing many fields
        $result = $this->service->validateDatabaseFields($incompleteFields);
        
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    public function test_log_cross_loaded_error(): void
    {
        $message = 'Test error message';
        $context = ['test' => 'data'];
        
        $this->mockLogger->shouldReceive('error')
            ->once()
            ->with('[CrossLoadedDomain] ' . $message, Mockery::type('array'));
        
        $this->service->logCrossLoadedError($message, $context);
        
        // Add assertion to satisfy PHPUnit
        $this->assertTrue(true);
    }

    public function test_get_fields_requiring_dagger_with_empty_groups(): void
    {
        // Test with empty concern domains - should return empty array
        $result = $this->service->getFieldsRequiringDagger([]);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_cross_loaded_groups_contain_expected_fields(): void
    {
        $groups = $this->service->getCrossLoadedItemGroups();
        
        // Test specific known cross-loaded groups
        $articulateGroup = null;
        foreach ($groups as $group) {
            if (in_array('A_P_S_ARTICULATE_CL1', $group) && in_array('A_P_S_ARTICULATE_CL2', $group)) {
                $articulateGroup = $group;
                break;
            }
        }
        
        $this->assertNotNull($articulateGroup, 'Articulate clearly group should exist');
        $this->assertContains('A_P_S_ARTICULATE_CL1', $articulateGroup);
        $this->assertContains('A_P_S_ARTICULATE_CL2', $articulateGroup);
    }

    public function test_field_to_domain_mapping_consistency(): void
    {
        $groups = $this->service->getCrossLoadedItemGroups();
        $mapping = $this->service->getFieldToDomainMap();
        
        // Verify that all fields in cross-loaded groups are mapped to domains
        foreach ($groups as $group) {
            foreach ($group as $field) {
                $this->assertArrayHasKey($field, $mapping, "Field {$field} should be mapped to a domain");
            }
        }
    }

    public function test_hygiene_field_spelling_variation(): void
    {
        $mapping = $this->service->getFieldToDomainMap();
        
        // Test the hygiene fields with corrected spelling
        $this->assertArrayHasKey('O_P_HYGEINE_CL1', $mapping); // Physical Health
        $this->assertArrayHasKey('O_P_HYGIENE_CL2', $mapping); // Supports Outside of School
        
        // Both should map to their respective domains
        $this->assertEquals('Physical Health', $mapping['O_P_HYGEINE_CL1']);
        $this->assertEquals('Supports Outside of School', $mapping['O_P_HYGIENE_CL2']);
    }
}