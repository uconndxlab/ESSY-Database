<?php

use Illuminate\Support\Facades\Route;
use App\Models\ReportData;
use App\Http\Controllers\ReportController;

Route::get('/', [ReportController::class, 'index']);

Route::get('/reports/{id}', [ReportController::class, 'show_individual']);
