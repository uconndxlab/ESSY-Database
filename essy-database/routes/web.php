<?php

use Illuminate\Support\Facades\Route;
use App\Models\ReportData;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportImportController;
use App\Http\Controllers\BatchController;


Route::get('/', [ReportController::class, 'index']);

Route::get('/reports/print/{id}', [ReportController::class, 'print_all']);

Route::get('/reports/{id}', [ReportController::class, 'print_all']);

Route::post('/import', [ReportImportController::class, 'import'])->name('reports.import');

Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');

Route::get('/reports/pdf/{id}', [ReportController::class, 'downloadPdf'])->name('reports.download');

Route::get('/batches/{batch}/download-zip', [\App\Http\Controllers\BatchController::class, 'downloadZip'])->name('batches.downloadZip');
