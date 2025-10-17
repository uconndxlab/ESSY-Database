<?php

use Illuminate\Support\Facades\Route;
use App\Models\ReportData;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportImportController;
use App\Http\Controllers\BatchController;


Route::get('/', [ReportController::class, 'index']);

Route::get('/reports/print/{id}', [ReportController::class, 'print_all']);

Route::get('/reports/{id}', [ReportController::class, 'print_all']);

Route::get('/gate1/batch/{batch}', [ReportController::class, 'showGate1Batch'])->name('gate1.batch');

Route::get('/gate1/batch/{batch}/pdf', [ReportController::class, 'downloadGate1Pdf'])->name('gate1.download');

Route::post('/import', [ReportImportController::class, 'import'])->name('reports.import');

Route::post('/importGate1', [ReportImportController::class, 'importGate1'])->name('reports.importGate1');

Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');

Route::get('/reports/pdf/{id}', [ReportController::class, 'downloadPdf'])->name('reports.download');

Route::get('/batches/{batch}/download-zip', [\App\Http\Controllers\BatchController::class, 'downloadZip'])->name('batches.downloadZip');

Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');

Route::delete('/reports/{id}', [ReportController::class, 'destroy'])->name('reports.destroy');