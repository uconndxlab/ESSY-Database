@extends('layouts.app')

@section('content')
    <h1>ESSY Database Reports</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
    <p style="color: red;">{{ session('error') }}</p>
    @endif

    <form action="{{ route('reports.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="file">Upload New Batch File:</label>
        <input type="file" name="file" required>
        <button type="submit" class="btn btn-success">Import</button>
    </form>

    <br>

    @if ($batches->isEmpty())
        <p>No reports found.</p>
    @else
    <table>
        <thead style="background-color: #f0f0f0;">
            <tr>
                <th>Batch ID</th>
                <th>Date Imported</th>
                <th>View Reports</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($batches as $batch)
                <tr>
                    <td>{{ $batch->batch_id }}</td>
                    <td>{{ \Carbon\Carbon::parse($batch->created_at)->format('m/d/Y') }}</td>
                    <td>
                        <a href="{{ route('batches.show', ['batch' => $batch->batch_id]) }}">View</a>
                    </td>
                    <td>
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
    
        
    @endif
@endsection
