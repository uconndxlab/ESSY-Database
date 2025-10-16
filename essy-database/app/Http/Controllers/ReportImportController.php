<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ReportImportController extends Controller
{
    public function import(Request $request)
    {
        $file = $request->file('file');

        if (!$file) {
            return redirect()->back()->with('error', 'No file uploaded.');
        }

        $tempPath = $file->getRealPath();

        $exitCode = Artisan::call('report-data:importxlsx', [
            'file' => $tempPath,
        ]);

        if ($exitCode !== 0) {
            return redirect()->back()->with('error', 'Error Importing Spreadsheet');
        }

        $batchFile = storage_path('app/last_batch.txt');
        $batchId = File::exists($batchFile) ? trim(File::get($batchFile)) : null;

        if ($batchId) {
            return redirect()->route('batches.show', ['batch' => $batchId]);
        } else {
            return redirect()->back()->with('error', 'Import succeeded, but batch ID not found.');
        }
    }

    public function importGate1(Request $request)
    {
        $file = $request->file('file');

        if (!$file) {
            return redirect()->back()->with('error', 'No file uploaded.');
        }

        $tempPath = $file->getRealPath();

        $exitCode = Artisan::call('report-data:importgate1', [
            'file' => $tempPath,
        ]);

        if ($exitCode !== 0) {
            return redirect()->back()->with('error', 'Error Importing Gate 1 Spreadsheet');
        }

        $batchFile = storage_path('app/last_gate1_batch.txt');
        $batchId = File::exists($batchFile) ? trim(File::get($batchFile)) : null;

        if ($batchId) {
            return redirect()->route('gate1.batch', ['batch' => $batchId])
                ->with('success', 'Gate 1 data imported successfully.');
        } else {
            return redirect()->back()->with('error', 'Import succeeded, but batch ID not found.');
        }
    }
}
