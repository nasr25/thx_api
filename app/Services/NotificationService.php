<?php

namespace App\Services;

use App\Models\Appreciation;
use App\Models\UserNotification;
use App\Models\User;

class NotificationService
{
    public function notifyNewAppreciation(Appreciation $appreciation): void
    {
        $sender = $appreciation->sender;
        $receiver = $appreciation->receiver;

        UserNotification::create([
            'user_id'  => $receiver->id,
            'type'     => 'appreciation_received',
            'title'    => "New Appreciation from {$sender->full_name}",
            'title_ar' => "تقدير جديد من {$sender->full_name_ar}",
            'body'     => $appreciation->message
                ? "You received an appreciation: \"{$appreciation->message}\""
                : "You received an appreciation from {$sender->full_name}",
            'body_ar'  => $appreciation->message
                ? "لقد تلقيت تقديراً: \"{$appreciation->message}\""
                : "لقد تلقيت تقديراً من {$sender->full_name_ar}",
            'data' => [
                'appreciation_id' => $appreciation->id,
                'sender_id'       => $sender->id,
                'sender_name'     => $sender->full_name,
                'sender_photo'    => $sender->profile_photo_url,
            ],
        ]);
    }

    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    public function markAllAsRead(User $user): void
    {
        $user->notifications()->unread()->update(['read_at' => now()]);
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = UserNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }
}
