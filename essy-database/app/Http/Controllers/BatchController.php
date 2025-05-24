<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


use App\Models\ReportData;

class BatchController extends Controller
{
    public function show($batch)
    {
        $reports = ReportData::where('batch_id', $batch)->get();

        return view('batches.show', compact('reports', 'batch'));
    }

    public function downloadZip($batch)
    {
        $reports = \App\Models\ReportData::where('batch_id', $batch)->get();

        if ($reports->isEmpty()) {
            return redirect()->back()->with('error', 'No reports found for this batch.');
        }

        $zipFileName = "batch_{$batch}.zip";
        $tempDir = storage_path("app/tmp-pdfs-" . Str::uuid());
        mkdir($tempDir, 0755, true);

        foreach ($reports as $report) {
            $pdf = Pdf::loadView('print', compact('report'));
            $filename = "Report_{$report->FN_STUDENT}_{$report->LN_STUDENT}_{$report->id}.pdf";
            $pdfPath = $tempDir . '/' . $filename;
            $pdf->save($pdfPath);
        }

        $zipPath = storage_path("app/{$zipFileName}");
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (glob("$tempDir/*.pdf") as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        collect(glob("$tempDir/*"))->each(fn($f) => unlink($f));
        rmdir($tempDir);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function destroy($batch)
    {
        $deleted = ReportData::where('batch_id', $batch)->delete();

        return redirect('/')->with('success', "Batch Deleted Successfully ($deleted Reports were Removed).");
    }
}


