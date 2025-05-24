@extends('layouts.app')

@section('content')
    <h1>Batch ID: {{ $batch }}</h1> 
    <form action="{{ route('batches.destroy', $batch) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Delete Batch</button>
    </form>
    <br/>
    <a href="{{ route('batches.downloadZip', $batch) }}" class="btn btn-primary">
        Download All PDFs (ZIP)
    </a>
    <br/><br/>

    @if ($reports->isEmpty())
        <p>No reports found for this batch.</p>
    @else
        <table>
            <table>
                <thead style="background-color: #f0f0f0;">
                    <tr>
                        <th>Report ID</th>
                        <th>Student</th>
                        <th>Download</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->FN_STUDENT }} {{ $report->LN_STUDENT }}</td>
                            <td>
                                <a href="{{ route('reports.download', $report->id) }}" class="btn btn-sm btn-primary">PDF</a>
                            </td>
                            <td>
                                <form action="{{ route('reports.destroy', $report->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete this report">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
    @endif
@endsection
