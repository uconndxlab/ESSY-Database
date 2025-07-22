# Requirements Document

## Introduction

The ESSY database application generates PDF reports from Excel data with a two-gate assessment system. At Gate 1, domains are categorized into strength/concern levels. At Gate 2, domains identified as areas of concern have additional items rated and displayed in tables. Some items appear across multiple domains (cross-loaded) and should be marked with a dagger symbol (†) to indicate this relationship. Currently, the cross-loaded domain functionality is not working correctly and needs to be fixed.

## Requirements

### Requirement 1

**User Story:** As a report generator, I want to correctly identify cross-loaded items that appear in multiple domains, so that users can understand which assessment items span across different areas of evaluation.

#### Acceptance Criteria

1. WHEN an assessment item appears in multiple domains THEN the system SHALL identify it as cross-loaded
2. WHEN an item is cross-loaded THEN the system SHALL mark it with a dagger symbol (†) in all domains where it appears
3. WHEN generating Gate 2 tables THEN the system SHALL preserve cross-loaded item markings across all relevant domain sections

### Requirement 2

**User Story:** As a report viewer, I want cross-loaded items to be visually distinguished with dagger symbols, so that I can identify items that influence multiple assessment domains.

#### Acceptance Criteria

1. WHEN viewing a Gate 2 report table THEN cross-loaded items SHALL display a dagger symbol (†) immediately after the item text
2. WHEN an item appears in only one domain THEN it SHALL NOT display a dagger symbol
3. WHEN multiple cross-loaded items exist in a domain THEN each SHALL have its own dagger symbol

### Requirement 3

**User Story:** As a system administrator, I want the cross-loaded domain detection to work consistently across all report generations, so that all PDF reports maintain accurate cross-reference indicators.

#### Acceptance Criteria

1. WHEN processing Excel data THEN the system SHALL consistently identify duplicate items across domains
2. WHEN generating multiple reports THEN cross-loaded item detection SHALL produce identical results for identical data
3. IF an item text matches exactly across domains THEN it SHALL be marked as cross-loaded
4. WHEN item text has minor variations (spacing, punctuation) THEN the system SHALL normalize text for comparison

### Requirement 4

**User Story:** As a developer, I want clear error handling for cross-loaded domain processing, so that report generation doesn't fail when cross-loaded items are encountered.

#### Acceptance Criteria

1. WHEN cross-loaded item detection fails THEN the system SHALL log the error and continue report generation
2. WHEN dagger symbol insertion fails THEN the system SHALL display the item without the symbol rather than failing completely
3. WHEN duplicate detection encounters malformed data THEN the system SHALL handle gracefully with appropriate logging