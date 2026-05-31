<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /** List users with their roles (admin management table). */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('department')->withCount('receivedAppreciations');

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        $users = $query->orderBy('full_name')->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users->load('roles')),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /** Grant or revoke the admin role for a user. */
    public function updateRole(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'is_admin' => 'required|boolean',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => __('messages.user_not_found')], 404);
        }

        // Protect against removing the last admin / self-demotion lockout.
        if (!$request->boolean('is_admin') && $user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.cannot_demote_self'),
            ], 422);
        }

        if ($request->boolean('is_admin')) {
            $user->syncRoles(['admin']);
            $action = 'grant_admin';
            $desc   = "Granted admin access to {$user->full_name}";
        } else {
            $user->syncRoles(['employee']);
            $action = 'revoke_admin';
            $desc   = "Revoked admin access from {$user->full_name}";
        }

        ActivityLog::log($action, $desc, ['target_user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => __('messages.role_updated'),
            'data'    => new UserResource($user->load(['department', 'roles'])),
        ]);
    }

    /** Activate / deactivate a user. */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $request->validate(['is_active' => 'required|boolean']);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('messages.user_not_found')], 404);
        }

        if (!$request->boolean('is_active') && $user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.cannot_demote_self'),
            ], 422);
        }

        $user->update(['is_active' => $request->boolean('is_active')]);

        ActivityLog::log('update_user_status', "Set {$user->full_name} active=" . ($request->boolean('is_active') ? '1' : '0'));

        return response()->json([
            'success' => true,
            'message' => __('messages.role_updated'),
            'data'    => new UserResource($user->load(['department', 'roles'])),
        ]);
    }
}
