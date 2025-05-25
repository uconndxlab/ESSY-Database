<?php

namespace App\Http\Controllers;

use App\Models\ReportData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $batches = ReportData::whereNotNull('batch_id')
            ->select('batch_id', DB::raw('MAX(created_at) as created_at'))
            ->groupBy('batch_id')
            ->orderByDesc(DB::raw('MAX(created_at)'))
            ->get();

        return view('index', compact('batches'));
    }

    public function print_all($id)
    {
        $report = ReportData::findOrFail($id);
        return view('print', compact('report'));
    }

    public function downloadPdf($id)
    {
        $report = ReportData::findOrFail($id);

        $pdf = Pdf::loadView('print', compact('report'))
                 ->setPaper('letter', 'portrait');

        return $pdf->download("report_{$report->id}.pdf");
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
