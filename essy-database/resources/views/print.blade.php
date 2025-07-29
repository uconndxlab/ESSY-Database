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

        $crossLoadedDomainService = new \App\Services\CrossLoadedDomainService();
        $decisionRulesService = config('essy.use_decision_rules') 
            ? new \App\Services\DecisionRulesService($crossLoadedDomainService)
            : null;

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

    $items = [
        'E_SHARM' => [
            'value' => trim($report->E_SHARM ?? ''),
            'text' => 'engages in <strong>self-harming behaviors</strong>',
            'proceed' => ['Almost never'],
        ],
        'E_BULLIED' => [
            'value' => trim($report->E_BULLIED ?? ''),
            'text' => 'has been <strong>bullied</strong> by other students',
            'proceed' => ['Almost never'],
        ],
        'E_EXCLUDE' => [
            'value' => trim($report->E_EXCLUDE ?? ''),
            'text' => 'experiences <strong>social exclusion</strong> in school',
            'proceed' => ['Almost never'],
        ],
        'E_WITHDRAW' => [
            'value' => trim($report->E_WITHDRAW ?? ''),
            'text' => '<strong>avoids or withdraws</strong> from peers',
            'proceed' => ['Almost never', 'Occasionally'],
        ],
        'E_REGULATE' => [
            'value' => trim($report->E_REGULATE ?? ''),
            'text' => '<strong>regulates emotions</strong>',
            'proceed' => ['Almost always', 'Frequently'],
        ],
        'E_RESTED' => [
            'value' => trim($report->E_RESTED ?? ''),
            'text' => 'appears <strong>well-rested</strong>',
            'proceed' => ['Almost always', 'Frequently'],
        ],
    ];

    foreach ($items as $item) {
        // Handle confidence flags properly
        $hasConfidence = str_contains($item['value'], ',');
        $cleanValue = trim(explode(',', $item['value'])[0]);
        $prefix = ucfirst($cleanValue);
        
        $confidenceFlag = $hasConfidence ? ' *' : '';
        $line = $prefix . ' ' . $item['text'] . '.' . $confidenceFlag;

        if (in_array($cleanValue, $item['proceed'])) {
            $proceedItems[] = $line;
        } else {
            $cautionItems[] = $line;
        }
    }
@endphp

