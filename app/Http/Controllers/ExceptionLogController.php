<?php

namespace App\Http\Controllers;

use App\Models\ExceptionLog;
use Illuminate\Http\Request;

class ExceptionLogController extends Controller
{
    public function getIndex(Request $request)
    {
        $logs = ExceptionLog::orderBy('id', 'desc');

        if($request->term) {
            $logs->where(function($q) use($request) {
                $q->where('url', 'like', "%$request->term%");
                $q->orWhere('command', 'like', "%$request->term%");
            });
        }

        $logs = $logs->paginate(config('app.pagination'));

        if($request->ajax()) {
            return response()->json([
                'itemsHtml' => View('exception-logs.list', compact('logs'))->render(),
                'paginationHtml' => $logs->appends($request->all())->render()
            ]);
        }

        return view('exception-logs.index', compact('logs'));
    }

    public function getSingle($id)
    {
        $log = ExceptionLog::findOrFail($id);

        return view('exception-logs.single', compact('log'));
    }
}
