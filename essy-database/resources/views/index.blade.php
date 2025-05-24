@extends('layouts.app')

@section('content')
    <h1>ESSY Database Reports</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <form action="{{ route('reports.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="file">Upload Excel or CSV File:</label>
        <input type="file" name="file" required>
        <button type="submit">Import</button>
    </form>

    <br>

    @if ($batches->isEmpty())
        <p>No reports found.</p>
    @else
    <ul>
        @foreach ($batches as $batch)
            <li>
                <a href="{{ route('batches.show', ['batch' => $batch->batch_id]) }}">
                    Batch {{ $batch->batch_id }}
                </a>
            </li>
        @endforeach
        </ul>    
    @endif
@endsection
