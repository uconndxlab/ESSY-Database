<?php

namespace App\Http\Controllers;

use App\Models\ReportData;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $batches = ReportData::whereNotNull('batch_id')
            ->select('batch_id', 'created_at')
            ->distinct()
            ->orderByDesc('created_at')
            ->get();

        return view('index', compact('batches'));
    }

    public function print_all($id)
    {
        $report = ReportData::findOrFail($id);
        return view('print', compact('report'));
    }

    public function showGate1Batch($batch)
    {
        // Get all reports for this Gate 1 batch
        $reports = ReportData::where('batch_id', $batch)->get();
        
        if ($reports->isEmpty()) {
            return redirect('/')->with('error', 'No Gate 1 reports found for this batch.');
        }
        
        return view('printGate1', compact('reports', 'batch'));
    }


    public function downloadPdf($id)
    {
        $report = ReportData::findOrFail($id);

        $pdf = Pdf::loadView('print', compact('report'))
                ->setPaper('letter', 'portrait');

        return $pdf->download("ESSY_{$report->SCHOOL}_{$report->DEM_GRADE}_{$report->LN_STUDENT}.pdf");
    }

    public function destroy($id)
    {
        $report = ReportData::findOrFail($id);
        $batchId = $report->batch_id;
        $report->delete();

        return redirect()->route('batches.show', ['batch' => $batchId])
                        ->with('success', 'Report Deleted Successfully.');
    }

}
