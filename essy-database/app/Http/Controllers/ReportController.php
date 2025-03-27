<?php

namespace App\Http\Controllers;

use App\Models\ReportData;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $reports = ReportData::all();
        return view('index', compact('reports'));
    }

    public function show_individual($id)
    {
        $report = ReportData::findOrFail($id);
        return view('show', compact('report'));

    }

}
