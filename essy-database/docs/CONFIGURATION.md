# ESSY Configuration Guide

## Decision Rules Configuration

The ESSY system supports configurable decision rules lookup functionality that uses a database-driven approach for contextually appropriate text.

### Environment Variables

#### ESSY_USE_DECISION_RULES

**Default:** `true`  
**Type:** Boolean  
**Description:** Controls whether the system uses the decision rules lookup functionality.

- `true`: Use database-driven decision rules lookup for contextually appropriate text
- `false`: Skip items when decision rules are disabled

**Example:**
```env
ESSY_USE_DECISION_RULES=true
```



### Configuration File

The configuration is defined in `config/essy.php`:

```php
return [
    'use_decision_rules' => env('ESSY_USE_DECISION_RULES', true),

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
    // Skip items when decision rules are disabled
    continue;
}

// Use decision rule or skip item
if (!$decisionText) {
    // Skip item when no decision rule found
    continue;
}
```

### Deployment Considerations

#### Development Environment
- Set `ESSY_USE_DECISION_RULES=true` to test new functionality
- Ensure all decision rules are imported from Excel file

#### Production Environment
- Set `ESSY_USE_DECISION_RULES=true` (decision rules are extracted from uploaded Excel files automatically)
- Ensure all required decision rules are available
- No separate import commands needed - decision rules are extracted during normal file upload

#### Testing Environment
- Use `ESSY_USE_DECISION_RULES=true` to test decision rules functionality
- Use `ESSY_USE_DECISION_RULES=false` to test backward compatibility

### Troubleshooting

#### Decision Rules Not Working
1. Check that `ESSY_USE_DECISION_RULES=true` in your `.env` file
2. Verify that uploaded Excel files contain a "Decision Rules" sheet with proper format
3. Check application logs for decision rules lookup errors

#### Missing Decision Rules
1. Review logs to see which decision rules are missing
2. Ensure uploaded Excel files contain all required decision rules in the "Decision Rules" sheet
3. Re-import decision rules if necessary

#### Performance Issues
1. Consider setting `ESSY_USE_DECISION_RULES=false` temporarily
2. Check database indexes on decision_rules table
3. Monitor query performance in application logs