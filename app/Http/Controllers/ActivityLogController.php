<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->get('aksi'), fn ($q, $v) => $q->where('aksi', $v))
            ->latest('created_at')->paginate(30)->withQueryString();

        $aksiList = ActivityLog::distinct()->orderBy('aksi')->pluck('aksi');

        return view('activity.index', compact('logs', 'aksiList'));
    }
}
