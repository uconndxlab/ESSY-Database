# Implementation Plan

- [x] 1. Create CrossLoadedDomainService with core functionality
  - Create new service class with dependency injection for logging
  - Implement cross-loaded item group configuration extraction from current template
  - Add comprehensive validation methods for configuration integrity
  - Implement safe field value retrieval with null checking and error handling
  - _Requirements: 1.1, 3.1, 4.1_

- [x] 2. Implement validation and error handling infrastructure
  - Create ValidationResult value object for structured error reporting
  - Implement database field validation against ReportData model
  - Add configuration validation for cross-loaded item groups
  - Create comprehensive error logging with structured context
  - _Requirements: 3.1, 4.1, 4.2, 4.3_

- [x] 3. Create value objects and DTOs for type safety
  - Implement ProcessedItem value object for structured item data
  - Create DomainProcessingResult for domain processing outcomes
  - Add type-safe constructors with readonly properties
  - Implement validation methods for value object integrity
  - _Requirements: 3.1, 4.1_

- [x] 4. Enhance ReportData model with cross-loaded domain methods
  - Add getConcernDomains method with robust domain detection logic
  - Implement safe attribute access methods with null checking
  - Create domain rating validation and cleaning methods
  - Add confidence flag detection with error handling
  - _Requirements: 1.1, 1.2, 3.1, 4.1_

- [x] 5. Create ReportTemplateHelper for template logic extraction
  - Implement template helper class with dependency injection
  - Create formatItemWithDagger method with safe string manipulation
  - Add processItemsForDomain method with comprehensive error handling
  - Implement safeProcessItem with validation and fallback logic
  - _Requirements: 1.1, 1.2, 2.1, 4.1_

- [x] 6. Extract and refactor cross-loaded configuration from template
  - Move cross-loaded item groups from Blade template to service configuration
  - Create structured configuration with proper field name handling
  - Implement field-to-domain mapping with validation
  - Add configuration validation on service initialization
  - _Requirements: 1.1, 3.1, 4.1_

- [x] 7. Implement dagger detection logic with error handling
  - Create getFieldsRequiringDagger method with robust logic
  - Handle edge cases for cross-loaded item detection
  - Implement fallback behavior when dagger logic encounters errors
  - Add comprehensive logging for dagger detection issues
  - _Requirements: 1.1, 1.2, 2.1, 4.1_

- [x] 8. Create comprehensive unit tests for service layer
  - Test CrossLoadedDomainService methods with various data scenarios
  - Validate error handling for malformed configuration and data
  - Test value objects and DTOs with edge cases
  - Create mock data for testing cross-loaded scenarios
  - _Requirements: 1.1, 1.2, 2.1, 3.1, 4.1_

- [x] 9. Refactor Blade template to use new service methods
  - Replace embedded cross-loaded logic with service method calls
  - Implement error handling in template for service failures
  - Simplify template code while maintaining existing functionality
  - Add fallback rendering when service methods fail
  - _Requirements: 1.1, 1.2, 2.1, 4.1_

- [x] 10. Add integration tests for complete report generation
  - Test PDF generation with cross-loaded items across multiple domains
  - Validate dagger symbols appear correctly in generated reports
  - Test various combinations of concern domains
  - Verify error handling doesn't break report generation
  - _Requirements: 1.1, 1.2, 2.1, 3.1_

- [x] 11. Create comprehensive field name validation across entire Laravel application
  - Scan all PHP files for database field references and validate against ReportData model
  - Create automated script to detect potential field name typos throughout codebase
  - Validate all field names in Blade templates against actual database fields
  - Check controller methods for correct field name usage
  - _Requirements: 3.1, 4.1, 4.2_

- [x] 12. Implement field name constants and validation helpers
  - Create constants class with all ReportData field names to prevent typos
  - Implement helper methods for safe field access with typo detection
  - Add IDE autocomplete support for field names
  - Create validation middleware for field name usage
  - _Requirements: 3.1, 4.1, 4.3_

- [x] 13. Implement data validation and consistency checks
  - Add validation for field values against expected patterns
  - Implement consistency checks for cross-loaded items across domains
  - Create data integrity validation with error recovery
  - Add monitoring for data quality issues
  - _Requirements: 3.1, 3.2, 4.1, 4.2_

- [x] 14. Create field name validation script for entire codebase
  - Build automated script to scan all PHP and Blade files for field references
  - Compare found field names against ReportData model fillable array
  - Generate report of potential typos and invalid field references
  - Create CI/CD integration to run field validation on code changes
  - _Requirements: 3.1, 4.1, 4.2_

- [x] 15. Implement ReportDataFields constants class
  - Create class with all field names as constants to prevent typos
  - Add static methods for field validation and existence checking
  - Implement autocomplete-friendly field access helpers
  - Create documentation for proper field usage patterns
  - _Requirements: 3.1, 4.1, 4.3_

- [x] 16. Add comprehensive error logging and monitoring
  - Implement structured logging for all cross-loaded domain operations
  - Add error context and debugging information to logs
  - Create monitoring for error rates and patterns
  - Implement alerting for critical cross-loaded domain failures
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 17. Create visual regression tests for PDF output
  - Generate test PDFs with known cross-loaded scenarios
  - Compare PDF outputs before and after implementation
  - Validate dagger symbol placement and formatting
  - Test table layout integrity with cross-loaded items
  - _Requirements: 1.2, 2.1_

- [x] 18. Optimize performance and add caching where appropriate
  - Profile cross-loaded domain processing performance
  - Implement caching for expensive validation operations
  - Optimize database field access patterns
  - Add performance monitoring for report generation
  - _Requirements: 3.1, 4.1_

- [x] 19. Final validation and cleanup
  - Test with real production data scenarios
  - Validate all cross-loaded items display correctly with dagger symbols
  - Clean up any remaining template complexity
  - Document new service methods and configuration options
  - _Requirements: 1.1, 1.2, 2.1, 3.1, 4.1_