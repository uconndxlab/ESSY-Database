<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ESSY Gate 1 Screening Results</title>
    <style>
        /* Force colors to print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }

        h1 {
            text-align: center;
            font-size: 24pt;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .header-info {
            text-align: center;
            color: #4b5563;
            margin-bottom: 20px;
            font-size: 11pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
        }

        .legend-table {
            width: auto;
            margin: 0 auto 20px auto;
        }

        .legend-table th {
            background-color: #f3f4f6;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            padding: 12px;
        }

        .legend-table td {
            padding: 16px 24px;
            text-align: center;
        }

        .legend-table td span {
            font-weight: 900;
            font-size: 12pt;
        }

        .data-table {
            margin-top: 20px;
        }

        .data-table thead {
            background-color: #f3f4f6;
        }

        .data-table th {
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
            padding: 10px 6px;
        }

        .data-table td {
            font-size: 9pt;
            padding: 6px;
        }

        /* Domain color columns - fixed width */
        .domain-column {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }

        /* Domain color column backgrounds */
        .bg-blue-50 {
            background-color: #eff6ff;
        }

        .bg-purple-50 {
            background-color: #faf5ff;
        }

        .bg-orange-50 {
            background-color: #fff7ed;
        }

        .bg-pink-50 {
            background-color: #fdf2f8;
        }

        .bg-teal-50 {
            background-color: #f0fdfa;
        }

        .bg-indigo-50 {
            background-color: #eef2ff;
        }

        /* Summary row */
        .summary-row {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .summary-row td {
            text-align: center;
            font-size: 10pt;
        }
    </style>
</head>
<body>

@php
    function getDomainBgStyle($rating) {
        if (!$rating) return '';
        $rating = str_replace('  ', ' ', $rating);
        if(str_contains($rating, 'substantial concern')) return 'background-color: #ff989b;';
        if(str_contains($rating, 'some concern')) return 'background-color: #ffcdc3;';
        if(str_contains($rating, 'concern or strength')) return 'background-color: #bed6ef;';
        if(str_contains($rating, 'some strength')) return 'background-color: #c6e1b4;';
        if(str_contains($rating, 'substantial strength')) return 'background-color: #a8d08c;';
        return 'background-color: #e5e7eb;';
    }

    function hasNeed($rating) {
        if (!$rating) return false;
        $rating = str_replace('  ', ' ', $rating);
        return str_contains($rating, 'substantial concern') || str_contains($rating, 'some concern');
    }

    // Initialize counters for each domain (6 domains total)
    $domainNeedCounts = [0, 0, 0, 0, 0, 0]; // Academic, Attendance, Behavior, Physical, Social, Outside
    $totalStudents = $reports->count();
@endphp

    <table>
        <tr style="text-align: center;">
            <td>School: {{ $reports->first()->SCHOOL }}</td>
            <td>Grade: {{ $reports->first()->DEM_GRADE }}</td>
            <td>Total Students Screened: {{ $reports->count() }}</td>
        </tr>
    </table>    
    <h1>ESSY Gate 1 Summary of Broad Concerns</h1>

    <!-- Legend/Key -->
    <table class="legend-table">
        <thead>
            <tr>
                <th colspan="5">Legend:</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background-color: #ff989b;">
                    <span>Area of Substantial Concern</span>
                </td>
                <td style="background-color: #ffcdc3;">
                    <span>Area of Some Concern</span>
                </td>
                <td style="background-color: #bed6ef;">
                    <span>No Concern or Strength</span>
                </td>
                <td style="background-color: #c6e1b4;">
                    <span>Area of Some Strength</span>
                </td>
                <td style="background-color: #a8d08c;">
                    <span>Area of Substantial Strength</span>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Main Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th >Teacher Name</th>
                <th >Student First Name</th>
                <th >Student Last Name</th>
                <th class="domain-column">Academic Skills</th>
                <th class="domain-column">Attendance</th>
                <th class="domain-column">Behavior</th>
                <th class="domain-column">Physical Health</th>
                <th class="domain-column">Social & Emotional Well-Being</th>
                <th class="domain-column">Supports Outside of School</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $report)
            @php
                // Track needs for each domain
                if (hasNeed($report->A_DOMAIN)) $domainNeedCounts[0]++;
                if (hasNeed($report->ATT_DOMAIN)) $domainNeedCounts[1]++;
                if (hasNeed($report->B_DOMAIN)) $domainNeedCounts[2]++;
                if (hasNeed($report->P_DOMAIN)) $domainNeedCounts[3]++;
                if (hasNeed($report->S_DOMAIN)) $domainNeedCounts[4]++;
                if (hasNeed($report->O_DOMAIN)) $domainNeedCounts[5]++;
            @endphp
            <tr>
                <td >{{ $report->FN_TEACHER }} {{ $report->LN_TEACHER }}</td>
                <td >{{ $report->FN_STUDENT }}</td>
                <td >{{ $report->LN_STUDENT }}</td>
                <td class="domain-column" style="{{ getDomainBgStyle($report->A_DOMAIN) }}"></td>
                <td class="domain-column" style="{{ getDomainBgStyle($report->ATT_DOMAIN) }}"></td>
                <td class="domain-column" style="{{ getDomainBgStyle($report->B_DOMAIN) }}"></td>
                <td class="domain-column" style="{{ getDomainBgStyle($report->P_DOMAIN) }}"></td>
                <td class="domain-column" style="{{ getDomainBgStyle($report->S_DOMAIN) }}"></td>
                <td class="domain-column" style="{{ getDomainBgStyle($report->O_DOMAIN) }}"></td>
            </tr>
            @endforeach
            <tr class="summary-row">
                <td colspan="3">% of students exhibiting needs</td>
                <td class="domain-column">{{ $totalStudents > 0 ? number_format(($domainNeedCounts[0] / $totalStudents) * 100, 1) : 0 }}%</td>
                <td class="domain-column">{{ $totalStudents > 0 ? number_format(($domainNeedCounts[1] / $totalStudents) * 100, 1) : 0 }}%</td>
                <td class="domain-column">{{ $totalStudents > 0 ? number_format(($domainNeedCounts[2] / $totalStudents) * 100, 1) : 0 }}%</td>
                <td class="domain-column">{{ $totalStudents > 0 ? number_format(($domainNeedCounts[3] / $totalStudents) * 100, 1) : 0 }}%</td>
                <td class="domain-column">{{ $totalStudents > 0 ? number_format(($domainNeedCounts[4] / $totalStudents) * 100, 1) : 0 }}%</td>
                <td class="domain-column">{{ $totalStudents > 0 ? number_format(($domainNeedCounts[5] / $totalStudents) * 100, 1) : 0 }}%</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
