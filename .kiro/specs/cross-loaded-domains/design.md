# Design Document

## Overview

The ESSY application currently has a cross-loaded domain system implemented in the Blade template (`print.blade.php`) that identifies items appearing across multiple domains and marks them with dagger symbols (†). However, the current implementation has issues that prevent the cross-loaded functionality from working correctly.

The system processes assessment data through a two-gate approach where Gate 1 identifies broad domain concerns, and Gate 2 provides detailed item-level analysis for domains flagged as concerns. Cross-loaded items are assessment questions that appear in multiple domains and should be marked with a dagger symbol when both domains are flagged as concerns.

## Architecture

### Current Implementation Analysis

The existing system uses:
- **Cross-load Item Groups**: Defined as arrays of database field names that represent the same assessment item across different domains
- **Field-to-Domain Mapping**: Maps database fields to their respective domains
- **Dagger Logic**: Determines which fields need dagger symbols based on cross-domain presence in concern areas
- **Template Processing**: Blade template processes each domain's indicators and applies dagger symbols

### Identified Issues

1. **Field Name Variations**: Cross-loaded items have different field names across domains (e.g., `O_P_HYGEINE_CL1` vs `O_P_HYGIENE_CL2`) which are separate database fields representing the same concept
2. **Logic Gaps**: The dagger detection logic may not properly handle all cross-loaded scenarios or edge cases
3. **Template Complexity**: All cross-loaded logic is embedded in the Blade template, making it difficult to debug, test, and maintain
4. **Missing Validation**: No validation to ensure cross-loaded item groups are properly configured or that database fields exist
5. **Error Handling**: Limited error handling for missing data, malformed values, or configuration issues
6. **Code Safety**: No type safety, null checks, or defensive programming practices in the current implementation

## Components and Interfaces

### 1. CrossLoadedDomainService

A new service class to handle cross-loaded domain logic with comprehensive error handling:

```php
class CrossLoadedDomainService
{
    private array $crossLoadedItemGroups;
    private array $fieldToDomainMap;
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    public function getCrossLoadedItemGroups(): array
    public function getFieldToDomainMap(): array
    public function getFieldsRequiringDagger(array $concernDomains): array
    public function validateCrossLoadedConfiguration(): ValidationResult
    public function validateDatabaseFields(array $modelFields): ValidationResult
    public function safeGetFieldValue(ReportData $report, string $field): ?string
    public function logCrossLoadedError(string $message, array $context = []): void
}
```

### 2. ReportData Model Enhancement

Extend the existing model with cross-loaded domain methods and safety checks:

```php
class ReportData extends Model
{
    public function getConcernDomains(): array
    public function getItemValue(string $field): ?string
    public function hasValidValue(string $field): bool
    public function safeGetAttribute(string $field, $default = null): mixed
    public function validateDomainRating(string $domain): bool
    public function getCleanRating(string $rawValue): ?string
    public function hasConfidenceFlag(string $rawValue): bool
}
```

### 3. Template Helper Methods

Create helper methods to simplify template logic with error handling:

```php
class ReportTemplateHelper
{
    private CrossLoadedDomainService $crossLoadedService;
    private LoggerInterface $logger;
    
    public function __construct(CrossLoadedDomainService $service, LoggerInterface $logger)
    public function formatItemWithDagger(string $item, string $field, array $daggerFields): string
    public function processItemsForDomain(string $domain, array $indicators, ReportData $report): DomainProcessingResult
    public function safeProcessItem(string $field, string $message, ReportData $report): ?ProcessedItem
    public function handleProcessingError(Exception $e, string $context): void
}
```

### 4. Value Objects and DTOs

Create structured data objects for better type safety:

```php
class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly array $warnings = []
    ) {}
}

class ProcessedItem
{
    public function __construct(
        public readonly string $text,
        public readonly string $category, // 'strengths', 'monitor', 'concerns'
        public readonly bool $hasConfidence,
        public readonly bool $hasDagger
    ) {}
}

class DomainProcessingResult
{
    public function __construct(
        public readonly array $strengths,
        public readonly array $monitor,
        public readonly array $concerns,
        public readonly array $errors = []
    ) {}
}
```

## Data Models

### Cross-Loaded Item Configuration

