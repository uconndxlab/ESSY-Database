<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ESSY Data Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        .label-cell {
            width: 25%;
        }
    </style>
</head>
<body>

    <h2>ESSY Whole Child Screener Report</h2>

    <!-- Demographic Information Table -->
    <table>
        <tr>
            <td class="label-cell"><strong>Student Name:</strong> {{ $report->FN_STUDENT }} {{ $report->LN_STUDENT }}</td>
            <td class="label-cell"><strong>School:</strong> {{ $report->SCHOOL ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Race / Ethnicity:</strong> {{ $report->DEM_RACE }} {{ $report->DEM_ETHNIC == 'No' ? '' : '/ Hispanic' }}</td>
            <td class="label-cell"><strong>Grade:</strong> {{ $report->DEM_GRADE }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Gender:</strong> {{ $report->DEM_GENDER }}</td>
            <td class="label-cell"><strong>Classroom Teacher:</strong> {{ $report->DEM_CLASSTEACH }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>IEP/504:</strong> {{ $report->DEM_IEP }} / {{ $report->DEM_504 }}</td>
            <td></td>
        </tr>
        <tr>
            <td class="label-cell"><strong>English Language Learner:</strong> {{ $report->DEM_ELL }}</td>
            <td class="label-cell"><strong>Chronic Illness:</strong> {{ $report->DEM_CI }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Date of Assessment:</strong> {{ \Carbon\Carbon::parse($report->EndDate)->format('m/d/y') }}</td>
            <td class="label-cell"><strong>Rater:</strong> {{ $report->FN_TEACHER }} {{ $report->LN_TEACHER }}</td>
        </tr>
    </table>

    <!-- Gate 1 Summary -->
    <h3>ESSY Gate 1 Summary of Broad Concerns</h3>
    <p>Broad domain ratings for <strong>{{ $report->FN_STUDENT }} {{ $report->LN_STUDENT }}</strong> suggest the following areas of strength and concern:</p>

    @php
        $domainValues = [
            'Academic Skills' => $report->A_DOMAIN,
            'Behavior' => $report->B_DOMAIN,
            'Social & Emotional Well-Being' => $report->S_DOMAIN,
            'Physical Health' => $report->P_DOMAIN,
            'Supports Outside of School' => $report->O_DOMAIN,
            'Attendance' => $report->ATT_DOMAIN,
        ];

        $substantialStrength = [];
        $someStrength = [];
        $neutral = [];
        $someConcern = [];
        $substantialConcern = [];
        $raterConfidenceFlag = false;

        foreach ($domainValues as $domain => $rating) {
            if (!$rating) continue;

            $hasConfidenceNote = str_contains($rating, 'Check here');
            $cleanRating = explode(',', $rating)[0];
            $domainLabel = $domain . ($hasConfidenceNote ? '*' : '');

            if ($hasConfidenceNote) {
                $raterConfidenceFlag = true;
            }

            switch (trim(strtolower($cleanRating))) {
                case 'an area of substantial strength':
                    $substantialStrength[] = $domainLabel;
                    break;
                case 'an area of some strength':
                    $someStrength[] = $domainLabel;
                    break;
                case 'neither an area of concern or strength':
                    $neutral[] = $domainLabel;
                    break;
                case 'an area of some concern':
                    $someConcern[] = $domainLabel;
                    break;
                case 'an area of substantial  concern':
                    $substantialConcern[] = $domainLabel;
                    break;
            }
        }

        $crossLoadedDomainService = $crossLoadedDomainService ?? new \App\Services\CrossLoadedDomainService();
        $decisionRulesService = $decisionRulesService ?? (config('essy.use_decision_rules') 
            ? new \App\Services\DecisionRulesService($crossLoadedDomainService)
            : null);

        $notOfConcern = array_diff(
            array_keys($domainValues),
            array_map(fn($v) => str_replace('*', '', $v), array_merge($someConcern, $substantialConcern))
        );
        $notOfConcernText = $crossLoadedDomainService->formatDomainsToText($notOfConcern);

        $ofConcern = array_unique(array_map(fn($v) => str_replace('*', '', $v), array_merge($someConcern, $substantialConcern)));
        $ofConcernText = $crossLoadedDomainService->formatDomainsToText($ofConcern);
    @endphp


    <table>
        <thead>
            <tr>
                <th style="background-color: #C8E6C9;">Area of Substantial Strength</th>
                <th style="background-color: #DCEDC8;">Area of Some Strength</th>
                <th style="background-color: #BBDEFB;">Area of Neither Strength Nor Concern</th>
                <th style="background-color: #F8BBD0;">Area of Some Concern</th>
                <th style="background-color: #EF9A9A;">Area of Substantial Concern</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background-color: #C8E6C9;">
                    @foreach ($substantialStrength as $domain)
                        <div>{{ $domain }}</div>
                    @endforeach
                </td>
                <td style="background-color: #DCEDC8;">
                    @foreach ($someStrength as $domain)
                        <div>{{ $domain }}</div>
                    @endforeach
                </td>
                <td style="background-color: #BBDEFB;">
                    @foreach ($neutral as $domain)
                        <div>{{ $domain }}</div>
                    @endforeach
                </td>
                <td style="background-color: #F8BBD0;">
                    @foreach ($someConcern as $domain)
                        <div>{{ $domain }}</div>
                    @endforeach
                </td>
                <td style="background-color: #EF9A9A;">
                    @foreach ($substantialConcern as $domain)
                        <div>{{ $domain }}</div>
                    @endforeach
                </td>
            </tr>
        </tbody>
    </table>

    <p><strong>In addition, please consider the following information regarding endorsed items:</strong></p>
    <table>
        <tbody>
            <td>
                <p><strong>Proceed:</strong></p>
                <ul>
                    @if (
                        str_contains(strtolower($report->RELATION_CLOSE), 'positive') &&
                        (str_contains($report->RELATION_CONFLICT, 'No conflict') ||
                        str_contains($report->RELATION_CONFLICT, 'Low conflict'))
                    )
                        <p>Student-rater relationship</p>
                    @endif
                    @if (
                        $report->SPEEDING_GATE1 != 1 &&
                        $report->SPEEDING_ESS != 1 &&
                        $report->SPEEDING_GATE2 != 1
                    )
                    <li> Potentially fast completion time - </li>
                    @endif


                </ul>
            </td>
            <td>
                <p><strong>Caution:</strong></p>
                <ul>
                    @if ($raterConfidenceFlag)
                        <li>*Rater confidence - see specific domains above</li>
                    @endif

                    @if (
                        (str_contains($report->RELATION_CLOSE, 'Somewhat negative') || 
                        str_contains($report->RELATION_CLOSE, 'Strong and negative') ||
                        str_contains($report->RELATION_CLOSE, 'Neither positive nor negative')) ||
                        (str_contains(strtolower($report->RELATION_CONFLICT), 'high') || str_contains(strtolower($report->RELATION_CONFLICT), 'some'))
                    )
                        <li>Student-rater relationship</li>
                    @endif
                    @if (
                        $report->SPEEDING_GATE1 == 1 ||
                        $report->SPEEDING_ESS == 1 ||
                        $report->SPEEDING_GATE2 == 1
                    )
                    <li> Potentially fast completion time - 
                        @php
                            $sections = [];

                            if ($report->SPEEDING_GATE1 == 1) $sections[] = 'Gate 1';
                            if ($report->SPEEDING_ESS == 1)  $sections[] = 'Essential Items';
                            if ($report->SPEEDING_GATE2 == 1) $sections[] = 'Gate 2';
                        @endphp

                        {{ implode(', ', $sections) }}
                    </li>
                    @endif

                </ul>
                
            </td>

        </tbody>
    </table>

    <!-- Page #2 ---------------------->
    <br/><br/><br/>
    <hr/>
    <br/>

    <table>
        <tr>
            <td class="label-cell"><strong>Student Name:</strong> {{ $report->FN_STUDENT }} {{ $report->LN_STUDENT }}</td>
            <td class="label-cell"><strong>Rater:</strong> {{ $report->FN_TEACHER }} {{ $report->LN_TEACHER }}</td>
        </tr>
    </table>

    <br/>

    @php
    $proceedItems = [];
    $cautionItems = [];

    // Essential Items configuration - using DecisionRulesService for proper text
    $essentialItemsConfig = [
        'E_SHARM' => [
            'field' => 'E_SHARM',
            'proceed' => ['Almost Never'],
        ],
        'E_BULLIED' => [
            'field' => 'E_BULLIED', 
            'proceed' => ['Almost Never'],
        ],
        'E_EXCLUDE' => [
            'field' => 'E_EXCLUDE',
            'proceed' => ['Almost Never'],
        ],
        'E_WITHDRAW' => [
            'field' => 'E_WITHDRAW',
            'proceed' => ['Almost Never', 'Occasionally'],
        ],
        'E_REGULATE' => [
            'field' => 'E_REGULATE',
            'proceed' => ['Almost Always', 'Frequently'],
        ],
        'E_RESTED' => [
            'field' => 'E_RESTED',
            'proceed' => ['Almost Always', 'Frequently'],
        ],
    ];

    // Use DecisionRulesService to get proper Essential Items text
    $decisionService = app(\App\Services\DecisionRulesService::class);

    foreach ($essentialItemsConfig as $config) {
        $fieldName = $config['field'];
        $rawValue = trim($report->{$fieldName} ?? '');
        
        if (empty($rawValue) || $rawValue === '-99') {
            continue; // Skip items without responses
        }

        // Handle confidence flags properly
        $hasConfidence = str_contains($rawValue, ',');
        $cleanValue = trim(explode(',', $rawValue)[0]);
        
        // Get the proper Essential Items text from DecisionRulesService
        $essentialText = $decisionService->getDecisionText($fieldName, $cleanValue);
        
        if ($essentialText) {
            $confidenceFlag = $hasConfidence ? ' *' : '';
            $line = $essentialText . $confidenceFlag;

            if (in_array($cleanValue, $config['proceed'])) {
                $proceedItems[] = $line;
            } else {
                $cautionItems[] = $line;
            }
        }
    }
@endphp

@php

    // Define cross-loaded item relationships using actual database column IDs from ReportData.php model
    $crossLoadItemGroups = [
        ['A_P_S_ARTICULATE_CL1', 'A_P_S_ARTICULATE_CL2', 'A_P_S_ARTICULATE_CL3'], // Articulates clearly - 3 domains
        ['A_S_ADULTCOMM_CL1', 'A_S_ADULTCOMM_CL2'],   // AW (Acad) / DF (SEWB) - Effectively communicates with adults
        ['A_B_DIRECTIONS_CL1', 'A_B_DIRECTIONS_CL2'], // Understands directions - Academic / Behavior
        ['A_B_CLASSEXPECT_CL1', 'A_B_CLASSEXPECT_CL2'],// BB (Acad) / BP (Beh) - Follows classroom expectations
        ['A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2'],       // BC (Acad) / BQ (Beh) - Exhibits impulsivity
        ['A_S_CONFIDENT_CL1', 'A_S_CONFIDENT_CL2'],   // BH (Acad) / CW (SEWB) - Displays confidence in self
        ['A_S_POSOUT_CL1', 'A_S_POSOUT_CL2'],         // BI (Acad) / CX (SEWB) - Demonstrates positive outlook
        ['S_P_ACHES_CL1', 'S_P_ACHES_CL2'],           // CM (Phys) / CY (SEWB) - Complains of aches
        ['B_O_HOUSING_CL1', 'B_O_HOUSING_CL2'],       // BY (Beh) / DZ (SOS) - Unstable living situation
        ['B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2'],   // BZ (Beh) / EA (SOS) - Family stressors
        ['B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2'], // CA (Beh) / EB (SOS) - Neighborhood stressors
        ['O_P_HUNGER_CL1', 'O_P_HUNGER_CL2'],         // CN (Phys) / DV (SOS) - Reports being hungry
        ['O_P_HYGIENE_CL1', 'O_P_HYGIENE_CL2'],       // CO (Phys) / DW (SOS) - Hygiene resources (Note: corrected spelling)
        ['O_P_CLOTHES_CL1', 'O_P_CLOTHES_CL2'],       // CP (Phys) / DX (SOS) - Adequate clothing
        ['S_O_COMMCONN_CL1', 'S_O_COMMCONN_CL2'],     // Community connection - SEWB / SOS
        ['A_S_O_ACTIVITY_CL1', 'A_S_O_ACTIVITY_CL2', 'A_S_O_ACTIVITY_CL3'] // BJ (Acad) / DJ (SEWB) / EC (SOS) - Extracurricular activity
    ];


@endphp


    <p><strong>ESSY Gate 2 Summary of Specific Concerns</strong></p>
    <p>Before reviewing Gate 2 ratings, please consider results on the following essential items:</p>

    <table>
        <tbody>
            <td>
                <p><strong>Proceed:</strong></p>
                <ul>
                    @foreach ($proceedItems as $item)
                        <li>{!! $item !!}</li>
                    @endforeach
                </ul>
            </td>
            <td>
                <p><strong>Caution:</strong></p>
                <ul>
                    @foreach ($cautionItems as $item)
                        <li>{!! $item !!}</li>
                    @endforeach
                </ul>
            </td>
        </tbody>
    </table>

    @if (!empty($notOfConcernText))
        <p>
            At Gate 1, the following domains were not identified as areas of concern:
            <strong>{{ $notOfConcernText }}</strong>.
            Therefore, no additional items in these domains were rated.
            These domains are not included in the table below.
        </p>
    @endif

    @if (!empty($ofConcernText))
        <p>
            However, at Gate 1, the following domains were identified as areas of some or substantial concern:
            <strong>{{ $ofConcernText }}</strong>.
            Therefore, additional items within each of these domains were rated at Gate 2 and are shown below.
        </p>
    @endif

    <p>The table below is organized using three categories: strengths to maintain, areas to monitor 
    (e.g., watch, gather additional data), and concerns for follow-up (problem solve, intervene).</p>

    <!-- DEBUG: After table description text -->

@php
    // Process all domains using the appropriate service based on configuration
    $concernDomains = array_map(fn($domain) => trim(explode('*', $domain)[0]), array_merge($someConcern, $substantialConcern));
    
    // Use DecisionRulesService if enabled, otherwise fall back to CrossLoadedDomainService
    $domainService = $decisionRulesService ?? $crossLoadedDomainService;
    
    try {
        $academicResults = $domainService->processDomainItems($report, 'Academic Skills', $concernDomains);
        $behaviorResults = $domainService->processDomainItems($report, 'Behavior', $concernDomains);
        $physicalResults = $domainService->processDomainItems($report, 'Physical Health', $concernDomains);
        $sewbResults = $domainService->processDomainItems($report, 'Social & Emotional Well-Being', $concernDomains);
        $sosResults = $domainService->processDomainItems($report, 'Supports Outside of School', $concernDomains);
    } catch (Exception $e) {
        // Fallback to empty arrays if there's an error
        $academicResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
        $behaviorResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
        $physicalResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
        $sewbResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
        $sosResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
    }
@endphp

{{-- DEBUG INFORMATION - Uncomment to debug frequency responses and categorization
<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; font-family: monospace; font-size: 12px;">
    <h4>DEBUG: Frequency Responses and Categorization</h4>
    <pre>{{ print_r($debugInfo, true) }}</pre>
    
    <h4>DEBUG: Domain Processing Results</h4>
    <pre>Academic Results: {{ print_r($academicResults, true) }}</pre>
    <pre>Behavior Results: {{ print_r($behaviorResults, true) }}</pre>
</div>
--}}



{{-- Table View --}}

<table>
    <thead>
        <tr>
            <th>Domain</th>
            <th style="background-color: #C8E6C9;">Strengths to Maintain</th>
            <th style="background-color: #BBDEFB;">Areas to Monitor</th>
            <th style="background-color: #EF9A9A;">Concerns for Follow Up</th>
        </tr>
    </thead>
    <tbody>
        @if(in_array('Academic Skills', $concernDomains))
        <tr>
            <td>Academic Skills</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($academicResults['strengths'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($academicResults['monitor'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($academicResults['concerns'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Behavior', $concernDomains))
        <tr>
            <td>Behavior</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($behaviorResults['strengths'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($behaviorResults['monitor'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($behaviorResults['concerns'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Physical Health', $concernDomains))
        <tr>
            <td>Physical Health</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($physicalResults['strengths'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($physicalResults['monitor'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($physicalResults['concerns'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Social & Emotional Well-Being', $concernDomains))
        <tr>
            <td>Social & Emotional Well-Being</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($sewbResults['strengths'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($sewbResults['monitor'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($sewbResults['concerns'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Supports Outside of School', $concernDomains))
        <tr>
            <td>Supports Outside of School</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($sosResults['strengths'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($sosResults['monitor'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($sosResults['concerns'] as $item)
                    <p>{!! $item !!}</p>
                @endforeach
            </td>
        </tr>
        @endif
    </tbody>
</table>


<p>* Rater reported less confidence in these responses.</p>
<p>† Item appears on multiple domains.</p>
<br/>
<h4>Additional Information</h4>
<table>
    <tr>
        <td>
            # of unanswered items:
            @php
                $missingItems = [];
                $crossLoadedGroups = $crossLoadItemGroups;
                $fieldMessages = $crossLoadedDomainService->getFieldMessages();
                $fieldToDomainMap = $crossLoadedDomainService->getFieldToDomainMap();
                
                // Track which cross-loaded groups have been processed to avoid duplicates
                $processedCrossLoadedGroups = [];
                
                foreach ($fieldMessages as $field => $message) {
                    // Skip comment fields
                    if (str_starts_with($field, 'COMMENTS_')) {
                        continue;
                    }
                    
                    // Skip Gate 2 essential items - they should not be counted as unanswered
                    // Gate 2 items should only appear if they are concerns, not because they're unanswered
                    if (in_array($field, ['E_SHARM', 'E_BULLIED', 'E_EXCLUDE', 'E_WITHDRAW', 'E_REGULATE', 'E_RESTED'])) {
                        continue;
                    }
                    
                    // Only count fields from concern domains
                    $fieldDomain = $fieldToDomainMap[$field] ?? null;
                    if (!$fieldDomain || !in_array($fieldDomain, $concernDomains)) {
                        continue;
                    }
                    
                    // Check if this field is part of a cross-loaded group
                    $crossLoadedGroupIndex = null;
                    foreach ($crossLoadedGroups as $groupIndex => $group) {
                        if (in_array($field, $group)) {
                            $crossLoadedGroupIndex = $groupIndex;
                            break;
                        }
                    }
                    
                    // If this field is part of a cross-loaded group
                    if ($crossLoadedGroupIndex !== null) {
                        // Skip if we've already processed this cross-loaded group
                        if (isset($processedCrossLoadedGroups[$crossLoadedGroupIndex])) {
                            continue;
                        }
                        
                        // Check if ANY field in the cross-loaded group has a value OR is specifically unanswered (-99)
                        $groupHasValue = false;
                        $groupHasUnanswered = false;
                        $group = $crossLoadedGroups[$crossLoadedGroupIndex];
                        foreach ($group as $groupField) {
                            $rawValue = trim($report->getAttribute($groupField) ?? '');
                            if (!empty($rawValue) && $rawValue !== '-99') {
                                $groupHasValue = true;
                                break;
                            } elseif ($rawValue === '-99') {
                                $groupHasUnanswered = true;
                            }
                        }
                        
                        // Only count as missing if the group has -99 values (specifically unanswered)
                        // Don't count empty fields as missing - they weren't presented
                        if (!$groupHasValue && $groupHasUnanswered) {
                            // Find a field from a concern domain to use as representative
                            $representativeField = null;
                            $fieldsFromConcernDomains = [];
                            
                            foreach ($group as $groupField) {
                                $groupFieldDomain = $fieldToDomainMap[$groupField] ?? null;
                                if ($groupFieldDomain && in_array($groupFieldDomain, $concernDomains)) {
                                    $fieldsFromConcernDomains[] = $groupField;
                                }
                            }
                            
                            // Only count as missing if we have fields from concern domains in this group
                            if (!empty($fieldsFromConcernDomains)) {
                                // Use the first field from a concern domain as representative
                                $representativeField = $fieldsFromConcernDomains[0];
                                $representativeMessage = $fieldMessages[$representativeField] ?? $message;
                                $missingItems[] = $representativeMessage;
                            }
                        }
                        
                        // Mark this group as processed
                        $processedCrossLoadedGroups[$crossLoadedGroupIndex] = true;
                    } else {
                        // For non-cross-loaded fields, check if specifically unanswered (-99)
                        $rawValue = trim($report->getAttribute($field) ?? '');
                        if ($rawValue === '-99') {
                            $missingItems[] = $message;
                        }
                        // Don't count empty fields - they weren't presented
                    }
                }
            @endphp

            {{ count($missingItems) }}
            @if (!empty($missingItems))
                <ul>
                    @foreach ($missingItems as $msg)
                        <li>{{ $msg }}</li>
                    @endforeach
                </ul>
            @endif
        </td>
    </tr>
    <tr>
        <td>
            Rater comments:
            <ul>
                @php
                    $comments = [
                        'COMMENTS_GATE1' => 'Gate 1',
                        'COMMENTS_STR'   => 'Student-rater relationship',
                        'COMMENTS_ESS'   => 'Essential Items',
                        'COMMENTS_AS'    => 'Academics',
                        'COMMENTS_BEH'   => 'Behavior',
                        'COMMENTS_PH'    => 'Physical Health',
                        'COMMENTS_SEW'   => 'Social & Emotional Well-Being',
                        'COMMENTS_SOS'   => 'Supports Outside of School',
                    ];
                @endphp

                @foreach ($comments as $field => $label)
                    @php
                        $commentValue = $report->$field ?? '';
                        $cleanValue = trim($commentValue);
                    @endphp
                    @if (!empty($cleanValue) && $cleanValue !== '-99')
                        <li><strong>{{ $label }}:</strong> {{ $cleanValue }}</li>
                    @endif
                @endforeach
            </ul>
        </td>
    </tr>
</table>




</body>
</html>