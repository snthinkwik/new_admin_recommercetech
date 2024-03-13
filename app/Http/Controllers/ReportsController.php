<?php

namespace App\Http\Controllers;

use App\Models\StockLog;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function getChangeLogIndex(Request $request)
    {
        $logs = StockLog::orderBy('id', 'desc')->limit(100)->get();

        return view('reports.change-log', compact('logs'));
    }
}
