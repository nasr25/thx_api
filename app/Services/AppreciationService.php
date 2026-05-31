<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appreciation;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\Contracts\AppreciationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Jobs\SendAppreciationEmailJob;

class AppreciationService
{
    public function __construct(
        protected AppreciationRepositoryInterface $appreciationRepository,
        protected UserRepositoryInterface $userRepository,
        protected NotificationService $notificationService,
    ) {}

    public function send(User $sender, int $receiverId, ?string $message = null, bool $isPublic = true): Appreciation
    {
        $receiver = $this->userRepository->findById($receiverId);

        if (!$receiver) {
            throw new \Exception(__('messages.user_not_found'), 404);
        }

        // Prevent self-appreciation
        if ($sender->id === $receiverId) {
            throw new \Exception(__('messages.no_self_appreciation'), 422);
        }

        // Check receiver is active
        if (!$receiver->is_active) {
            throw new \Exception(__('messages.user_not_found'), 404);
        }

        // Check monthly limit
        $limit = $sender->getMonthlyLimit();
        $monthlyCount = $this->appreciationRepository->getMonthlyCountForSender($sender->id);

        if ($monthlyCount >= $limit) {
            throw new \Exception(
                __('messages.monthly_limit_reached', ['limit' => $limit]),
                422
            );
        }

        // Check daily limit
        $dailyLimit = (int) Setting::getValue('max_daily_appreciations', 5);
        $dailyCount = $this->appreciationRepository->getDailyCountForSender($sender->id);

        if ($dailyCount >= $dailyLimit) {
            throw new \Exception(
                __('messages.daily_limit_reached', ['limit' => $dailyLimit]),
                422
            );
        }

        // Check max appreciations to same receiver per month
        $maxSameReceiver = (int) Setting::getValue('max_same_receiver_per_month', 3);
        $sameReceiverCount = $this->appreciationRepository->getMonthlyCountForSenderToReceiver($sender->id, $receiverId);

        if ($sameReceiverCount >= $maxSameReceiver) {
            throw new \Exception(
                __('messages.same_receiver_limit', ['limit' => $maxSameReceiver]),
                422
            );
        }

        $appreciation = $this->appreciationRepository->create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiverId,
            'message'     => $message ? strip_tags(trim($message)) : null,
            'is_public'   => $isPublic,
        ]);

        ActivityLog::log('send_appreciation', "Sent appreciation to {$receiver->full_name}", [
            'receiver_id'    => $receiverId,
            'appreciation_id' => $appreciation->id,
        ]);

        // Send notification (via queue)
        $this->notificationService->notifyNewAppreciation($appreciation);

        if ((bool) Setting::getValue('email_notifications_enabled', true)) {
            SendAppreciationEmailJob::dispatch($appreciation)->onQueue('notifications');
        }

        return $appreciation->load(['sender', 'receiver']);
    }

    public function delete(int $appreciationId, User $admin, string $reason = ''): bool
    {
        $appreciation = $this->appreciationRepository->findById($appreciationId);

        if (!$appreciation) {
            throw new \Exception(__('messages.not_found'), 404);
        }

        $result = $this->appreciationRepository->delete($appreciation, $admin->id, $reason);

        ActivityLog::log('delete_appreciation', "Admin deleted appreciation #{$appreciationId}", [
            'appreciation_id' => $appreciationId,
            'reason'          => $reason,
        ]);

        return $result;
    }

    public function getDashboardStats(User $user): array
    {
        $totalReceived = $user->receivedAppreciations()->count();
        $monthlyReceived = $user->receivedAppreciations()->thisMonth()->count();
        $totalSent = $user->sentAppreciations()->count();
        $monthlySent = $user->sentAppreciations()->thisMonth()->count();
        $monthlyLimit = $user->getMonthlyLimit();
        $monthlyUsed = $this->appreciationRepository->getMonthlyCountForSender($user->id);

        $latestAppreciations = $this->appreciationRepository->getLatestForUser($user->id, 5);
        $leaderboard = $this->userRepository->getLeaderboard(10);

        return [
            'stats' => [
                'total_received'    => $totalReceived,
                'monthly_received'  => $monthlyReceived,
                'total_sent'        => $totalSent,
                'monthly_sent'      => $monthlySent,
                'monthly_limit'     => $monthlyLimit,
                'monthly_remaining' => max(0, $monthlyLimit - $monthlyUsed),
            ],
            'latest_appreciations' => $latestAppreciations,
            'leaderboard'          => $leaderboard,
        ];
    }
}
