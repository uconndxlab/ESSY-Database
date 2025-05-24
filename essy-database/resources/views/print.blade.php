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

    $crossLoadedMap = [
        'A_P_ARTICULATE_CL1' => 'A_P_ARTICULATE_CL2',
        'A_S_ADULTCOMM_CL1' => 'A_S_ADULTCOMM_CL2',
        'A_B_CLASSEXPECT_CL1' => 'A_B_CLASSEXPECT_CL2',
        'A_B_IMPULSE_CL1' => 'A_B_IMPULSE_CL2',
        'A_S_CONFIDENT_CL1' => 'A_S_CONFIDENT_CL2',
        'A_S_POSOUT_CL1' => 'A_S_POSOUT_CL2',
        'B_O_HOUSING_CL1' => 'B_O_HOUSING_CL2',
        'B_O_FAMSTRESS_CL1' => 'B_O_FAMSTRESS_CL2',
        'B_O_NBHDSTRESS_CL1' => 'B_O_NBHDSTRESS_CL2',
        'S_P_ACHES_CL1' => 'S_P_ACHES_CL2',
        'O_P_HUNGER_CL1' => 'O_P_HUNGER_CL2',
        'O_P_CLOTHES_CL1' => 'O_P_CLOTHES_CL2'
    ];


@endphp
    

{{-- filtering for academic skills! --}}
{{-- filtering for academic skills! --}}
@php
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
        'A_ENGAGE' => 'engaged in academic activities.',
        'A_INTEREST' => 'shows interest in learning activities.',
        'A_PERSIST' => 'persists with challenging tasks.',
        'A_GROWTH' => 'demonstrates a growth mindset.',
        'A_S_CONFIDENT_CL1' => 'displays confidence in self.',
        'A_S_POSOUT_CL1' => 'demonstrates positive outlook.',
        'A_S_O_ACTIVITY3_CL1' => 'is engaged in at least one extracurricular activity.',
        'A_B_IMPULSE_CL1' => 'exhibits impulsivity.'
    ];

    $academic_skills_strengths = [];
    $academic_monitor = [];
    $academic_concerns = [];

    $allReportKeys = array_keys((array) $report);

    foreach ($academicIndicators as $field => $message) {
        $valueRaw = $report->$field ?? '';
        if (!$valueRaw || trim($valueRaw) === '-99') continue;

        $hasConfidence = str_contains($valueRaw, ',');

        $value = strtolower(trim(explode(',', $valueRaw)[0]));
        $prefix = ucfirst($value);
        $suffix = $hasConfidence ? ' *' : '';

        $sentence = "{$prefix} {$message}{$suffix}";

        if ($field === 'A_B_IMPULSE_CL1') {
            if (in_array($value, ['almost always', 'frequently'])) {
                $academic_concerns[] = $sentence;
            } elseif ($value === 'sometimes') {
                $academic_monitor[] = $sentence;
            } elseif (in_array($value, ['occasionally', 'almost never'])) {
                $academic_skills_strengths[] = $sentence;
            }
        } else {
            if (in_array($value, ['almost always', 'frequently'])) {
                $academic_skills_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $academic_monitor[] = $sentence;
            } elseif (in_array($value, ['occasionally', 'almost never'])) {
                $academic_concerns[] = $sentence;
            }
        }
    }
@endphp




{{-- filtering for behaviors --}}
@php
$behaviorIndicators = [
    'A_B_CLASSEXPECT_CL2' => 'follows classroom expectations.',
    'A_B_IMPULSE_CL2' => 'exhibits impulsivity.',
    'B_CLINGY' => 'exhibits clingy or attention-seeking behaviors.',
    'B_SNEAK' => 'demonstrates sneaky or dishonest behavior.',
    'BEH_VERBAGGRESS' => 'engages in verbally aggressive behavior.',
    'BEH_PHYSAGGRESS' => 'engages in physically aggressive behavior.',
    'B_DESTRUCT' => 'engages in destructive behavior toward property.',
    'B_BULLY' => 'bullies/has bullied another student.',
    'B_PUNITIVE' => 'experiences punitive or exclusionary discipline.',
    'B_O_HOUSING_CL1' => 'reports not having a stable living situation.',
    'B_O_FAMSTRESS_CL1' => 'family is experiencing significant stressors.',
    'B_O_NBHDSTRESS_CL1' => 'neighborhood is experiencing significant stressors.'
];

$behavior_strengths = [];
$behavior_monitor = [];
$behavior_concerns = [];

