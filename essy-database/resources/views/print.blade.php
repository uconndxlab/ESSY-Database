<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ESSY Data Report</title>
    <style>
        @page {
            margin: 100px 30px 30px 30px;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
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
            height: auto;
            overflow: visible;
        }
    

        .label-cell {
            width: 25%;
        }
    </style>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body>

    <header style="text-align: center">
        <img src="{{ public_path('assets/essy-logo-trimmed.png') }}" alt="ESSY Logo" style="height:50px;"/>
    </header>

    <div style="page-break-inside: avoid;">
        <p>The ESSY Whole Child Screener is a measure to provide a holistic snapshot of each student. It assesses both individual student characteristics as well as conditions of the student's environment. There are six broad domains of focus: </p>
        <img src="{{ public_path('assets/essy-header-block.png') }}" alt="ESSY Domains" style="width:100%; max-width:800px;"/>
        
        <p>The ESSY Whole Child Screener includes two main sections in the rating process, also known as "gates." A gated process helps us to build efficiency by not asking unnecessary questions for all students, focusing more detailed questions only in areas of need for identified students. </p>

        <p>At Gate 1, raters respond to a single, broad item about each of the six domains to identify areas of strength and concern.</p>

 

        <p>If concerns are identified at Gate 1, raters are presented with more detailed items in the areas of concern (Gate 2) to inform decisions about further assessment or support.</p>
    </div>

