@extends('layouts.app')

@section('content')
    <style>
        .breadcrumb { margin-bottom: 20px; font-size: 14px; color: #666; }
        .breadcrumb a { color: #333; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { margin: 0 8px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #f8f8f8; border: 1px solid #ddd; margin-bottom: 20px; }
        .header-bar h1 { margin: 0; font-size: 20px; }
        .header-actions { display: flex; gap: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: 600; position: sticky; top: 0; }
        .table-wrapper { max-height: 600px; overflow-y: auto; border: 1px solid #ddd; }
        .actions { white-space: nowrap; }
        .actions form { display: inline; margin-left: 8px; }
    </style>

    <div class="breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span>/</span>
        <span>Batch {{ $batch }}</span>
    </div>

    <div class="header-bar">
        <h1>Batch ID: {{ $batch }}</h1>
        <div class="header-actions">
            <a href="{{ route('batches.downloadZip', $batch) }}" class="btn btn-primary">Download All PDFs</a>
            <form action="{{ route('batches.destroy', $batch) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Delete Batch</button>
            </form>
        </div>
    </div>

    @if ($reports->isEmpty())
        <p>No reports found for this batch.</p>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Student</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->FN_STUDENT }} {{ $report->LN_STUDENT }}</td>
                            <td class="actions">
                                <a href="{{ route('reports.download', $report->id) }}">PDF</a>
                                <form action="{{ route('reports.destroy', $report->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this report?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
