@extends('layouts.app')

@section('content')
    <style>
        .breadcrumb { margin-bottom: 20px; font-size: 14px; color: #666; }
        .breadcrumb a { color: #333; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { margin: 0 8px; }
        .tabs { border-bottom: 2px solid #ddd; margin-bottom: 20px; }
        .tabs button { background: none; border: none; padding: 12px 24px; cursor: pointer; font-size: 16px; border-bottom: 3px solid transparent; }
        .tabs button.active { border-bottom-color: #333; font-weight: 600; }
        .tabs button:hover { background: #f5f5f5; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .import-bar { background: #f8f8f8; padding: 16px; margin-bottom: 20px; border: 1px solid #ddd; }
        .import-bar form { display: flex; gap: 12px; align-items: center; }
        .import-bar input[type="file"] { flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: 600; position: sticky; top: 0; }
        .table-wrapper { max-height: 600px; overflow-y: auto; border: 1px solid #ddd; }
        .actions { white-space: nowrap; }
        .actions form { display: inline; margin-left: 8px; }
        .alert { padding: 12px 16px; margin-bottom: 16px; border-left: 4px solid; }
        .alert-success { background: #d4edda; border-color: #28a745; }
        .alert-error { background: #f8d7da; border-color: #dc3545; }
    </style>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="breadcrumb">
        <span>Home</span>
    </div>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('database')">Database Reports</button>
        <button class="tab-btn" onclick="switchTab('gate1')">Gate 1 Reports</button>
    </div>

    <div id="database-tab" class="tab-content active">
        <div class="import-bar">
            <form action="{{ route('reports.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" required>
                <button type="submit" class="btn btn-success">Import</button>
            </form>
        </div>

        @if ($batches->isEmpty())
            <p>No reports found.</p>
        @else
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Date Imported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            <tr>
                                <td>{{ $batch->batch_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($batch->created_at)->format('m/d/Y') }}</td>
                                <td class="actions">
                                    <a href="{{ route('batches.show', ['batch' => $batch->batch_id]) }}">View</a>
                                    <form action="{{ route('batches.destroy', $batch->batch_id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div id="gate1-tab" class="tab-content">
        <div class="import-bar">
            <form action="{{ route('reports.importGate1') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" required>
                <button type="submit" class="btn btn-success">Import</button>
            </form>
        </div>

        @if ($gate1Batches->isEmpty())
            <p>No Gate 1 reports found.</p>
        @else
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Date Imported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gate1Batches as $batch)
                            <tr>
                                <td>{{ $batch->batch_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($batch->created_at)->format('m/d/Y') }}</td>
                                <td class="actions">
                                    <a href="{{ route('gate1.batch', ['batch' => $batch->batch_id]) }}" target="_blank">View</a>
                                    <a href="{{ route('gate1.download', ['batch' => $batch->batch_id]) }}" class="btn btn-sm btn-primary">PDF</a>
                                    <form action="{{ route('batches.destroy', $batch->batch_id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
@endsection
