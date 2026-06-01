<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppreciationReasonResource;
use App\Models\ActivityLog;
use App\Models\AppreciationReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Full CRUD for appreciation reasons. Restricted to the super-admin role
 * (see the `super-admin` middleware on the routes).
 */
class ReasonManagementController extends Controller
{
    /** All reasons (including inactive) for the management table. */
    public function index(): JsonResponse
    {
        $reasons = AppreciationReason::orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => AppreciationReasonResource::collection($reasons),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'name_ar'    => 'nullable|string|max:255',
            'is_active'  => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $reason = AppreciationReason::create([
            'name'       => $data['name'],
            'name_ar'    => $data['name_ar'] ?? null,
            'is_active'  => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        ActivityLog::log('create_reason', "Created appreciation reason '{$reason->name}'", ['reason_id' => $reason->id]);

        return response()->json([
            'success' => true,
            'message' => __('messages.reason_created'),
            'data'    => new AppreciationReasonResource($reason),
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $reason = AppreciationReason::find($id);

        if (!$reason) {
            return response()->json(['success' => false, 'message' => __('messages.reason_not_found')], 404);
        }

        $data = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'name_ar'    => 'nullable|string|max:255',
            'is_active'  => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $reason->fill($data);
        if ($request->has('is_active')) {
            $reason->is_active = $request->boolean('is_active');
        }
        $reason->save();

        ActivityLog::log('update_reason', "Updated appreciation reason '{$reason->name}'", ['reason_id' => $reason->id]);

        return response()->json([
            'success' => true,
            'message' => __('messages.reason_updated'),
            'data'    => new AppreciationReasonResource($reason),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $reason = AppreciationReason::find($id);

        if (!$reason) {
            return response()->json(['success' => false, 'message' => __('messages.reason_not_found')], 404);
        }

        // FK is nullOnDelete, so existing appreciations keep their history with a null reason.
        $name = $reason->name;
        $reason->delete();

        ActivityLog::log('delete_reason', "Deleted appreciation reason '{$name}'", ['reason_id' => $id]);

        return response()->json([
            'success' => true,
            'message' => __('messages.reason_deleted'),
        ]);
    }
}
