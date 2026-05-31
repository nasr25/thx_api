<?php

namespace App\Repositories\Contracts;

use App\Models\Appreciation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AppreciationRepositoryInterface
{
    public function findById(int $id): ?Appreciation;
    public function getForUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    public function getSentByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    public function getMonthlyCountForSender(int $userId): int;
    public function getMonthlyCountForSenderToReceiver(int $senderId, int $receiverId): int;
    public function getDailyCountForSender(int $userId): int;
    public function create(array $data): Appreciation;
    public function delete(Appreciation $appreciation, int $deletedBy, string $reason): bool;
    public function getMonthlyTrends(int $months = 12): array;
    public function getDepartmentStats(): array;
    public function getTotalCount(): int;
    public function getLatestForUser(int $userId, int $limit = 5): Collection;
    public function getPublicFeed(int $perPage = 20): LengthAwarePaginator;
}
