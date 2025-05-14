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
            <td class="label-cell"><strong>Student Name:</strong> {{ $report->INITIALS }}</td>
            <td class="label-cell"><strong>School:</strong> {{ $report->ExternalReference ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Race / Ethnicity:</strong> {{ $report->DEM_RACE }} {{ $report->DEM_ETHNIC == 'No' ? '' : '/ Hispanic' }}</td>
            <td class="label-cell"><strong>Grade:</strong> {{ $report->DEM_GRADE }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Gender:</strong> {{ $report->DEM_GENDER }}</td>
            <td class="label-cell"><strong>Classroom Teacher:</strong> {{ $report->RecipientLastName ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>IEP/504:</strong> {{ ($report->DEM_IEP === 'Yes' || $report->DEM_504 === 'Yes') ? 'Yes' : 'No' }}</td>
            <td></td>
        </tr>
        <tr>
            <td class="label-cell"><strong>English Language Learner:</strong> {{ $report->DEM_ELL }}</td>
            <td class="label-cell"><strong>Chronic Illness:</strong> {{ $report->DEM_CI }}</td>
        </tr>
        <tr>
            <td class="label-cell"><strong>Date of Assessment:</strong> {{ \Carbon\Carbon::parse($report->RecordedDate)->format('m/d/y') }}</td>
            <td class="label-cell"><strong>Rater:</strong> {{ $report->RecipientFirstName ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- Gate 1 Summary -->
    <h3>ESSY Gate 1 Summary of Broad Concerns</h3>

    @php
        $domainValues = [
            'Academic Skills' => $report->AS_DOMAIN,
            'Behavior' => $report->BEH_DOMAIN,
            'Social & Emotional Well-Being' => $report->SEW_DOMAIN,
            'Physical Health' => $report->PH2_DOMAIN,
            'Supports Outside of School' => $report->SOS2_DOMAIN,
            'Attendance' => $report->ATT_C_DOMAIN,
        ];

        $substantialStrength = [];
        $someStrength = [];
        $neutral = [];
        $someConcern = [];
        $substantialConcern = [];

        foreach ($domainValues as $domain => $rating) {
            switch (trim(strtolower($rating))) {
                case 'an area of substantial strength':
                    $substantialStrength[] = $domain;
                    break;
                case 'an area of some strength':
                    $someStrength[] = $domain;
                    break;
                case 'neither an area of concern or strength':
                    $neutral[] = $domain;
                    break;
                case 'an area of some concern':
                    $someConcern[] = $domain;
                    break;
                case 'an area of substantial concern':
                    $substantialConcern[] = $domain;
                    break;
            }
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
                    <li></li>

                </ul>
            </td>
            <td>
                <p><strong>Caution:</strong></p>
                <ul>
                    <li></li>

                </ul>
            </td>

        </tbody>
    </table>


</body>
</html>
