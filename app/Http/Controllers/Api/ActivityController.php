<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        return [
            'data' => ActivityLog::with('user:id,name')
                ->when($request->get('aksi'), fn ($q, $v) => $q->where('aksi', $v))
                ->latest('created_at')->limit(100)->get(),
            'aksiList' => ActivityLog::distinct()->orderBy('aksi')->pluck('aksi'),
        ];
    }
}
