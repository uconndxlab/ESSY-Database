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
            $cleanRating = explode(',', $rating)[0]; // Get just the rating portion
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
        $notOfConcern = array_diff(
            array_keys($domainValues),
            array_map(fn($v) => str_replace('*', '', $v), array_merge($someConcern, $substantialConcern))
        );

        $notOfConcern = array_values($notOfConcern); // reset keys to ensure order

        $notOfConcernText = '';
        $count = count($notOfConcern);
        if ($count === 1) {
            $notOfConcernText = $notOfConcern[0];
        } elseif ($count === 2) {
            $notOfConcernText = $notOfConcern[0] . ' and ' . $notOfConcern[1];
        } elseif ($count > 2) {
            $last = array_pop($notOfConcern);
            $notOfConcernText = implode(', ', $notOfConcern) . ', and ' . $last;
        }

        $ofConcern = array_merge($someConcern, $substantialConcern);
        $ofConcern = array_map(fn($v) => str_replace('*', '', $v), $ofConcern); // remove asterisks for this summary
        $ofConcern = array_unique($ofConcern); // prevent duplicates
        $ofConcern = array_values($ofConcern); // reindex

        $ofConcernText = '';
        $countConcern = count($ofConcern);
        if ($countConcern === 1) {
            $ofConcernText = $ofConcern[0];
        } elseif ($countConcern === 2) {
            $ofConcernText = $ofConcern[0] . ' and ' . $ofConcern[1];
        } elseif ($countConcern > 2) {
            $last = array_pop($ofConcern);
            $ofConcernText = implode(', ', $ofConcern) . ', and ' . $last;
        }


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
        $line = ucfirst($item['value']) . ' ' . $item['text'] . '.';

        if (in_array($item['value'], $item['proceed'])) {
            $proceedItems[] = $line;
        } else {
            $cautionItems[] = $line;
        }
    }
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

{{-- cross filtering information --}}
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
        ['O_P_HYGEINE_CL1', 'O_P_HYGIENE_CL2'],       // CO (Phys) / DW (SOS) - Hygiene resources (Note: O_P_HYGEINE_CL1 from model)
        ['O_P_CLOTHES_CL1', 'O_P_CLOTHES_CL2'],       // CP (Phys) / DX (SOS) - Adequate clothing
        ['A_S_O_ACTIVITY3_CL1', 'A_S_O_ACTIVITY_CL2', 'A_S_O_ACTIVITY_CL3'] // BJ (Acad) / DJ (SEWB) / EC (SOS) - Extracurricular activity
    ];


@endphp
    

