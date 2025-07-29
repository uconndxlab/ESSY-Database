<?php

namespace Tests\Unit;

use App\Models\DecisionRule;
use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use App\Services\DecisionRulesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class DecisionRulesServiceTest extends TestCase
{
    use RefreshDatabase;

    private DecisionRulesService $service;
    private CrossLoadedDomainService $crossLoadedService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->crossLoadedService = new CrossLoadedDomainService($this->logger);
        $this->service = new DecisionRulesService($this->crossLoadedService, $this->logger);
    }

    public function test_getDecisionText_returns_decision_text_when_rule_exists()
    {
        // Arrange
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'almost always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always meets grade-level expectations for reading skills.'
        ]);

        // Act
        $result = $this->service->getDecisionText('A_READ', 'almost always');

        // Assert
        $this->assertEquals('The student almost always meets grade-level expectations for reading skills.', $result);
    }

    public function test_getDecisionText_returns_null_when_rule_does_not_exist()
    {
        // Arrange
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('[DecisionRules] Decision rule not found'),
                $this->arrayHasKey('item_code')
            );

        // Act
        $result = $this->service->getDecisionText('NONEXISTENT_FIELD', 'sometimes');

        // Assert
        $this->assertNull($result);
    }

    public function test_getDecisionText_handles_database_exceptions()
    {
        // Arrange
        $this->logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('[DecisionRules] Error retrieving decision text'),
                $this->arrayHasKey('error')
            );

        // Mock DecisionRule to throw exception
        $this->mock(DecisionRule::class, function ($mock) {
            $mock->shouldReceive('getDecisionText')
                ->andThrow(new \Exception('Database error'));
        });

        // Act
        $result = $this->service->getDecisionText('A_READ', 'sometimes');

        // Assert
        $this->assertNull($result);
    }

    public function test_processDomainItems_uses_decision_rules_when_available()
    {
        // Arrange
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'almost always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always meets grade-level expectations for reading skills.'
        ]);

        $report = new ReportData([
            'A_READ' => 'almost always',
            'A_DOMAIN' => 'an area of strength'
        ]);

        // Act
        $result = $this->service->processDomainItems($report, 'Academic Skills', []);

        // Assert
        $this->assertArrayHasKey('strengths', $result);
        $this->assertContains('The student almost always meets grade-level expectations for reading skills.', $result['strengths']);
    }

    public function test_processDomainItems_falls_back_to_concatenation_when_no_decision_rule()
    {
        // Arrange
        $report = new ReportData([
            'A_READ' => 'almost always',
            'A_DOMAIN' => 'an area of strength'
        ]);

        // Act
        $result = $this->service->processDomainItems($report, 'Academic Skills', []);

        // Assert
        $this->assertArrayHasKey('strengths', $result);
        $this->assertContains('Almost always meets grade-level expectations for reading skills.', $result['strengths']);
    }

    public function test_processDomainItems_preserves_confidence_indicators()
    {
        // Arrange
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes meets grade-level expectations for reading skills.'
        ]);

        $report = new ReportData([
            'A_READ' => 'sometimes, Check here if you have low confidence in this rating',
            'A_DOMAIN' => 'an area of some concern'
        ]);

        // Act
        $result = $this->service->processDomainItems($report, 'Academic Skills', []);

        // Assert
        $this->assertArrayHasKey('monitor', $result);
        $this->assertContains('The student sometimes meets grade-level expectations for reading skills. *', $result['monitor']);
    }

    public function test_processDomainItems_preserves_cross_loaded_dagger_symbols()
    {
        // Arrange
        DecisionRule::create([
            'item_code' => 'A_P_ARTICULATE_CL1',
            'frequency' => 'frequently',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student frequently articulates clearly enough to be understood.'
        ]);

        DecisionRule::create([
            'item_code' => 'A_P_ARTICULATE_CL2',
            'frequency' => 'frequently',
            'domain' => 'Physical Health',
            'decision_text' => 'The student frequently articulates clearly enough to be understood.'
        ]);

        $report = new ReportData([
            'A_P_ARTICULATE_CL1' => 'frequently',
            'A_P_ARTICULATE_CL2' => 'frequently',
            'A_DOMAIN' => 'an area of some concern',
            'P_DOMAIN' => 'an area of some concern'
        ]);

        $concernDomains = ['Academic Skills', 'Physical Health'];

        // Act
        $result = $this->service->processDomainItems($report, 'Academic Skills', $concernDomains);

        // Assert
        $this->assertArrayHasKey('strengths', $result);
        $this->assertContains('The student frequently articulates clearly enough to be understood. †', $result['strengths']);
    }

    public function test_processDomainItems_handles_cross_loaded_fallback_values()
    {
        // Arrange
        DecisionRule::create([
            'item_code' => 'A_P_ARTICULATE_CL1',
            'frequency' => 'sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes articulates clearly enough to be understood.'
        ]);

        DecisionRule::create([
            'item_code' => 'A_P_ARTICULATE_CL2',
            'frequency' => 'sometimes',
            'domain' => 'Physical Health',
            'decision_text' => 'The student sometimes articulates clearly enough to be understood.'
        ]);

        $report = new ReportData([
            'A_P_ARTICULATE_CL1' => 'sometimes', // Primary field has value
            'A_P_ARTICULATE_CL2' => null, // Secondary field is empty
            'A_DOMAIN' => 'an area of some concern',
            'P_DOMAIN' => 'an area of some concern'
        ]);

        $concernDomains = ['Academic Skills', 'Physical Health'];

        // Act
        $result = $this->service->processDomainItems($report, 'Physical Health', $concernDomains);

        // Assert
        $this->assertArrayHasKey('monitor', $result);
        $this->assertContains('The student sometimes articulates clearly enough to be understood. †', $result['monitor']);
    }

    public function test_processDomainItems_combines_confidence_and_dagger_symbols()
    {
        // Arrange
        DecisionRule::create([
            'item_code' => 'A_P_ARTICULATE_CL1',
            'frequency' => 'occasionally',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student occasionally articulates clearly enough to be understood.'
        ]);

        DecisionRule::create([
            'item_code' => 'A_P_ARTICULATE_CL2',
            'frequency' => 'occasionally',
            'domain' => 'Physical Health',
            'decision_text' => 'The student occasionally articulates clearly enough to be understood.'
        ]);

        $report = new ReportData([
            'A_P_ARTICULATE_CL1' => 'occasionally, Check here if you have low confidence in this rating',
            'A_P_ARTICULATE_CL2' => 'occasionally, Check here if you have low confidence in this rating',
            'A_DOMAIN' => 'an area of some concern',
            'P_DOMAIN' => 'an area of some concern'
        ]);

        $concernDomains = ['Academic Skills', 'Physical Health'];

        // Act
        $result = $this->service->processDomainItems($report, 'Academic Skills', $concernDomains);

        // Assert
        $this->assertArrayHasKey('concerns', $result);
        $this->assertContains('The student occasionally articulates clearly enough to be understood. * †', $result['concerns']);
    }

    public function test_processDomainItems_skips_items_without_values()
    {
        // Arrange
        $report = new ReportData([
            'A_READ' => null,
            'A_WRITE' => '',
            'A_MATH' => '-99',
            'A_DOMAIN' => 'an area of strength'
        ]);

        // Act
        $result = $this->service->processDomainItems($report, 'Academic Skills', []);

        // Assert
        $this->assertEmpty($result['strengths']);
        $this->assertEmpty($result['monitor']);
        $this->assertEmpty($result['concerns']);
    }

    public function test_processDomainItems_categorizes_items_correctly()
    {
        // Arrange
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'almost always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always meets grade-level expectations for reading skills.'
        ]);

        DecisionRule::create([
            'item_code' => 'A_WRITE',
            'frequency' => 'sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes meets expectations for grade-level writing skills.'
        ]);

        DecisionRule::create([
            'item_code' => 'A_MATH',
            'frequency' => 'almost never',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost never meets expectations for grade-level math skills.'
        ]);

        $report = new ReportData([
            'A_READ' => 'almost always',
            'A_WRITE' => 'sometimes',
            'A_MATH' => 'almost never',
            'A_DOMAIN' => 'an area of some concern'
        ]);

        // Act
        $result = $this->service->processDomainItems($report, 'Academic Skills', []);

        // Assert
        $this->assertCount(1, $result['strengths']);
        $this->assertCount(1, $result['monitor']);
        $this->assertCount(1, $result['concerns']);
        
        $this->assertContains('The student almost always meets grade-level expectations for reading skills.', $result['strengths']);
        $this->assertContains('The student sometimes meets expectations for grade-level writing skills.', $result['monitor']);
        $this->assertContains('The student almost never meets expectations for grade-level math skills.', $result['concerns']);
    }

    public function test_processDomainItems_falls_back_to_cross_loaded_service_on_exception()
    {
        // Arrange
        $report = new ReportData([
            'A_READ' => 'sometimes',
            'A_DOMAIN' => 'an area of strength'
        ]);

        // Mock the cross-loaded service to return expected result
        $expectedResult = ['strengths' => ['Fallback result'], 'monitor' => [], 'concerns' => []];
        $mockCrossLoadedService = $this->createMock(CrossLoadedDomainService::class);
        $mockCrossLoadedService->expects($this->once())
            ->method('processDomainItems')
            ->with($report, 'Academic Skills', [])
            ->willReturn($expectedResult);

        // Create service with mocked dependencies that will throw exception
        $mockCrossLoadedService->method('getFieldMessages')->willThrowException(new \Exception('Test exception'));
        
        $service = new DecisionRulesService($mockCrossLoadedService, $this->logger);

        $this->logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('[DecisionRules] Error processing domain items'),
                $this->arrayHasKey('error')
            );

        // Act
        $result = $service->processDomainItems($report, 'Academic Skills', []);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function test_safeGetFieldValue_delegates_to_cross_loaded_service()
    {
        // Arrange
        $report = new ReportData(['A_READ' => 'sometimes']);
        
        // Act
        $result = $this->service->safeGetFieldValue($report, 'A_READ');

        // Assert
        $this->assertEquals('sometimes', $result);
    }

    public function test_getCrossLoadedValue_handles_exceptions()
    {
        // Arrange
        $report = new ReportData([
            'A_P_ARTICULATE_CL1' => 'frequently',
            'A_P_ARTICULATE_CL2' => null
        ]);

        $this->logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('[DecisionRules] Error getting cross-loaded value'),
                $this->arrayHasKey('error')
            );

        // Create a service with a mock that throws exception
        $mockCrossLoadedService = $this->createMock(CrossLoadedDomainService::class);
        $mockCrossLoadedService->method('getCrossLoadedItemGroups')
            ->willThrowException(new \Exception('Test exception'));

        $service = new DecisionRulesService($mockCrossLoadedService, $this->logger);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getCrossLoadedValue');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($service, $report, 'A_P_ARTICULATE_CL2');

        // Assert
        $this->assertNull($result);
    }
}