foreach ($behaviorIndicators as $field => $description) {
    $raw = $report->$field ?? '';
    if (!$raw || trim($raw) === '-99') continue;

    $hasConfidence = str_contains($raw, ',');
    $value = strtolower(trim(explode(',', $raw)[0]));
    $prefix = ucfirst($value);
    $suffix = ($hasConfidence ? ' *' : '');
          

    $sentence = "{$prefix} {$description}{$suffix}";

    switch ($field) {
        case 'A_B_CLASSEXPECT_CL2':
            if (in_array($value, ['almost always', 'frequently'])) {
                $behavior_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $behavior_monitor[] = $sentence;
            } elseif (in_array($value, ['occasionally', 'almost never'])) {
                $behavior_concerns[] = $sentence;
            }
            break;

        case 'A_B_IMPULSE_CL2':
        case 'B_CLINGY':
        case 'B_O_FAMSTRESS_CL1':
        case 'B_O_NBHDSTRESS_CL1':
            if (in_array($value, ['occasionally', 'almost never'])) {
                $behavior_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $behavior_monitor[] = $sentence;
            } elseif (in_array($value, ['almost always', 'frequently'])) {
                $behavior_concerns[] = $sentence;
            }
            break;

        case 'B_SNEAK':
        case 'BEH_VERBAGGRESS':
        case 'B_DESTRUCT':
        case 'B_O_HOUSING_CL1':
            if ($value === 'almost never') {
                $behavior_strengths[] = $sentence;
            } elseif (in_array($value, ['sometimes', 'occasionally'])) {
                $behavior_monitor[] = $sentence;
            } elseif (in_array($value, ['almost always', 'frequently'])) {
                $behavior_concerns[] = $sentence;
            }
            break;

        case 'BEH_PHYSAGGRESS':
        case 'B_BULLY':
        case 'B_PUNITIVE':
            if ($value === 'almost never') {
                $behavior_strengths[] = $sentence;
            } else {
                $behavior_concerns[] = $sentence;
            }
            break;
    }
}
@endphp



{{-- filtering for physical health --}}
@php
$physicalIndicators = [
    'P_SIGHT' => 'has vision concerns.',
    'P_HEAR' => 'has hearing concerns.',
    'A_P_ARTICULATE_CL2' => 'articulates clearly enough to be understood.',
    'A_ORAL' => 'has clear oral communication.',
    'A_PHYS' => 'demonstrates physical coordination.',
    'P_PARTICIPATE' => 'participates in physical activities.',
    'S_P_ACHES_CL1' => 'reports physical discomfort or frequent aches.',
    'O_P_HUNGER_CL1' => 'often comes to school hungry.',
    'O_P_HYGEINE_CL1' => 'displays appropriate hygiene.',
    'O_P_CLOTHES_CL1' => 'has appropriate clothing for school.'
];

$ph_strengths = [];
$ph_monitor = [];
$ph_concerns = [];

foreach ($physicalIndicators as $field => $description) {
    $raw = $report->$field ?? '';
    if (!$raw || trim($raw) === '-99') continue;

    $hasConfidence = str_contains($raw, ',');
    $value = strtolower(trim(explode(',', $raw)[0]));
    $prefix = ucfirst($value);
    $suffix = ($hasConfidence ? ' *' : '');

    $sentence = "{$prefix} {$description}{$suffix}";

    switch ($field) {
        case 'P_SIGHT':
        case 'P_HEAR':
            if ($value === 'almost always') {
                $ph_strengths[] = $sentence;
            } elseif ($value === 'frequently') {
                $ph_monitor[] = $sentence;
            } else {
                $ph_concerns[] = $sentence;
            }
            break;

        case 'A_P_ARTICULATE_CL2':
        case 'A_ORAL':
        case 'A_PHYS':
        case 'P_PARTICIPATE':
        case 'O_P_HYGEINE_CL1':
        case 'O_P_CLOTHES_CL1':
            if (in_array($value, ['almost always', 'frequently'])) {
                $ph_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $ph_monitor[] = $sentence;
            } else {
                $ph_concerns[] = $sentence;
            }
            break;

        case 'S_P_ACHES_CL1':
            if (in_array($value, ['occasionally', 'almost never'])) {
                $ph_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $ph_monitor[] = $sentence;
            } else {
                $ph_concerns[] = $sentence;
            }
            break;

        case 'O_P_HUNGER_CL1':
            if ($value === 'almost never') {
                $ph_strengths[] = $sentence;
            } elseif (in_array($value, ['sometimes', 'occasionally'])) {
                $ph_monitor[] = $sentence;
            } else {
                $ph_concerns[] = $sentence;
            }
            break;
    }
}
@endphp



@php
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

$sewb_strengths = [];
$sewb_monitor = [];
$sewb_concerns = [];

