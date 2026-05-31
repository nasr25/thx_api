<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->when($request->input('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->input('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->input('from'), fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->input('to'), fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => ActivityLogResource::collection($logs),
            'meta'    => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