@php
    // Define domain indicators using actual database column IDs from ReportData.php model
    $academicIndicators = [
        'A_READ' => 'meets grade-level expectations for reading skills.',
        'A_WRITE' => 'meets expectations for grade-level writing skills.',
        'A_MATH' => 'meets expectations for grade-level math skills.',
        'A_P_ARTICULATE_CL1' => 'articulates clearly enough to be understood.',
        'A_S_ADULTCOMM_CL1' => 'effectively communicates with adults.',
        'A_DIRECTIONS' => 'understands directions.',
        'A_INITIATE' => 'initiates academic tasks.',
        'A_PLANORG' => 'demonstrates ability to plan, organize, focus, and prioritize tasks.',
        'A_TURNIN' => 'completes and turns in assigned work.',
        'A_B_CLASSEXPECT_CL1' => 'follows classroom expectations.',
        'A_B_IMPULSE_CL1' => 'exhibits impulsivity.',
        'A_ENGAGE' => 'engaged in academic activities.',
        'A_INTEREST' => 'shows interest in learning activities.',
        'A_PERSIST' => 'persists with challenging tasks.',
        'A_GROWTH' => 'demonstrates a growth mindset.',
        'A_S_CONFIDENT_CL1' => 'displays confidence in self.',
        'A_S_POSOUT_CL1' => 'demonstrates positive outlook.',
        'A_S_O_ACTIVITY3_CL1' => 'is engaged in at least one extracurricular activity.'
    ];

    $behaviorIndicators = [
        'A_B_CLASSEXPECT_CL2' => 'follows classroom expectations.',
        'A_B_IMPULSE_CL2' => 'exhibits impulsivity.',
        'B_CLINGY' => 'exhibits overly clingy or attention-seeking behaviors.',
        'B_SNEAK' => 'demonstrates sneaky or dishonest behavior.',
        'BEH_VERBAGGRESS' => 'engages in verbally aggressive behavior toward others.',
        'BEH_PHYSAGGRESS' => 'engages in physically aggressive behavior toward others.',
        'B_DESTRUCT' => 'engages in destructive behavior towards property.',
        'B_BULLY' => 'bullies/has bullied another student.',
        'B_PUNITIVE' => 'experiences/has experienced punitive or exclusionary discipline at school.',
        'B_O_HOUSING_CL1' => 'reports not having a stable living situation.',
        'B_O_FAMSTRESS_CL1' => 'family is experiencing significant stressors.',
        'B_O_NBHDSTRESS_CL1' => 'neighborhood is experiencing significant stressors.'
    ];

    $physicalIndicators = [
        'P_SIGHT' => 'able to see, from a distance or up close.',
        'P_HEAR' => 'able to hear information.',
        'A_P_ARTICULATE_CL2' => 'articulates clearly enough to be understood.',
        'A_ORAL' => 'oral health appears to be addressed.',
        'A_PHYS' => 'physical health appears to be addressed.',
        'P_PARTICIPATE' => 'physical health allows for participation in school activities.',
        'S_P_ACHES_CL1' => 'complains of headaches, stomachaches, or body aches.',
        'O_P_HUNGER_CL1' => 'reports being hungry.',
        'O_P_HYGEINE_CL1' => 'appears to have the resources to address basic hygiene needs.', // Note: O_P_HYGEINE_CL1 from model
        'O_P_CLOTHES_CL1' => 'shows up to school with adequate clothing.'
    ];

    $sewbIndicators = [
        'S_CONTENT' => 'appears content.',
        'A_S_CONFIDENT_CL2' => 'displays confidence in self.',
        'A_S_POSOUT_CL2' => 'demonstrates positive outlook.',
        'S_P_ACHES_CL2' => 'complains of headaches, stomachaches, or body aches.',
        'S_NERVOUS' => 'appears nervous, worried, tense, or fearful.',
        'S_SAD' => 'appears sad.',
        'S_SOCIALCONN' => 'has friends/social connections.',
        'S_FRIEND' => 'has at least one close friend at school.',
        'S_PROSOCIAL' => 'demonstrates prosocial skills.',
        'S_PEERCOMM' => 'effectively communicates with peers.',
        'A_S_ADULTCOMM_CL2' => 'effectively communicates with adults.',
        'S_POSADULT' => 'has a positive relationship with at least one adult in the school.',
        'S_SCHOOLCONN' => 'appears to experience a sense of connection in their school.',
        'S_COMMCONN' => 'appears to experience a sense of connection in their community.',
        'A_S_O_ACTIVITY_CL2' => 'is engaged in at least one extracurricular activity.'
    ];

    $sosIndicators = [
        'O_RECIPROCAL' => 'family-school communication is reciprocal.',
        'O_POSADULT' => 'has a positive adult outside of school with whom they feel close.',
        'O_ADULTBEST' => 'reports having an adult outside of school who wants them to do their best.',
        'O_TALK' => 'reports having someone outside of school to talk to about their interests and problems.',
        'O_ROUTINE' => 'shares having a caregiver who helps them with daily routines.',
        'O_FAMILY' => 'reports getting along with family members.',
        'O_P_HUNGER_CL2' => 'reports being hungry.',
        'O_P_HYGIENE_CL2' => 'appears to have the resources to address basic hygiene needs.',
        'O_P_CLOTHES_CL2' => 'shows up to school with adequate clothing.',
        'O_RESOURCE' => 'reports having access to resources (materials, internet) to complete schoolwork.',
        'B_O_HOUSING_CL2' => 'reports not having a stable living situation.',
        'B_O_FAMSTRESS_CL2' => 'family is experiencing significant stressors.',
        'B_O_NBHDSTRESS_CL2' => 'neighborhood is experiencing significant stressors.',
        'A_S_O_ACTIVITY_CL3' => 'is engaged in at least one extracurricular activity.'
    ];

    // Map fields to their domains for cross-loading checks
    $fieldToDomainMap = [];
    $allDomainIndicators = [
        'Academic Skills' => $academicIndicators,
        'Behavior' => $behaviorIndicators,
        'Physical Health' => $physicalIndicators,
        'Social & Emotional Well-Being' => $sewbIndicators,
        'Supports Outside of School' => $sosIndicators
    ];
    
    foreach ($allDomainIndicators as $domain => $indicators) {
        foreach (array_keys($indicators) as $field) {
            // $field is now the database column name
            $fieldToDomainMap[$field] = $domain;
        }
    }

    // Calculate which fields need a dagger (†) symbol
    $fieldsThatNeedDagger = [];
    

    $concernDomains = array_map(fn($domain) => trim(explode('*', $domain)[0]), array_merge($someConcern ?? [], $substantialConcern ?? []));

    // Identify fields that need the cross symbol
    foreach ($crossLoadItemGroups as $group) { // $group contains database field names
        $domainsInGroupThatAreConcerns = [];
        
        // Check which domains in this group are concerns
        foreach ($group as $field) { // $field is a database field name
            if (isset($fieldToDomainMap[$field]) && in_array($fieldToDomainMap[$field], $concernDomains)) {
                $domainsInGroupThatAreConcerns[$fieldToDomainMap[$field]] = true;
            }
        }
        
        // If more than one domain in this group is a concern, mark all fields in that group (that belong to a concern domain) for a dagger
        if (count($domainsInGroupThatAreConcerns) > 1) {
            foreach ($group as $field) { // $field is a database field name
                if (isset($fieldToDomainMap[$field]) && in_array($fieldToDomainMap[$field], $concernDomains)) {
                    $fieldsThatNeedDagger[$field] = true;
                }
            }
        }
    }
    
    $academic_skills_strengths = []; $academic_monitor = []; $academic_concerns = [];
    $behavior_strengths = []; $behavior_monitor = []; $behavior_concerns = [];
    $ph_strengths = []; $ph_monitor = []; $ph_concerns = [];
    $sewb_strengths = []; $sewb_monitor = []; $sewb_concerns = [];
    $sos_strengths = []; $sos_monitor = []; $sos_concerns = [];