foreach ($sewbIndicators as $field => $description) {
    $raw = $report->$field ?? '';
    if (!$raw || trim($raw) === '-99') continue;

    $hasConfidence = str_contains($raw, ',');
    $value = strtolower(trim(explode(',', $raw)[0]));
    $prefix = ucfirst($value);
    $suffix =($hasConfidence ? ' *' : '');

    $sentence = "{$prefix} {$description}{$suffix}";

    if (in_array($field, ['S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD'])) {
        if (in_array($value, ['almost never', 'occasionally'])) {
            $sewb_strengths[] = $sentence;
        } elseif ($value === 'sometimes') {
            $sewb_monitor[] = $sentence;
        } elseif (in_array($value, ['frequently', 'almost always'])) {
            $sewb_concerns[] = $sentence;
        }
    } else {
        if (in_array($value, ['almost always', 'frequently'])) {
            $sewb_strengths[] = $sentence;
        } elseif ($value === 'sometimes') {
            $sewb_monitor[] = $sentence;
        } elseif (in_array($value, ['occasionally', 'almost never'])) {
            $sewb_concerns[] = $sentence;
        }
    }
}
@endphp



{{-- filtering for Supports Outside of School --}}
{{-- filtering for Supports Outside of School --}}
@php
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

$sos_strengths = [];
$sos_monitor = [];
$sos_concerns = [];

