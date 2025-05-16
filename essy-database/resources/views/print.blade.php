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

    foreach ($academicIndicators as $field => $message) {
        $value = $report->$field ?? null;
        if (!$value) continue;

        $normalized = strtolower(trim($value));
        $frequency = ucfirst($normalized);
        $needsFlag = str_contains($value, ',');

        $flaggedSentence = "$frequency $message" . ($needsFlag ? '*' : '');

        if ($field === 'A_B_IMPULSE_CL1') {
            if (in_array($normalized, ['almost always', 'frequently'])) {
                $academic_concerns[] = $flaggedSentence;
            } elseif ($normalized === 'sometimes') {
                $academic_monitor[] = $flaggedSentence;
            } elseif (in_array($normalized, ['occasionally', 'almost never'])) {
                $academic_skills_strengths[] = $flaggedSentence;
            }
        } else {
            if (in_array($normalized, ['almost always', 'frequently'])) {
                $academic_skills_strengths[] = $flaggedSentence;
            } elseif ($normalized === 'sometimes') {
                $academic_monitor[] = $flaggedSentence;
            } elseif (in_array($normalized, ['occasionally', 'almost never'])) {
                $academic_concerns[] = $flaggedSentence;
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
        $value = strtolower(trim($report->$field ?? ''));
        $raw = $report->$field ?? '';
        $needsFlag = str_contains($raw, ',');
        $sentence = ucfirst($value) . ' ' . $description . ($needsFlag ? '*' : '');

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
                if (in_array($value, ['almost never'])) {
                    $behavior_strengths[] = $sentence;
                } else {
                    $behavior_concerns[] = $sentence;
                }
                break;
        }
    }
@endphp




<table>
    <thead>
        <tr>
            <th>Domain</th>
            <th>Strengths to Maintain</th>
            <th>Areas to Monitor</th>
            <th>Concerns for Follow Up</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Academic Skills</td>
            <td>
                @foreach ($academic_skills_strengths as $item)
                    <p>{{ $item }}</p>
                    <br/>
                @endforeach
            </td>
            <td>
                @foreach ($academic_monitor as $item)
                    <p>{{ $item }}</p>
                    <br/>
                @endforeach
            </td>
            <td>
                @foreach ($academic_concerns as $item)
                    <p>{{ $item }}</p>
                    <br/>
                @endforeach
            </td>
        </tr>
        <tr>
            <td>Behavior</td>
            <td>
                @foreach ($behavior_strengths as $item)
                    <p>{{ $item }}</p>
                    <br/>
                @endforeach
            </td>
            <td>
                @foreach ($behavior_monitor as $item)
                    <p>{{ $item }}</p>
                    <br/>
                @endforeach
            </td>
            <td>
                @foreach ($behavior_concerns as $item)
                    <p>{{ $item }}</p>
                    <br/>
                @endforeach
            </td>
        </tr>
    </tbody>
</table>    

</body>
</html>