@endphp

{{-- Academic section--}}
@php
    // Process Academic Skills items
    foreach ($academicIndicators as $field => $message) { // $field is now the database column name
        $valueRaw = $report->$field ?? ''; // Accessing $report using database column name
        if (!$valueRaw || trim($valueRaw) === '-99') continue;
        
        $hasConfidence = str_contains($valueRaw, ','); // Assuming confidence is still marked by a comma
        $value = strtolower(trim(explode(',', $valueRaw)[0]));
        $prefix = ucfirst($value);
        
        $itemSuffix = $hasConfidence ? ' *' : '';
        if (isset($fieldsThatNeedDagger[$field])) { // $field is database column name
            $itemSuffix .= ' †';
        }
        
        $sentence = "{$prefix} {$message}{$itemSuffix}";
        
        // Special handling for "exhibits impulsivity" (A_B_IMPULSE_CL1) - reversed interpretation
        if ($field === 'A_B_IMPULSE_CL1') { // Use database column name for the check
            if (in_array($value, ['almost always', 'frequently'])) {
                $academic_concerns[] = $sentence;
            } elseif ($value === 'sometimes') {
                $academic_monitor[] = $sentence;
            } else {
                $academic_skills_strengths[] = $sentence;
            }
        } else {
            // Normal items (higher frequency is generally better for most academic skills)
            if (in_array($value, ['almost always', 'frequently'])) {
                $academic_skills_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $academic_monitor[] = $sentence;
            } else {
                $academic_concerns[] = $sentence;
            }
        }
    }
