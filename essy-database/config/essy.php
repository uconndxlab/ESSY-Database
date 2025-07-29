<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Decision Rules Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the decision rules lookup functionality.
    | When enabled, the system will query the decision_rules database table
    | for contextually appropriate text. When disabled, it falls back to
    | the traditional concatenation approach.
    |
    */

    'use_decision_rules' => env('ESSY_USE_DECISION_RULES', true),

    /*
    |--------------------------------------------------------------------------
    | Decision Rules Fallback
    |--------------------------------------------------------------------------
    |
    | When decision rules lookup is enabled but a specific rule is not found,
    | this setting controls whether to fall back to concatenation or skip
    | the item entirely. True means fallback to concatenation.
    |
    */

    'decision_rules_fallback' => env('ESSY_DECISION_RULES_FALLBACK', true),

];