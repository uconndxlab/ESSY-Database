@extends('layouts.app')

@section('content')
    <style>
        .breadcrumb { margin-bottom: 20px; font-size: 14px; color: #666; }
        .breadcrumb a { color: #333; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { margin: 0 8px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #f8f8f8; border: 1px solid #ddd; margin-bottom: 20px; }
        .header-bar h1 { margin: 0; font-size: 20px; }
        .header-actions { display: flex; gap: 12px; align-items: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: 600; position: sticky; top: 0; }
        .table-wrapper { max-height: 600px; overflow-y: auto; border: 1px solid #ddd; }
        .actions { white-space: nowrap; }
        .actions form { display: inline; margin-left: 8px; }
        
        .download-progress { display: flex; flex-direction: column; gap: 8px; min-width: 250px; }
        .progress-info { display: flex; flex-direction: column; gap: 4px; }
        .progress-bar { width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; border: 1px solid #ddd; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s ease; border-radius: 10px; }
        #progress-text { font-size: 14px; font-weight: 500; }
        
        .error-message { display: flex; flex-direction: column; gap: 8px; }
        .text-danger { color: #dc3545; font-size: 14px; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; cursor: not-allowed; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn:hover:not([disabled]) { opacity: 0.9; }
        
        #download-section { display: flex; align-items: center; gap: 8px; }
    </style>

    <div class="breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span>/</span>
        <span>Batch {{ $batch }}</span>
    </div>

    <div class="header-bar">
        <h1>Batch ID: {{ $batch }}</h1>
        <div class="header-actions">
            <div id="download-section">
                @if($downloadJob && $downloadJob->isInProgress())
                    <div id="download-progress" class="download-progress">
                        <div class="progress-info">
                            <span id="progress-text">Preparing download...</span>
                            <div class="progress-bar">
                                <div id="progress-fill" class="progress-fill" style="width: 0%"></div>
                            </div>
                        </div>
                        <button disabled class="btn btn-secondary">Download in Progress</button>
                    </div>
                @elseif($downloadJob && $downloadJob->isCompleted())
                    <a href="{{ $downloadJob->getDownloadUrl() }}" class="btn btn-success">Download ZIP (Ready)</a>
                    <a href="{{ route('batches.downloadZip', $batch) }}" class="btn btn-primary btn-sm">Generate New</a>
                @elseif($downloadJob && $downloadJob->hasFailed())
                    <div class="error-message">
                        <span class="text-danger">Download failed: {{ $downloadJob->error_message }}</span>
                        <a href="{{ route('batches.downloadZip', $batch) }}" class="btn btn-primary">Try Again</a>
                    </div>
                @else
                    <a href="{{ route('batches.downloadZip', $batch) }}" class="btn btn-primary">Download All PDFs</a>
                @endif
            </div>
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

    @if($downloadJob && $downloadJob->isInProgress())
    <script>
        let pollInterval;
        
        function checkDownloadStatus() {
            fetch('{{ route('batches.downloadStatus', $batch) }}')
                .then(response => response.json())
                .then(data => {
                    const progressText = document.getElementById('progress-text');
                    const progressFill = document.getElementById('progress-fill');
                    
                    if (data.status === 'processing') {
                        const percentage = data.progress_percentage || 0;
                        progressText.textContent = `Processing: ${data.processed_reports}/${data.total_reports} reports (${percentage}%)`;
                        progressFill.style.width = percentage + '%';
                    } else if (data.status === 'completed') {
                        clearInterval(pollInterval);
                        window.location.reload(); // Reload to show download button
                    } else if (data.status === 'failed') {
                        clearInterval(pollInterval);
                        window.location.reload(); // Reload to show error message
                    } else if (data.status === 'pending') {
                        progressText.textContent = 'Preparing download...';
                        progressFill.style.width = '0%';
                    }
                })
                .catch(error => {
                    console.error('Error checking download status:', error);
                });
        }
        
        // Start polling when page loads
        document.addEventListener('DOMContentLoaded', function() {
            checkDownloadStatus(); // Check immediately
            pollInterval = setInterval(checkDownloadStatus, 2000); // Check every 2 seconds
        });
    </script>
    @endif
@endsection