@endphp

{{-- Behavior section--}}
@php
    // Process Behavior items
    foreach ($behaviorIndicators as $field => $message) { // $field is now the database column name
        $valueRaw = $report->$field ?? '';
        if (!$valueRaw || trim($valueRaw) === '-99') continue;
        
        $hasConfidence = str_contains($valueRaw, ',');
        $value = strtolower(trim(explode(',', $valueRaw)[0]));
        $prefix = ucfirst($value);
        
        $itemSuffix = $hasConfidence ? ' *' : '';
        if (isset($fieldsThatNeedDagger[$field])) {
            $itemSuffix .= ' †';
        }
        
        $sentence = "{$prefix} {$message}{$itemSuffix}";
        
        // Different types of behavior items need different interpretation logic
        // Ensure case statements use the correct database field names
        switch ($field) {
            // Positive items - higher frequency is better
            case 'A_B_CLASSEXPECT_CL2': // follows classroom expectations (was BP)
                if (in_array($value, ['almost always', 'frequently'])) {
                    $behavior_strengths[] = $sentence;
                } elseif ($value === 'sometimes') {
                    $behavior_monitor[] = $sentence;
                } else {
                    $behavior_concerns[] = $sentence;
                }
                break;
                
            // Negative items with moderate monitor category
            case 'A_B_IMPULSE_CL2': // exhibits impulsivity (was BQ)
            case 'B_CLINGY': // clingy behaviors (was BR)
            case 'B_O_FAMSTRESS_CL1': // family stressors (was BZ)
            case 'B_O_NBHDSTRESS_CL1': // neighborhood stressors (was CA)
                if (in_array($value, ['almost never', 'occasionally'])) {
                    $behavior_strengths[] = $sentence;
                } elseif ($value === 'sometimes') {
                    $behavior_monitor[] = $sentence;
                } else {
                    $behavior_concerns[] = $sentence;
                }
                break;
                
            // Negative items with different monitoring threshold
            case 'B_SNEAK': // sneaky behavior (was BS)
            case 'BEH_VERBAGGRESS': // verbal aggression (was BT)
            case 'B_DESTRUCT': // destructive behavior (was BV)
            case 'B_O_HOUSING_CL1': // unstable living (was BY)
                if ($value === 'almost never') {
                    $behavior_strengths[] = $sentence;
                } elseif (in_array($value, ['sometimes', 'occasionally'])) {
                    $behavior_monitor[] = $sentence;
                } else {
                    $behavior_concerns[] = $sentence;
                }
                break;
                
            // Zero-tolerance negative items
            case 'BEH_PHYSAGGRESS': // physical aggression (was BU)
            case 'B_BULLY': // bullying (was BW)
            case 'B_PUNITIVE': // punitive discipline (was BX)
                if ($value === 'almost never') {
                    $behavior_strengths[] = $sentence;
                } else {
                    $behavior_concerns[] = $sentence;
                }
                break;
        }
    }
@endphp

