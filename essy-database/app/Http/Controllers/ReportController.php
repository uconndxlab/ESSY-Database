<?php

namespace App\Http\Controllers;

use App\Models\ReportData;
use App\Models\Gate1Report;
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

        //Now we fetch from the gate 1 table to on the index to display those jobs :)
        $gate1Batches = Gate1Report::select('batch_id', 'created_at')
            ->distinct()
            ->orderByDesc('created_at')
            ->get();

        return view('index', compact('batches', 'gate1Batches'));
    }

    public function print_all($id)
    {
        $report = ReportData::findOrFail($id);
        return view('print', compact('report'));
    }

    public function showGate1Batch($batch)
    {
        $reports = Gate1Report::where('batch_id', $batch)->get();
        
        if ($reports->isEmpty()) {
            return redirect('/')->with('error', 'No Gate 1 reports found for this batch.');
        }
        
        return view('printGate1', compact('reports', 'batch'));
    }

    public function downloadGate1Pdf($batch)
    {
        $reports = Gate1Report::where('batch_id', $batch)->get();
        
        if ($reports->isEmpty()) {
            return redirect('/')->with('error', 'No Gate 1 reports found for this batch.');
        }

        $pdf = Pdf::loadView('printGate1', compact('reports', 'batch'))
                ->setPaper('letter', 'landscape');

        return $pdf->download("ESSY_Gate1_Batch_{$batch}.pdf");
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
