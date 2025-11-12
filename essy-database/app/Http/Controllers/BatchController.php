<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Models\ReportData;
use App\Models\Gate1Report;
use App\Models\BatchDownloadJob;
use App\Jobs\GenerateBatchPdfZip;

class BatchController extends Controller
{
    public function show($batch)
    {
        $reports = ReportData::where('batch_id', $batch)->get();
        
        // Get the latest download job for this batch
        $downloadJob = BatchDownloadJob::where('batch_id', $batch)
            ->latest()
            ->first();

        return view('batches.show', compact('reports', 'batch', 'downloadJob'));
    }

    public function downloadZip($batch)
    {
        $reports = ReportData::where('batch_id', $batch)->get();

        if ($reports->isEmpty()) {
            return redirect()->back()->with('error', 'No reports found for this batch.');
        }

        // Check if there's already a job in progress for this batch
        $existingJob = BatchDownloadJob::where('batch_id', $batch)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingJob) {
            return redirect()->back()->with('info', 'A download is already being prepared for this batch. Please wait for it to complete.');
        }

        // Create a new download job record
        $downloadJob = BatchDownloadJob::create([
            'batch_id' => $batch,
            'status' => 'pending'
        ]);

        // Dispatch the job to the queue
        GenerateBatchPdfZip::dispatch($batch, $downloadJob->id);

        return redirect()->back()->with('success', 'Your download is being prepared. This page will refresh automatically to show progress.');
    }

    public function downloadJobStatus($batch)
    {
        $downloadJob = BatchDownloadJob::where('batch_id', $batch)
            ->latest()
            ->first();

        if (!$downloadJob) {
            return response()->json(['status' => 'not_found']);
        }

        return response()->json([
            'status' => $downloadJob->status,
            'progress_percentage' => $downloadJob->progress_percentage,
            'processed_reports' => $downloadJob->processed_reports,
            'total_reports' => $downloadJob->total_reports,
            'error_message' => $downloadJob->error_message,
            'download_url' => $downloadJob->getDownloadUrl(),
            'completed_at' => $downloadJob->completed_at?->format('Y-m-d H:i:s')
        ]);
    }

    public function downloadFile($downloadJobId)
    {
        $downloadJob = BatchDownloadJob::find($downloadJobId);

        if (!$downloadJob || !$downloadJob->isCompleted() || !$downloadJob->file_path) {
            return redirect()->back()->with('error', 'Download file not found or not ready.');
        }

        $filePath = storage_path("app/{$downloadJob->file_path}");

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Download file not found on disk.');
        }

        $filename = "batch_{$downloadJob->batch_id}.zip";
        
        return response()->download($filePath, $filename);
    }

    public function destroy($batch)
    {
        // Try deleting from both Gate 1 and Gate 2 (regular) tables
        $deletedGate1 = Gate1Report::where('batch_id', $batch)->delete();
        $deletedGate2 = ReportData::where('batch_id', $batch)->delete();
        
        //Fancy way of just getting whatevr isnt null kinda cool 
        $totalDeleted = $deletedGate1 + $deletedGate2;

        if ($totalDeleted > 0) {
            return redirect('/')->with('success', "Batch Deleted Successfully ($totalDeleted Reports were Removed).");
        } else {
            return redirect('/')->with('error', "Batch not found.");
        }
    }
}