{{-- Physical Health section--}}
@php
    // Process Physical Health items
    foreach ($physicalIndicators as $field => $message) { // $field is now the database column name
        $valueRaw = $report->$field ?? '';
        if (!$valueRaw || trim($valueRaw) === '-99') continue;
        
        $hasConfidence = str_contains($valueRaw, ',');
        $value = strtolower(trim(explode(',', $valueRaw)[0]));
        $prefix = ucfirst($value);
        
        $itemSuffix = $hasConfidence ? ' *' : '';
        if (isset($fieldsThatNeedDagger[$field])) {
            $itemSuffix .= ' †';
        }
        
        $sentence = "{$prefix} {$message}{$itemSuffix}";
        
        // Ensure case statements use the correct database field names
        switch ($field) {
            // Items where higher frequency indicates strength
            case 'P_SIGHT': // able to see (was CG)
            case 'P_HEAR': // able to hear (was CH)
            case 'A_P_ARTICULATE_CL2': // articulates clearly (was CI)
            case 'A_ORAL': // oral health addressed (was CJ)
            case 'A_PHYS': // physical health addressed (was CK)
            case 'P_PARTICIPATE': // participates in activities (was CL)
            case 'O_P_HYGEINE_CL1': // hygiene resources (was CO) - Note: O_P_HYGEINE_CL1 from model
            case 'O_P_CLOTHES_CL1': // adequate clothing (was CP)
                if (in_array($value, ['almost always', 'frequently'])) {
                    $ph_strengths[] = $sentence;
                } elseif ($value === 'sometimes') {
                    $ph_monitor[] = $sentence;
                } else {
                    $ph_concerns[] = $sentence;
                }
                break;
                
            // Items where lower frequency indicates strength
            case 'S_P_ACHES_CL1': // complains of aches (was CM)
            case 'O_P_HUNGER_CL1': // reports being hungry (was CN)
                if (in_array($value, ['almost never', 'occasionally'])) {
                    $ph_strengths[] = $sentence;
                } elseif ($value === 'sometimes') {
                    $ph_monitor[] = $sentence;
                } else {
                    $ph_concerns[] = $sentence;
                }
                break;
        }
    }
@endphp



{{-- Social & Emotional Well-Being  section--}}

@php
    // Process Social & Emotional Well-Being items
    foreach ($sewbIndicators as $field => $message) { // $field is now the database column name
        $valueRaw = $report->$field ?? '';
        if (!$valueRaw || trim($valueRaw) === '-99') continue;
        
        $hasConfidence = str_contains($valueRaw, ',');
        $value = strtolower(trim(explode(',', $valueRaw)[0]));
        $prefix = ucfirst($value);
        
        $itemSuffix = $hasConfidence ? ' *' : '';
        if (isset($fieldsThatNeedDagger[$field])) {
            $itemSuffix .= ' †';
        }
        
        $sentence = "{$prefix} {$message}{$itemSuffix}";
        
        // Items where lower frequency indicates strength (negative items)
        // Ensure field names in the array are database column names
        if (in_array($field, ['S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD'])) { // aches (was CY), nervous (was CZ), sad (was DA)
            if (in_array($value, ['almost never', 'occasionally'])) {
                $sewb_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $sewb_monitor[] = $sentence;
            } else {
                $sewb_concerns[] = $sentence;
            }
        } 
        // All other items - higher frequency is better (positive items)
        else {
            if (in_array($value, ['almost always', 'frequently'])) {
                $sewb_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $sewb_monitor[] = $sentence;
            } else {
                $sewb_concerns[] = $sentence;
            }
        }
    }
@endphp



{{-- Support Outside of School section--}}

