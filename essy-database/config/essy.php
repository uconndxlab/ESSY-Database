<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Decision Rules Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the decision rules lookup functionality.
    | When enabled, the system will query the decision_rules database table
    | for contextually appropriate text. When disabled, items will be skipped.
    |
    */

    'use_decision_rules' => env('ESSY_USE_DECISION_RULES', true),



    /*
    |--------------------------------------------------------------------------
    | Decision Rules Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for decision rule lookup attempts, matches,
    | and fallback usage. This is useful for debugging field name mismatches
    | and understanding why certain items appear as unanswered.
    |
    */

    'log_decision_rule_lookups' => env('ESSY_LOG_DECISION_RULE_LOOKUPS', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Item Processing
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for item processing decisions, including
    | field value extraction, cross-loaded logic, and categorization.
    | This provides comprehensive debugging information for report generation.
    |
    */

    'debug_item_processing' => env('ESSY_DEBUG_ITEM_PROCESSING', false),

];