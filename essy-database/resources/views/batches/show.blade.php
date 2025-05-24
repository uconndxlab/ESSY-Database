@extends('layouts.app')

@section('content')
    <h1>Batch {{ $batch }}</h1>

    @if ($reports->isEmpty())
        <p>No reports found for this batch.</p>
    @else
        @foreach ($reports as $report)
            <div>
                <p>{{ $report->id }} — {{ $report->FN_STUDENT }} {{ $report->LN_STUDENT }}</p>
                <a href="{{ route('reports.download', $report->id) }}">Download PDF</a>
            </div>
            <hr>
        @endforeach
    @endif

    <a href="{{ route('batches.downloadZip', $batch) }}" class="btn btn-primary">
        Download All PDFs (ZIP)
    </a>
    <br><br>
    
@endsection