@php
    // Process Supports Outside of School items
    foreach ($sosIndicators as $field => $message) { // $field is now the database column name
        $valueRaw = $report->$field ?? '';
        if (!$valueRaw || trim($valueRaw) === '-99') continue;
        
        $hasConfidence = str_contains($valueRaw, ',');
        $value = strtolower(trim(explode(',', $valueRaw)[0]));
        $prefix = ucfirst($value);
        
        $itemSuffix = $hasConfidence ? ' *' : '';
        if (isset($fieldsThatNeedDagger[$field])) {
            $itemSuffix .= ' †';
        }
        
        $sentence = "{$prefix} {$message}{$itemSuffix}";
        
        // Ensure case statements use the correct database field names
        switch ($field) {
            // Standard positive items - higher frequency is better
            case 'O_RECIPROCAL': // reciprocal communication (was DP)
            case 'O_POSADULT': // positive adult (was DQ)
            case 'O_ADULTBEST': // adult who wants best (was DR)
            case 'O_TALK': // someone to talk to (was DS)
            case 'O_ROUTINE': // caregiver helps (was DT)
            case 'O_FAMILY': // gets along with family (was DU)
            case 'O_P_HYGIENE_CL2': // hygiene resources (was DW)
            case 'O_P_CLOTHES_CL2': // adequate clothing (was DX)
            case 'O_RESOURCE': // resources for schoolwork (was DY)
            case 'A_S_O_ACTIVITY_CL3': // extracurricular activity (was EC)
                if (in_array($value, ['almost always', 'frequently'])) {
                    $sos_strengths[] = $sentence;
                } elseif ($value === 'sometimes') {
                    $sos_monitor[] = $sentence;
                } else {
                    $sos_concerns[] = $sentence;
                }
                break;
                
            // Negative items with regular monitoring threshold
            case 'B_O_FAMSTRESS_CL2': // family stressors (was EA)
            case 'B_O_NBHDSTRESS_CL2': // neighborhood stressors (was EB)
                if (in_array($value, ['almost never', 'occasionally'])) {
                    $sos_strengths[] = $sentence;
                } elseif ($value === 'sometimes') {
                    $sos_monitor[] = $sentence;
                } else {
                    $sos_concerns[] = $sentence;
                }
                break;
                
            // Negative items with different monitoring threshold
            case 'O_P_HUNGER_CL2': // reports being hungry (was DV)
            case 'B_O_HOUSING_CL2': // unstable living (was DZ)
                if ($value === 'almost never') {
                    $sos_strengths[] = $sentence;
                } elseif (in_array($value, ['sometimes', 'occasionally'])) {
                    $sos_monitor[] = $sentence;
                } else {
                    $sos_concerns[] = $sentence;
                }
                break;
        }
    }