```php
// Cross-loaded item groups with proper field validation
$crossLoadItemGroups = [
    'articulate_clearly' => [
        'primary' => 'A_P_ARTICULATE_CL1',
        'secondary' => ['A_P_ARTICULATE_CL2'],
        'domains' => ['Academic Skills', 'Physical Health']
    ],
    'communicate_adults' => [
        'primary' => 'A_S_ADULTCOMM_CL1', 
        'secondary' => ['A_S_ADULTCOMM_CL2'],
        'domains' => ['Academic Skills', 'Social & Emotional Well-Being']
    ],
    // ... other groups
];
```

### Domain Indicator Structure

```php
// Standardized domain indicator structure
$domainIndicators = [
    'domain_name' => [
        'field_name' => [
            'message' => 'assessment item text',
            'type' => 'positive|negative', // for interpretation logic
            'cross_loaded' => true|false
        ]
    ]
];
```

## Error Handling

### 1. Configuration Validation
- **Field Existence**: Validate that all cross-loaded item groups reference actual database fields from the ReportData model
- **Domain Mapping**: Check that field-to-domain mappings are consistent and complete
- **Cross-Reference Integrity**: Ensure no orphaned cross-loaded references or circular dependencies
- **Type Safety**: Validate configuration structure matches expected schema

### 2. Runtime Error Handling
- **Null Safety**: Implement null coalescing and safe navigation for all field access
- **Missing Data**: Graceful handling of missing field values with appropriate defaults
- **Malformed Values**: Sanitize and validate raw field values before processing
- **Fallback Behavior**: When dagger logic fails, continue processing without daggers rather than breaking
- **Exception Wrapping**: Catch and wrap exceptions with contextual information
- **Logging**: Comprehensive logging of cross-loaded domain processing errors with structured context

### 3. Data Integrity Checks
- **Value Validation**: Validate that field values match expected patterns (e.g., rating scales)
- **Consistency Checks**: Verify cross-loaded items have logically consistent values across domains
- **Domain Detection**: Robust validation of concern domain detection with error recovery
- **Field Access**: Safe attribute access with existence checks before reading model properties

### 4. Defensive Programming Practices
- **Input Sanitization**: Clean and validate all input data before processing
- **Boundary Checks**: Validate array indices and collection access
- **Type Checking**: Explicit type validation for critical operations
- **Circuit Breaker**: Stop processing if too many errors occur to prevent cascading failures
- **Graceful Degradation**: Provide meaningful fallbacks when components fail

### 5. Error Recovery Strategies
- **Partial Processing**: Continue processing other domains if one domain fails
- **Default Values**: Provide sensible defaults for missing or invalid data
- **User Feedback**: Clear error messages for administrators when issues occur
- **Monitoring**: Track error rates and patterns for proactive maintenance

## Testing Strategy

### 1. Unit Tests
- Test cross-loaded item group configuration
- Validate dagger symbol logic for various scenarios
- Test field-to-domain mapping accuracy
- Verify error handling for malformed data

### 2. Integration Tests
- Test complete report generation with cross-loaded items
- Validate PDF output contains correct dagger symbols
- Test with various combinations of concern domains
- Verify template rendering with cross-loaded items

### 3. Data Validation Tests
- Test with real Excel import data
- Validate cross-loaded detection with edge cases
- Test field naming consistency across domains
- Verify missing data handling

### 4. Visual Regression Tests
- Compare PDF outputs before and after fixes
- Validate dagger symbol placement and formatting
- Test table layout with cross-loaded items
- Verify legend and footnote accuracy

## Implementation Approach

### Phase 1: Service Layer Creation
1. Create `CrossLoadedDomainService` with core logic
2. Extract cross-loaded configuration from template
3. Implement validation and error handling
4. Add comprehensive unit tests

### Phase 2: Model Enhancement
1. Add helper methods to `ReportData` model
2. Implement concern domain detection logic
3. Add data validation methods
4. Create integration tests

### Phase 3: Template Refactoring
1. Simplify Blade template using new service methods
2. Remove embedded cross-loaded logic
3. Implement template helper methods
4. Add visual regression tests

### Phase 4: Bug Fixes and Validation
1. Fix field naming inconsistencies
2. Correct dagger detection logic
3. Validate against real data scenarios
4. Performance optimization and cleanup