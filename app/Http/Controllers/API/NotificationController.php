<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success'      => true,
            'data'         => $notifications->items(),
            'unread_count' => $this->notificationService->getUnreadCount($request->user()),
            'meta'         => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => ['count' => $this->notificationService->getUnreadCount($request->user())],
        ]);
    }

    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $result = $this->notificationService->markAsRead($id, $request->user());

        return response()->json([
            'success' => $result,
            'message' => $result ? __('messages.notification_read') : __('messages.not_found'),
        ], $result ? 200 : 404);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'message' => __('messages.all_notifications_read'),
        ]);
    }
}
