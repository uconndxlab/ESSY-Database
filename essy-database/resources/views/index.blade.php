@extends('layouts.app')

@section('content')
    <h1>ESSY Database Reports</h1>

    @if ($reports->isEmpty())
        <p>No reports found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Finished</th>
                    <th>Response ID</th>
                    <th>Recipient Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reports as $report)
                    <tr>
                        <td><a href="{{ url('/reports/' . $report->id) }}">{{ $report->id }}</a></td>
                        <td>{{ $report->StartDate }}</td>
                        <td>{{ $report->EndDate }}</td>
                        <td>{{ $report->Status }}</td>
                        <td>{{ $report->Progress }}%</td>
                        <td>{{ $report->Finished ? 'Yes' : 'No' }}</td>
                        <td>{{ $report->ResponseId }}</td>
                        <td>{{ $report->RecipientFirstName }} {{ $report->RecipientLastName }}</td>
                        <td>{{ $report->RecipientEmail }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