@endphp

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
                @foreach ($academic_skills_strengths as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($academic_monitor as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($academic_concerns as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Behavior', $concernDomains))
        <tr>
            <td>Behavior</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($behavior_strengths as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($behavior_monitor as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($behavior_concerns as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Physical Health', $concernDomains))
        <tr>
            <td>Physical Health</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($ph_strengths as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($ph_monitor as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($ph_concerns as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Social & Emotional Well-Being', $concernDomains))
        <tr>
            <td>Social & Emotional Well-Being</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($sewb_strengths as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($sewb_monitor as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($sewb_concerns as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
        </tr>
        @endif

        @if(in_array('Supports Outside of School', $concernDomains))
        <tr>
            <td>Supports Outside of School</td>
            <td style="background-color: #C8E6C9;">
                @foreach ($sos_strengths as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #BBDEFB;">
                @foreach ($sos_monitor as $item)
                    <p>{!! $item !!}</p>
                    <br/>
                @endforeach
            </td>
            <td style="background-color: #EF9A9A;">
                @foreach ($sos_concerns as $item)
                    <p>{!! $item !!}</p>
                    <br/>
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
                // Use actual database column names as keys
                // For cross-loaded items, the message refers to its primary domain context if it's a primary field
                $indicatorMessages = [
                    // Academic Skills - Primary Instances
                    'A_READ' => 'meets grade-level expectations for reading skills.',
                    'A_WRITE' => 'meets expectations for grade-level writing skills.',
                    'A_MATH' => 'meets expectations for grade-level math skills.',
                    'A_P_ARTICULATE_CL1' => 'articulates clearly enough to be understood (Academic Skills primary).',
                    'A_S_ADULTCOMM_CL1' => 'effectively communicates with adults (Academic Skills primary).',
                    'A_DIRECTIONS' => 'understands directions.',
                    'A_INITIATE' => 'initiates academic tasks.',
                    'A_PLANORG' => 'demonstrates ability to plan, organize, focus, and prioritize tasks.',
                    'A_TURNIN' => 'completes and turns in assigned work.',
                    'A_B_CLASSEXPECT_CL1' => 'follows classroom expectations (Academic Skills primary).',
                    'A_B_IMPULSE_CL1' => 'exhibits impulsivity (Academic Skills primary).',
                    'A_ENGAGE' => 'engaged in academic activities.',
                    'A_INTEREST' => 'shows interest in learning activities.',
                    'A_PERSIST' => 'persists with challenging tasks.',
                    'A_GROWTH' => 'demonstrates a growth mindset.',
                    'A_S_CONFIDENT_CL1' => 'displays confidence in self (Academic Skills primary).',
                    'A_S_POSOUT_CL1' => 'demonstrates positive outlook (Academic Skills primary).',
                    'A_S_O_ACTIVITY3_CL1' => 'is engaged in at least one extracurricular activity (Academic Skills primary).',

                    // Behavior - Primary Instances (excluding those already listed as CL2 under other domains if they are secondary there)
                    // 'A_B_CLASSEXPECT_CL2' is secondary, so primary is A_B_CLASSEXPECT_CL1 (Academic)
                    // 'A_B_IMPULSE_CL2' is secondary, so primary is A_B_IMPULSE_CL1 (Academic)
                    'B_CLINGY' => 'exhibits overly clingy or attention-seeking behaviors.',
                    'B_SNEAK' => 'demonstrates sneaky or dishonest behavior.',
                    'BEH_VERBAGGRESS' => 'engages in verbally aggressive behavior toward others.',
                    'BEH_PHYSAGGRESS' => 'engages in physically aggressive behavior toward others.',
                    'B_DESTRUCT' => 'engages in destructive behavior towards property.',
                    'B_BULLY' => 'bullies/has bullied another student.',
                    'B_PUNITIVE' => 'experiences/has experienced punitive or exclusionary discipline at school.',
                    'B_O_HOUSING_CL1' => 'reports not having a stable living situation (Behavior primary).',
                    'B_O_FAMSTRESS_CL1' => 'family is experiencing significant stressors (Behavior primary).',
                    'B_O_NBHDSTRESS_CL1' => 'neighborhood is experiencing significant stressors (Behavior primary).',

                    // Physical Health - Primary Instances
                    'P_SIGHT' => 'able to see, from a distance or up close.',
                    'P_HEAR' => 'able to hear information.',
                    // 'A_P_ARTICULATE_CL2' is secondary, primary is A_P_ARTICULATE_CL1 (Academic)
                    'A_ORAL' => 'oral health appears to be addressed.',
                    'A_PHYS' => 'physical health appears to be addressed.',
                    'P_PARTICIPATE' => 'physical health allows for participation in school activities.',
                    'S_P_ACHES_CL1' => 'complains of headaches, stomachaches, or body aches (Physical Health primary).',
                    'O_P_HUNGER_CL1' => 'reports being hungry (Physical Health primary).',
                    'O_P_HYGEINE_CL1' => 'appears to have the resources to address basic hygiene needs (Physical Health primary).', // Note: O_P_HYGEINE_CL1 from model
                    'O_P_CLOTHES_CL1' => 'shows up to school with adequate clothing (Physical Health primary).',

                    // Social & Emotional Well-Being - Primary Instances
                    'S_CONTENT' => 'appears content.',
                    // 'A_S_CONFIDENT_CL2' is secondary
                    // 'A_S_POSOUT_CL2' is secondary
                    // 'S_P_ACHES_CL2' is secondary
                    'S_NERVOUS' => 'appears nervous, worried, tense, or fearful.',
                    'S_SAD' => 'appears sad.',
                    'S_SOCIALCONN' => 'has friends/social connections.',
                    'S_FRIEND' => 'has at least one close friend at school.',
                    'S_PROSOCIAL' => 'demonstrates prosocial skills.',
                    'S_PEERCOMM' => 'effectively communicates with peers.',
                    // 'A_S_ADULTCOMM_CL2' is secondary
                    'S_POSADULT' => 'has a positive relationship with at least one adult in the school.',
                    'S_SCHOOLCONN' => 'appears to experience a sense of connection in their school.',
                    'S_COMMCONN' => 'appears to experience a sense of connection in their community.',
                    // 'A_S_O_ACTIVITY_CL2' is secondary

                    // Supports Outside of School - Primary Instances
                    'O_RECIPROCAL' => 'family-school communication is reciprocal.',
                    'O_POSADULT' => 'has a positive adult outside of school with whom they feel close.',
                    'O_ADULTBEST' => 'reports having an adult outside of school who wants them to do their best.',
                    'O_TALK' => 'reports having someone outside of school to talk to about their interests and problems.',
                    'O_ROUTINE' => 'shares having a caregiver who helps them with daily routines.',
                    'O_FAMILY' => 'reports getting along with family members.',
                    // 'O_P_HUNGER_CL2' is secondary
                    // 'O_P_HYGIENE_CL2' is secondary
                    // 'O_P_CLOTHES_CL2' is secondary
                    'O_RESOURCE' => 'reports having access to resources (materials, internet) to complete schoolwork.',
                    // 'B_O_HOUSING_CL2' is secondary
                    // 'B_O_FAMSTRESS_CL2' is secondary
                    // 'B_O_NBHDSTRESS_CL2' is secondary
                    // 'A_S_O_ACTIVITY_CL3' is tertiary
                ];

                // These are the database field names of the *secondary* or *tertiary* instances of cross-loaded items.
                // Missing items from this list will NOT be counted towards unanswered items, as their primary instance should be checked.
                $secondaryOrTertiaryCrossloadFields = [
                    'A_P_ARTICULATE_CL2',   // CI (Phys) - secondary to AV (Acad)
                    'A_S_ADULTCOMM_CL2',    // DF (SEWB) - secondary to AW (Acad)
                    'A_B_CLASSEXPECT_CL2',  // BP (Beh) - secondary to BB (Acad)
                    'A_B_IMPULSE_CL2',      // BQ (Beh) - secondary to BC (Acad)
                    'A_S_CONFIDENT_CL2',    // CW (SEWB) - secondary to BH (Acad)
                    'A_S_POSOUT_CL2',       // CX (SEWB) - secondary to BI (Acad)
                    'S_P_ACHES_CL2',        // CY (SEWB) - secondary to CM (Phys)
                    'B_O_HOUSING_CL2',      // DZ (SOS) - secondary to BY (Beh)
                    'B_O_FAMSTRESS_CL2',    // EA (SOS) - secondary to BZ (Beh)
                    'B_O_NBHDSTRESS_CL2',   // EB (SOS) - secondary to CA (Beh)
                    'O_P_HUNGER_CL2',       // DV (SOS) - secondary to CN (Phys)
                    'O_P_HYGIENE_CL2',      // DW (SOS) - secondary to O_P_HYGEINE_CL1 (Phys)
                    'O_P_CLOTHES_CL2',      // DX (SOS) - secondary to O_P_CLOTHES_CL1 (Phys)
                    'A_S_O_ACTIVITY_CL2',   // DJ (SEWB) - secondary to BJ (Acad)
                    'A_S_O_ACTIVITY_CL3'    // EC (SOS) - tertiary to BJ (Acad)
                ];

                foreach ($indicatorMessages as $field => $message) {
                    // $field is now the actual database column name (primary instance of an item)
                    $value = $report->$field ?? null; 
                    
                    $isComment = str_starts_with($field, 'COMMENTS_'); // This check remains valid
                    
                    // We only count missing for primary items. 
                    // We ensure $field is not in the secondary/tertiary list just to be safe
                    $isSecondaryOrTertiary = in_array($field, $secondaryOrTertiaryCrossloadFields);

                    if (!$isSecondaryOrTertiary && !$isComment && (string)$value === '-99') {
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
                    @if (!empty($report->$field))
                        <li><strong>{{ $label }}:</strong> {{ $report->$field }}</li>
                    @endif
                @endforeach
            </ul>
        </td>
    </tr>
</table>




</body>
</html>