<div style="page-break-after: always;"></div>


    <h2 style="text-align: center">ESSY Whole Child Screener Report</h2>

    <!-- Demographic Information Table -->
    <table>
        <tr>
            <td class="label-cell"><strong>Student Name:</strong> {{ $report->FN_STUDENT }} {{ $report->LN_STUDENT }}</td>
            <td class="label-cell"><strong>School:</strong> {{ $report->SCHOOL ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Race / Ethnicity:</strong> 
                @php
                    $raceDisplay = $report->DEM_RACE;
                    $ethnicityDisplay = '';
                    
                    // Handle unanswered race (-99 or empty) - should be completely blank per decision rules C7
                    if (empty($raceDisplay) || $raceDisplay === '-99') {
                        $raceDisplay = '';
                        $ethnicityDisplay = ''; // Don't show ethnicity if race is unanswered
                    } else {
                        // If race contains "Other" and there's text in DEM_RACE_14_TEXT, check if that text is valid
                        if (str_contains($raceDisplay, 'Other') && !empty($report->DEM_RACE_14_TEXT)) {
                            $raceTextValue = trim($report->DEM_RACE_14_TEXT);
                            // If the race text is -99 (unanswered), treat the whole thing as blank per decision rules C7
                            if ($raceTextValue === '-99') {
                                $raceDisplay = '';
                                $ethnicityDisplay = '';
                            } else {
                                // Replace "Other (please specify)" with the actual text
                                $raceDisplay = str_replace(['Other (please specify)', 'Other'], $raceTextValue, $raceDisplay);
                            }
                        }
                        
                        // Only add ethnicity if race is not blank and ethnicity is valid
                        if (!empty($raceDisplay) && $report->DEM_ETHNIC && $report->DEM_ETHNIC !== 'No' && $report->DEM_ETHNIC !== '-99') {
                            $ethnicityDisplay = ' / Hispanic';
                        }
                    }
                    
                    $fullRaceEthnicityDisplay = $raceDisplay . $ethnicityDisplay;
                @endphp
                {{ $fullRaceEthnicityDisplay }}
            </td>
            <td class="label-cell"><strong>Grade:</strong> {{ $report->DEM_GRADE }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Gender:</strong> 
                @php
                    $crossLoadedDomainService = app(\App\Services\CrossLoadedDomainService::class);
                    echo $crossLoadedDomainService->processGenderDisplay($report->DEM_GENDER, $report->DEM_GENDER_8_TEXT ?? null);
                @endphp
            </td>
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
            'Attendance' => $report->ATT_DOMAIN,
            'Behavior' => $report->B_DOMAIN,
            'Physical Health' => $report->P_DOMAIN,
            'Social & Emotional Well-Being' => $report->S_DOMAIN,
            'Supports Outside of School' => $report->O_DOMAIN,
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

            // Normalize spaces to handle data inconsistencies (single vs double spaces)
            $normalizedRating = preg_replace('/\s+/', ' ', trim(strtolower($cleanRating)));
            
            switch ($normalizedRating) {
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
                case 'an area of substantial concern':
                    $substantialConcern[] = $domainLabel;
                    break;
            }
        }

        $crossLoadedDomainService = app(\App\Services\CrossLoadedDomainService::class);

        $notOfConcern = array_diff(
            array_keys($domainValues),
            array_map(fn($v) => str_replace('*', '', $v), array_merge($someConcern, $substantialConcern))
        );
        $notOfConcernText = $crossLoadedDomainService->formatDomainsToText($notOfConcern);

        $ofConcern = array_unique(array_map(fn($v) => str_replace('*', '', $v), array_merge($someConcern, $substantialConcern)));
        $ofConcernText = $crossLoadedDomainService->formatDomainsToText($ofConcern);
        
        // Check if there are no concerns at Gate 1 (special case)
        $hasNoConcernsAtGate1 = empty($ofConcern);
    @endphp


    <table>
        <thead>
            <tr>
                <th style="background-color: #C8E6C9; width: 20%;">Area of Substantial Strength</th>
                <th style="background-color: #DCEDC8; width: 20%;">Area of Some Strength</th>
                <th style="background-color: #BBDEFB; width: 20%;">Area of Neither Strength Nor Concern</th>
                <th style="background-color: #F8BBD0; width: 20%;">Area of Some Concern</th>
                <th style="background-color: #EF9A9A; width: 20%;">Area of Substantial Concern</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background-color: #C8E6C9; width: 20%;">
                    @foreach ($substantialStrength as $domain)
                        <div>{{ $domain }}</div> <br>
                    @endforeach
                </td>
                <td style="background-color: #DCEDC8; width: 20%;">
                    @foreach ($someStrength as $domain)
                        <div>{{ $domain }}</div> <br>
                    @endforeach
                </td>
                <td style="background-color: #BBDEFB; width: 20%;">
                    @foreach ($neutral as $domain)
                        <div>{{ $domain }}</div> <br>
                    @endforeach
                </td>
                <td style="background-color: #F8BBD0; width: 20%;">
                    @foreach ($someConcern as $domain)
                        <div>{{ $domain }}</div> <br>
                    @endforeach
                </td>
                <td style="background-color: #EF9A9A; width: 20%;">
                    @foreach ($substantialConcern as $domain)
                        <div>{{ $domain }}</div> <br>
                    @endforeach
                </td>
            </tr>
        </tbody>
    </table>

    <p><strong>In addition, please consider the following information regarding endorsed items:</strong></p>
    <table>
        <tbody>
            <td style="width: 50%;">
                <img src="{{ public_path('assets/icons/PROCEED.PNG.png') }}" alt="Proceed Icon" style="width:60px; vertical-align: middle;"/>                <p><strong>Proceed:</strong></p>
                <ul>
                    @if (
                        str_contains(strtolower($report->RELATION_CLOSE), 'positive') &&
                        (str_contains($report->RELATION_CONFLICT, 'No conflict') ||
                        str_contains($report->RELATION_CONFLICT, 'Low conflict'))
                    )
                        <li>Student-rater relationship</li>
                    @endif


                </ul>
            </td>
            <td style="width: 50%;">
                <img src="{{ public_path('assets/icons/CAUTION.png') }}" alt="Caution Icon" style="width:60px; vertical-align: middle;"/>
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

    @if (!$hasNoConcernsAtGate1)
    <!-- Page #2 - Only show when there are concerns at Gate 1 ---------------------->
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
    // Cross-loaded item groups are now managed by the CrossLoadedDomainService
    // This ensures consistency and eliminates duplication
@endphp


    <p><strong>ESSY Gate 2 Summary of Specific Concerns</strong></p>
    <p>Before reviewing Gate 2 ratings, please consider results on the following essential items:</p>

    <table>
        <tbody>
            <td style="width: 50%;">
                <img src="{{ public_path('assets/icons/PROCEED.PNG.png') }}" alt="Proceed Icon" style="width:60px; vertical-align: middle;"/>
                <p><strong>Proceed:</strong></p>
                <ul>
                    @foreach ($proceedItems as $item)
                        <li>{!! $item !!}</li>
                    @endforeach
                </ul>
            </td>
            <td style="width: 50%;">
                <img src="{{ public_path('assets/icons/CAUTION.png') }}" alt="Caution Icon" style="width:60px; vertical-align: middle;"/>
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

    {{-- Table View --}}
    @php
    // Process all domains using the appropriate service based on configuration
    $concernDomains = array_map(fn($domain) => trim(explode('*', $domain)[0]), array_merge($someConcern, $substantialConcern));
    
    // Sort concern domains alphabetically for proper display order
    sort($concernDomains);
    
    // Always use DecisionRulesService for domain processing
    $domainService = app(\App\Services\DecisionRulesService::class);
    $erroredItems = [];
    $checkboxErrorItems = [];

    try {
        $academicResults = $domainService->processDomainItems($report, 'Academic Skills', $concernDomains);
        $erroredItems = array_merge($erroredItems, $academicResults['errored']);
        $checkboxErrorItems = array_merge($checkboxErrorItems, $academicResults['checkboxError']);
    } catch (Exception $e) {
        $academicResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
    }

    try {
        $behaviorResults = $domainService->processDomainItems($report, 'Behavior', $concernDomains);
        $erroredItems = array_merge($erroredItems, $behaviorResults['errored']);
        $checkboxErrorItems = array_merge($checkboxErrorItems, $behaviorResults['checkboxError']);
    } catch (Exception $e) {
        $behaviorResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
    }

    try {
        $physicalResults = $domainService->processDomainItems($report, 'Physical Health', $concernDomains);
        $erroredItems = array_merge($erroredItems, $physicalResults['errored']);
        $checkboxErrorItems = array_merge($checkboxErrorItems, $physicalResults['checkboxError']);
    } catch (Exception $e) {
        $physicalResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
    }

    try {
        $sewbResults = $domainService->processDomainItems($report, 'Social & Emotional Well-Being', $concernDomains);
        $erroredItems = array_merge($erroredItems, $sewbResults['errored']);
        $checkboxErrorItems = array_merge($checkboxErrorItems, $sewbResults['checkboxError']);
    } catch (Exception $e) {
        $sewbResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
    }

    try {
        $sosResults = $domainService->processDomainItems($report, 'Supports Outside of School', $concernDomains);
        $erroredItems = array_merge($erroredItems, $sosResults['errored']);
        $checkboxErrorItems = array_merge($checkboxErrorItems, $sosResults['checkboxError']);
    } catch (Exception $e) {
        $sosResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
    }

    // Process Gate 2 Essential Items 
    //These arent really used only the errors generated from them are used
    try {
        $essentialResults = $domainService->processDomainItems($report, 'Gate 2 Essential Items', $concernDomains);
        $erroredItems = array_merge($erroredItems, $essentialResults['errored']);
        $checkboxErrorItems = array_merge($checkboxErrorItems, $essentialResults['checkboxError']);
    } catch (Exception $e) {
        $essentialResults = ['strengths' => [], 'monitor' => [], 'concerns' => []];
    }
    // Helper function to chunk array items into groups of max 10
    function chunkDomainItems($items, $maxPerChunk = 10) {
        return array_chunk($items, $maxPerChunk);
    }
    
    // Helper function to get the maximum number of chunks needed across all categories
    function getMaxChunks($strengths, $monitor, $concerns, $maxPerChunk = 10) {
        $strengthsChunks = ceil(count($strengths) / $maxPerChunk);
        $monitorChunks = ceil(count($monitor) / $maxPerChunk);
        $concernsChunks = ceil(count($concerns) / $maxPerChunk);
        return max($strengthsChunks, $monitorChunks, $concernsChunks, 1); // At least 1 row
    }
    
    // Helper function to get items for a specific chunk
    function getChunkItems($items, $chunkIndex, $maxPerChunk = 10) {
        $start = $chunkIndex * $maxPerChunk;
        return array_slice($items, $start, $maxPerChunk);
    }
    @endphp

    <table style="page-break-inside: avoid;">
        <thead style="display: table-row-group;">
            <tr>
                <th style="width: 15%;">Domain</th>
                <th style="background-color: #C8E6C9; padding-top: 12px; padding-bottom: 12px; width: 28.33%;">
                    <i style="font-size:22px; margin-right: 5px;" class="bi bi-hand-thumbs-up-fill"></i>
                    Strengths to Maintain</th>
                <th style="background-color: #BBDEFB; padding-top: 12px; padding-bottom: 12px; width: 28.33%;">
                    <i style="font-size:22px; margin-right: 5px;" class="bi bi-search"></i>
                    Areas to Monitor</th>
                <th style="background-color: #EF9A9A; padding-top: 12px; padding-bottom: 12px; width: 28.33%;">
                    <i style="font-size:22px; margin-right: 5px;" class="bi bi-exclamation-triangle-fill"></i>
                    Concerns for Follow Up</th>
            </tr>
        </thead>
        <tbody>
            @if(in_array('Academic Skills', $concernDomains))
                @php
                    $maxChunks = getMaxChunks($academicResults['strengths'], $academicResults['monitor'], $academicResults['concerns']);
                @endphp
                @for ($chunkIndex = 0; $chunkIndex < $maxChunks; $chunkIndex++)
                    <tr>
                        <td style="text-align: center; vertical-align: middle; width: 15%;">
                            @if ($chunkIndex === 0)
                                <img src="{{ public_path('assets/icons/ACADEMICS.png') }}" alt="Academic Skills" style="width:80px; display: block; margin: 0 auto;"/>
                                <div>Academic Skills</div>
                            @endif
                        </td>
                        <td style="background-color: #C8E6C9; width: 28.33%;">
                            @foreach (getChunkItems($academicResults['strengths'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #BBDEFB; width: 28.33%;">
                            @foreach (getChunkItems($academicResults['monitor'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #EF9A9A; width: 28.33%;">
                            @foreach (getChunkItems($academicResults['concerns'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                    </tr>
                @endfor
            @endif

            @if(in_array('Behavior', $concernDomains))
                @php
                    $maxChunks = getMaxChunks($behaviorResults['strengths'], $behaviorResults['monitor'], $behaviorResults['concerns']);
                @endphp
                @for ($chunkIndex = 0; $chunkIndex < $maxChunks; $chunkIndex++)
                    <tr>
                        <td style="text-align: center; vertical-align: middle; width: 15%;">
                            @if ($chunkIndex === 0)
                                <img src="{{ public_path('assets/icons/BEHAVIOR.png') }}" alt="Behavior" style="width:80px; display: block; margin: 0 auto;"/>
                                <div>Behavior</div>
                            @endif
                        </td>
                        <td style="background-color: #C8E6C9; width: 28.33%;">
                            @foreach (getChunkItems($behaviorResults['strengths'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #BBDEFB; width: 28.33%;">
                            @foreach (getChunkItems($behaviorResults['monitor'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #EF9A9A; width: 28.33%;">
                            @foreach (getChunkItems($behaviorResults['concerns'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                    </tr>
                @endfor
            @endif

            @if(in_array('Physical Health', $concernDomains))
                @php
                    $maxChunks = getMaxChunks($physicalResults['strengths'], $physicalResults['monitor'], $physicalResults['concerns']);
                @endphp
                @for ($chunkIndex = 0; $chunkIndex < $maxChunks; $chunkIndex++)
                    <tr>
                        <td style="text-align: center; vertical-align: middle; width: 15%;">
                            @if ($chunkIndex === 0)
                                <img src="{{ public_path('assets/icons/PHYSICAL HEALTH.png') }}" alt="Physical Health" style="width:80px; display: block; margin: 0 auto;"/>
                                <div>Physical Health</div>
                            @endif
                        </td>
                        <td style="background-color: #C8E6C9; width: 28.33%;">
                            @foreach (getChunkItems($physicalResults['strengths'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #BBDEFB; width: 28.33%;">
                            @foreach (getChunkItems($physicalResults['monitor'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #EF9A9A; width: 28.33%;">
                            @foreach (getChunkItems($physicalResults['concerns'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                    </tr>
                @endfor
            @endif

            @if(in_array('Social & Emotional Well-Being', $concernDomains))
                @php
                    $maxChunks = getMaxChunks($sewbResults['strengths'], $sewbResults['monitor'], $sewbResults['concerns']);
                @endphp
                @for ($chunkIndex = 0; $chunkIndex < $maxChunks; $chunkIndex++)
                    <tr>
                        <td style="text-align: center; vertical-align: middle; width: 15%;">
                            @if ($chunkIndex === 0)
                                <img src="{{ public_path('assets/icons/SOCIAL&EMOTIONAL WELL-BEING.png') }}" alt="Social & Emotional Well-Being" style="width:80px; display: block; margin: 0 auto;"/>
                                <div>Social & Emotional Well-Being</div>
                            @endif
                        </td>
                        <td style="background-color: #C8E6C9; width: 28.33%;">
                            @foreach (getChunkItems($sewbResults['strengths'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #BBDEFB; width: 28.33%;">
                            @foreach (getChunkItems($sewbResults['monitor'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #EF9A9A; width: 28.33%;">
                            @foreach (getChunkItems($sewbResults['concerns'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                    </tr>
                @endfor
            @endif

            @if(in_array('Supports Outside of School', $concernDomains))
                @php
                    $maxChunks = getMaxChunks($sosResults['strengths'], $sosResults['monitor'], $sosResults['concerns']);
                @endphp
                @for ($chunkIndex = 0; $chunkIndex < $maxChunks; $chunkIndex++)
                    <tr>
                        <td style="text-align: center; vertical-align: middle; width: 15%;">
                            @if ($chunkIndex === 0)
                                <img src="{{ public_path('assets/icons/SUPPORTS OUTSIDE OF SCHOOL.png') }}" alt="Supports Outside of School" style="width:80px; display: block; margin: 0 auto;"/>
                                <div>Supports Outside of School</div>
                            @endif
                        </td>
                        <td style="background-color: #C8E6C9; width: 28.33%;">
                            @foreach (getChunkItems($sosResults['strengths'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #BBDEFB; width: 28.33%;">
                            @foreach (getChunkItems($sosResults['monitor'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                        <td style="background-color: #EF9A9A; width: 28.33%;">
                            @foreach (getChunkItems($sosResults['concerns'], $chunkIndex) as $item)
                                <p>{!! $item !!}</p>
                            @endforeach
                        </td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>

    <p>* Rater reported less confidence in these responses.</p>
    <p>† Item appears on multiple domains.</p>
    
    <div style="page-break-after: always;"></div>
    
    <h4>Additional Information</h4>
    <table>
        <tr>
            <td>
                # of unanswered items:
                @php
                    $missingItems = [];
                    $crossLoadedDomainService = app(\App\Services\CrossLoadedDomainService::class);
                    $crossLoadedGroups = $crossLoadedDomainService->getCrossLoadedItemGroups();
                    $fieldMessages = $crossLoadedDomainService->getFieldMessages();
                    $fieldToDomainMap = $crossLoadedDomainService->getFieldToDomainMap();
                    
                    // Track which cross-loaded groups have been processed to avoid duplicates
                    $processedCrossLoadedGroups = [];
                    
                    foreach ($fieldMessages as $field => $message) {
                        // Skip comment fields
                        if (str_starts_with($field, 'COMMENTS_')) {
                            continue;
                        }

                        if (!in_array($field, ['E_SHARM', 'E_BULLIED', 'E_EXCLUDE', 'E_WITHDRAW', 'E_REGULATE', 'E_RESTED'])) {
                            // Only count fields from concern domains
                            $fieldDomain = $fieldToDomainMap[$field] ?? null;
                            if (!$fieldDomain || !in_array($fieldDomain, $concernDomains)) {
                                continue;
                            }
                        }
                        
                        // Check if this field is part of a cross-loaded group
                        $crossLoadedGroupIndex = null;
                        foreach ($crossLoadedGroups as $groupIndex => $group) {
                            if (in_array($field, $group)) {
                                $crossLoadedGroupIndex = $groupIndex;
                                break;
                            }
                        }
                        
                        // Check if this specific field is unanswered (-99)
                        $rawValue = trim($report->getAttribute($field) ?? '');
                        if ($rawValue === '-99') {
                            // This field is specifically unanswered, count it as missing
                            $missingItems[] = $message . " ($field)";
                        }
                        // Note: We no longer use complex cross-loaded group logic for unanswered items
                        // Each -99 field in a concern domain counts as missing, regardless of cross-loading
                    }
                @endphp
                    
                    

                @php
                    // Remove duplicates from missing items
                    $uniqueMissingItems = array_unique($missingItems);
                @endphp
                {{ count($uniqueMissingItems) }}
                @if (!empty($uniqueMissingItems))
                    <ul>
                        @foreach ($uniqueMissingItems as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                @endif
                
                
                @if (!empty($erroredItems))
                @php
                    $c = collect($erroredItems);
                    $c = $c->unique();
                    $erroredItems = $c->toArray();
                @endphp
                <p>Failed to parse from Qualtrics ({{ count($erroredItems) }}):</p>
                    <ul>
                        
                        @foreach ($erroredItems as $error)
                            @php
                                // If $error is an array, convert to string for display
                                $errorString = is_array($error) ? implode(', ', $error) : $error;
                                if(empty($errorString)) {
                                    continue;
                                }
                            @endphp
                            <li style="color: red;">{!! $errorString !!}</li>
                        @endforeach
                    </ul>
                @endif

                @if (!empty($checkboxErrorItems ))
                @php
                    $c = collect($checkboxErrorItems);
                    $c = $c->unique();
                    $checkboxErrorItems = $c->toArray();
                @endphp
                <p>Checked Box for Not Confident in rating ({{ count($checkboxErrorItems) }}):</p>
                    <ul>
                        
                        @foreach ($checkboxErrorItems as $error)
                            <li style="color: black;">{!! $error !!}</li>
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

@else
    {{-- Special case: No concerns at Gate 1 --}}
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
    
    <p><strong>ESSY Gate 2 Summary of Specific Concerns</strong></p>
    <p>There were no concerns reported at Gate 1, and so the rater did not receive Essential items or Gate 2 Items to rate.</p>
    
    <br/>
    <h4>Additional Information</h4>
    <table>
        <tr>
            <td>
                <strong># of unanswered items:</strong> 0
                <p><em>No Gate 2 items were presented due to no concerns at Gate 1.</em></p>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Rater comments:</strong>
                <ul>
                    @php
                        $comments = [
                            'COMMENTS_GATE1' => 'Gate 1',
                            'COMMENTS_STR'   => 'Student-rater relationship',
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
@endif




</body>
</html>