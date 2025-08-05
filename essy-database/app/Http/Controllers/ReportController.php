<?php

namespace App\Http\Controllers;

use App\Models\ReportData;
use App\Services\DecisionRulesService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $batches = ReportData::whereNotNull('batch_id')
            ->select('batch_id')
            ->distinct()
            ->orderByDesc('created_at')
            ->get();

        return view('index', compact('batches'));
    }

    public function print_all($id)
    {
        $report = ReportData::findOrFail($id);

        // Set the Excel file path in DecisionRulesService for this report
        if ($report->excel_file_path && file_exists($report->excel_file_path)) {
            $decisionRulesService = app(DecisionRulesService::class);
            $decisionRulesService->setUploadedExcelPath($report->excel_file_path);
        }

        return view('print', compact('report'));
    }


    public function downloadPdf($id)
    {
        $report = ReportData::findOrFail($id);

        // Set the Excel file path in DecisionRulesService for this report
        if ($report->excel_file_path && file_exists($report->excel_file_path)) {
            $decisionRulesService = app(DecisionRulesService::class);
            $decisionRulesService->setUploadedExcelPath($report->excel_file_path);
        }

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