@php

    // Define cross-loaded item relationships using actual database column IDs from ReportData.php model
    $crossLoadItemGroups = [
        ['A_P_ARTICULATE_CL1', 'A_P_ARTICULATE_CL2'], // AV (Acad) / CI (Phys) - Articulates clearly
        ['A_S_ADULTCOMM_CL1', 'A_S_ADULTCOMM_CL2'],   // AW (Acad) / DF (SEWB) - Effectively communicates with adults
        ['A_B_CLASSEXPECT_CL1', 'A_B_CLASSEXPECT_CL2'],// BB (Acad) / BP (Beh) - Follows classroom expectations
        ['A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2'],       // BC (Acad) / BQ (Beh) - Exhibits impulsivity
        ['A_S_CONFIDENT_CL1', 'A_S_CONFIDENT_CL2'],   // BH (Acad) / CW (SEWB) - Displays confidence in self
        ['A_S_POSOUT_CL1', 'A_S_POSOUT_CL2'],         // BI (Acad) / CX (SEWB) - Demonstrates positive outlook
        ['S_P_ACHES_CL1', 'S_P_ACHES_CL2'],           // CM (Phys) / CY (SEWB) - Complains of aches
        ['B_O_HOUSING_CL1', 'B_O_HOUSING_CL2'],       // BY (Beh) / DZ (SOS) - Unstable living situation
        ['B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2'],   // BZ (Beh) / EA (SOS) - Family stressors
        ['B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2'], // CA (Beh) / EB (SOS) - Neighborhood stressors
        ['O_P_HUNGER_CL1', 'O_P_HUNGER_CL2'],         // CN (Phys) / DV (SOS) - Reports being hungry
        ['O_P_HYGIENE_CL1', 'O_P_HYGIENE_CL2'],       // CO (Phys) / DW (SOS) - Hygiene resources (Note: O_P_HYGIENE_CL1 from model)
        ['O_P_CLOTHES_CL1', 'O_P_CLOTHES_CL2'],       // CP (Phys) / DX (SOS) - Adequate clothing
        ['A_S_O_ACTIVITY3_CL1', 'A_S_O_ACTIVITY_CL2', 'A_S_O_ACTIVITY_CL3'] // BJ (Acad) / DJ (SEWB) / EC (SOS) - Extracurricular activity
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

@php
    // Process all domains using the appropriate service based on configuration
    $concernDomains = array_map(fn($domain) => trim(explode('*', $domain)[0]), array_merge($someConcern ?? [], $substantialConcern ?? []));
    
    // Use DecisionRulesService if enabled, otherwise fall back to CrossLoadedDomainService
    $domainService = $decisionRulesService ?? $crossLoadedDomainService;
    
    $academicResults = $domainService->processDomainItems($report, 'Academic Skills', $concernDomains);
    $behaviorResults = $domainService->processDomainItems($report, 'Behavior', $concernDomains);
    $physicalResults = $domainService->processDomainItems($report, 'Physical Health', $concernDomains);
    $sewbResults = $domainService->processDomainItems($report, 'Social & Emotional Well-Being', $concernDomains);
    $sosResults = $domainService->processDomainItems($report, 'Supports Outside of School', $concernDomains);

    // DEBUG: Uncomment below to debug frequency responses and categorization
    /*
    $fieldToDomainMap = $crossLoadedDomainService->getFieldToDomainMap();
    $fieldMessages = $crossLoadedDomainService->getFieldMessages();
    $fieldsThatNeedDagger = $crossLoadedDomainService->getFieldsRequiringDagger($concernDomains);
    
    $debugInfo = [
        'concernDomains' => $concernDomains,
        'sampleFieldValues' => [
            'A_READ' => [
                'rawValue' => $report->A_READ ?? 'NULL',
                'safeValue' => $crossLoadedDomainService->safeGetFieldValue($report, 'A_READ'),
                'category' => $crossLoadedDomainService->categorizeFieldValue('A_READ', $crossLoadedDomainService->safeGetFieldValue($report, 'A_READ') ?? ''),
                'domain' => $fieldToDomainMap['A_READ'] ?? 'NOT FOUND'
            ],
            'A_B_IMPULSE_CL1' => [
                'rawValue' => $report->A_B_IMPULSE_CL1 ?? 'NULL',
                'safeValue' => $crossLoadedDomainService->safeGetFieldValue($report, 'A_B_IMPULSE_CL1'),
                'category' => $crossLoadedDomainService->categorizeFieldValue('A_B_IMPULSE_CL1', $crossLoadedDomainService->safeGetFieldValue($report, 'A_B_IMPULSE_CL1') ?? ''),
                'domain' => $fieldToDomainMap['A_B_IMPULSE_CL1'] ?? 'NOT FOUND'
            ],
            'B_CLINGY' => [
                'rawValue' => $report->B_CLINGY ?? 'NULL',
                'safeValue' => $crossLoadedDomainService->safeGetFieldValue($report, 'B_CLINGY'),
                'category' => $crossLoadedDomainService->categorizeFieldValue('B_CLINGY', $crossLoadedDomainService->safeGetFieldValue($report, 'B_CLINGY') ?? ''),
                'domain' => $fieldToDomainMap['B_CLINGY'] ?? 'NOT FOUND'
            ]
        ]
    ];
    */
    
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
                $crossLoadedGroups = $crossLoadedDomainService->getCrossLoadedItemGroups();
                $fieldMessages = $crossLoadedDomainService->getFieldMessages();
                
                // Get secondary/tertiary fields from cross-loaded groups
                $secondaryOrTertiaryCrossloadFields = [];
                foreach ($crossLoadedGroups as $group) {
                    // Skip the first field (primary), add the rest as secondary/tertiary
                    for ($i = 1; $i < count($group); $i++) {
                        $secondaryOrTertiaryCrossloadFields[] = $group[$i];
                    }
                }

                foreach ($fieldMessages as $field => $message) {
                    $value = $crossLoadedDomainService->safeGetFieldValue($report, $field);
                    $isComment = str_starts_with($field, 'COMMENTS_');
                    $isSecondaryOrTertiary = in_array($field, $secondaryOrTertiaryCrossloadFields);

                    if (!$isSecondaryOrTertiary && !$isComment && $value === null) {
                        $missingItems[] = $message;
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