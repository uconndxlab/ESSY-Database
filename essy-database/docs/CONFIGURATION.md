# ESSY Configuration Guide

## Decision Rules Configuration

The ESSY system supports configurable decision rules lookup functionality that can be toggled between the new database-driven approach and the traditional concatenation method.

### Environment Variables

#### ESSY_USE_DECISION_RULES

**Default:** `true`  
**Type:** Boolean  
**Description:** Controls whether the system uses the decision rules lookup functionality.

- `true`: Use database-driven decision rules lookup for contextually appropriate text
- `false`: Use traditional concatenation approach (frequency prefix + item message)

**Example:**
```env
ESSY_USE_DECISION_RULES=true
```

#### ESSY_DECISION_RULES_FALLBACK

**Default:** `true`  
**Type:** Boolean  
**Description:** When decision rules lookup is enabled but a specific rule is not found, this controls the fallback behavior.

- `true`: Fall back to concatenation approach when decision rule not found
- `false`: Skip items when decision rule not found (not recommended for production)

**Example:**
```env
ESSY_DECISION_RULES_FALLBACK=true
```

### Configuration File

The configuration is defined in `config/essy.php`:

```php
return [
    'use_decision_rules' => env('ESSY_USE_DECISION_RULES', true),
    'decision_rules_fallback' => env('ESSY_DECISION_RULES_FALLBACK', true),
];
```

### Usage in Code

Access configuration values using Laravel's `config()` helper:

```php
// Check if decision rules are enabled
if (config('essy.use_decision_rules')) {
    // Use DecisionRulesService
    $decisionText = $this->decisionRulesService->getDecisionText($itemCode, $frequency);
} else {
    // Use traditional concatenation
    $decisionText = "{$prefix} {$message}";
}

// Check fallback setting
if (!$decisionText && config('essy.decision_rules_fallback')) {
    $decisionText = "{$prefix} {$message}";
}
```

### Deployment Considerations

#### Development Environment
- Set `ESSY_USE_DECISION_RULES=true` to test new functionality
- Set `ESSY_DECISION_RULES_FALLBACK=true` for graceful degradation

#### Production Environment
- Set `ESSY_USE_DECISION_RULES=true` (decision rules are extracted from uploaded Excel files automatically)
- Always keep `ESSY_DECISION_RULES_FALLBACK=true` in production
- No separate import commands needed - decision rules are extracted during normal file upload

#### Testing Environment
- Use `ESSY_USE_DECISION_RULES=true` to test decision rules functionality
- Use `ESSY_USE_DECISION_RULES=false` to test backward compatibility

### Troubleshooting

#### Decision Rules Not Working
1. Check that `ESSY_USE_DECISION_RULES=true` in your `.env` file
2. Verify that uploaded Excel files contain a "Decision Rules" sheet with proper format
3. Check application logs for decision rules lookup errors

#### Falling Back to Concatenation
1. Check that `ESSY_DECISION_RULES_FALLBACK=true` in your `.env` file
2. Review logs to see which decision rules are missing
3. Ensure uploaded Excel files contain all required decision rules in the "Decision Rules" sheet

#### Performance Issues
1. Consider setting `ESSY_USE_DECISION_RULES=false` temporarily
2. Check database indexes on decision_rules table
3. Monitor query performance in application logs