foreach ($sosIndicators as $field => $message) {
    $raw = $report->$field ?? '';
    if (!$raw || trim($raw) === '-99') continue;

    $hasConfidence = str_contains($raw, ',');

    $value = strtolower(trim(explode(',', $raw)[0]));

    $prefix = ucfirst($value);
    $suffix = ($hasConfidence ? ' *' : '');

    $sentence = "{$prefix} {$message}{$suffix}";

    switch ($field) {
        case 'O_P_HYGIENE_CL2':
        case 'O_P_CLOTHES_CL2':
        case 'O_RESOURCE':
        case 'O_RECIPROCAL':
        case 'O_POSADULT':
        case 'O_ADULTBEST':
        case 'O_TALK':
        case 'O_ROUTINE':
        case 'O_FAMILY':
        case 'A_S_O_ACTIVITY_CL3':
            if (in_array($value, ['almost always', 'frequently'])) {
                $sos_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $sos_monitor[] = $sentence;
            } else {
                $sos_concerns[] = $sentence;
            }
            break;

        case 'B_O_FAMSTRESS_CL2':
        case 'B_O_NBHDSTRESS_CL2':
            if (in_array($value, ['almost never', 'occasionally'])) {
                $sos_strengths[] = $sentence;
            } elseif ($value === 'sometimes') {
                $sos_monitor[] = $sentence;
            } else {
                $sos_concerns[] = $sentence;
            }
            break;

        case 'O_P_HUNGER_CL2':
        case 'B_O_HOUSING_CL2':
            if ($value === 'almost never') {
                $sos_strengths[] = $sentence;
            } elseif (in_array($value, ['sometimes', 'occasionally'])) {
                $sos_monitor[] = $sentence;
            } else {
                $sos_concerns[] = $sentence;
            }
            break;

        default:
            if ($value === 'sometimes') {
                $sos_monitor[] = $sentence;
            } else {
                $sos_concerns[] = $sentence;
            }
            break;
    }
}
@endphp


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
                $indicatorMessages = [
                    'A_READ' => 'Meets grade-level expectations for reading skills.',
                    'A_WRITE' => 'Meets expectations for grade-level writing skills.',
                    'A_MATH' => 'Meets expectations for grade-level math skills.',
                    'A_P_ARTICULATE_CL1' => 'Articulates clearly enough to be understood.',
                    'A_S_ADULTCOMM_CL1' => 'Effectively communicates with adults.',
                    'A_DIRECTIONS' => 'Understands directions.',
                    'A_INITIATE' => 'Initiates academic tasks.',
                    'A_PLANORG' => 'Demonstrates ability to plan, organize, focus, and prioritize tasks.',
                    'A_TURNIN' => 'Completes and turns in assigned work.',
                    'A_B_CLASSEXPECT_CL1' => 'Follows classroom expectations.',
                    'A_B_IMPULSE_CL1' => 'Exhibits impulsivity.',
                    'A_ENGAGE' => 'Engaged in academic activities.',
                    'A_INTEREST' => 'Shows interest in learning activities.',
                    'A_PERSIST' => 'Persists with challenging tasks.',
                    'A_GROWTH' => 'Demonstrates a growth mindset.',
                    'A_S_CONFIDENT_CL1' => 'Displays confidence in self.',
                    'A_S_POSOUT_CL1' => 'Demonstrates positive outlook.',
                    'A_S_O_ACTIVITY3_CL1' => 'Is engaged in at least one extracurricular activity.',
                    'A_B_CLASSEXPECT_CL2' => 'Follows classroom expectations.',
                    'A_B_IMPULSE_CL2' => 'Exhibits impulsivity.',
                    'B_CLINGY' => 'Exhibits overly clingy or attention-seeking behaviors.',
                    'B_SNEAK' => 'Demonstrates sneaky or dishonest behavior.',
                    'BEH_VERBAGGRESS' => 'Engages in verbally aggressive behavior toward others.',
                    'BEH_PHYSAGGRESS' => 'Engages in physically aggressive behavior toward others.',
                    'B_DESTRUCT' => 'Engages in destructive behavior towards property.',
                    'B_BULLY' => 'Bullies/has bullied another student.',
                    'B_PUNITIVE' => 'Experiences/has experienced punitive or exclusionary discipline at school.',
                    'B_O_HOUSING_CL1' => 'Reports not having a stable living situation.',
                    'B_O_FAMSTRESS_CL1' => 'Family is experiencing significant stressors.',
                    'B_O_NBHDSTRESS_CL1' => 'Neighborhood is experiencing significant stressors.',
                    'P_SIGHT' => 'Able to see, from a distance or up close.',
                    'P_HEAR' => 'Able to hear information.',
                    'A_P_ARTICULATE_CL2' => 'Articulates clearly enough to be understood.',
                    'A_ORAL' => 'Oral health appears to be addressed.',
                    'A_PHYS' => 'Physical health appears to be addressed.',
                    'P_PARTICIPATE' => 'Physical health allows for participation in school activities.',
                    'S_P_ACHES_CL1' => 'Complains of headaches, stomachaches, or body aches.',
                    'O_P_HUNGER_CL1' => 'Reports being hungry.',
                    'O_P_HYGEINE_CL1' => 'Appears to have the resources to address basic hygiene needs.',
                    'O_P_CLOTHES_CL1' => 'Shows up to school with adequate clothing.',
                    'S_CONTENT' => 'Appears content.',
                    'A_S_CONFIDENT_CL2' => 'Displays confidence in self.',
                    'A_S_POSOUT_CL2' => 'Demonstrates positive outlook.',
                    'S_P_ACHES_CL2' => 'Complains of headaches, stomachaches, or body aches.',
                    'S_NERVOUS' => 'Appears nervous, worried, tense, or fearful.',
                    'S_SAD' => 'Appears sad.',
                    'S_SOCIALCONN' => 'Has friends/social connections.',
                    'S_FRIEND' => 'Has at least one close friend at school.',
                    'S_PROSOCIAL' => 'Demonstrates prosocial skills.',
                    'S_PEERCOMM' => 'Effectively communicates with peers.',
                    'A_S_ADULTCOMM_CL2' => 'Effectively communicates with adults.',
                    'S_POSADULT' => 'Has a positive relationship with at least one adult in the school.',
                    'S_SCHOOLCONN' => 'Appears to experience a sense of connection in their school.',
                    'S_COMMCONN' => 'Appears to experience a sense of connection in their community.',
                    'A_S_O_ACTIVITY_CL2' => 'Is engaged in at least one extracurricular activity.',
                    'O_RECIPROCAL' => 'Family-school communication is reciprocal.',
                    'O_POSADULT' => 'Has a positive adult outside of school with whom they feel close.',
                    'O_ADULTBEST' => 'Reports having an adult outside of school who wants them to do their best.',
                    'O_TALK' => 'Reports having someone outside of school to talk to about their interests and problems.',
                    'O_ROUTINE' => 'Shares having a caregiver who helps them with daily routines.',
                    'O_FAMILY' => 'Reports getting along with family members.',
                    'O_P_HUNGER_CL2' => 'Reports being hungry.',
                    'O_P_HYGIENE_CL2' => 'Appears to have the resources to address basic hygiene needs.',
                    'O_P_CLOTHES_CL2' => 'Shows up to school with adequate clothing.',
                    'O_RESOURCE' => 'Reports having access to resources (materials, internet) to complete schoolwork.',
                    'B_O_HOUSING_CL2' => 'Reports not having a stable living situation.',
                    'B_O_FAMSTRESS_CL2' => 'Family is experiencing significant stressors.',
                    'B_O_NBHDSTRESS_CL2' => 'Neighborhood is experiencing significant stressors.',
                    'A_S_O_ACTIVITY_CL3' => 'Is engaged in at least one extracurricular activity.',
                ];

                $excludedSuffixes = ['CL2'];
                $excludedPrefixes = ['COMMENTS_'];

                foreach ($indicatorMessages as $field => $message) {
                    $value = $report->$field ?? null;

                    $isCL2 = str_ends_with($field, 'CL2');
                    $isComment = str_starts_with($field, 'COMMENTS_');

                    if ($isCL2 || $isComment) {
                        continue;
                    }

                    if ((string) $value === '-99') {
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
