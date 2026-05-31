<?php

namespace App\Repositories\Eloquent;

use App\Models\Appreciation;
use App\Repositories\Contracts\AppreciationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AppreciationRepository implements AppreciationRepositoryInterface
{
    public function findById(int $id): ?Appreciation
    {
        return Appreciation::with(['sender', 'receiver'])->find($id);
    }

    public function getForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Appreciation::with(['sender.department'])
            ->where('receiver_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getSentByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Appreciation::with(['receiver.department'])
            ->where('sender_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getMonthlyCountForSender(int $userId): int
    {
        return Appreciation::where('sender_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function getMonthlyCountForSenderToReceiver(int $senderId, int $receiverId): int
    {
        return Appreciation::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function getDailyCountForSender(int $userId): int
    {
        return Appreciation::where('sender_id', $userId)
            ->whereDate('created_at', today())
            ->count();
    }

    public function create(array $data): Appreciation
    {
        return Appreciation::create($data);
    }

    public function delete(Appreciation $appreciation, int $deletedBy, string $reason): bool
    {
        $appreciation->update([
            'deleted_by'     => $deletedBy,
            'deleted_reason' => $reason,
        ]);
        return $appreciation->delete();
    }

    public function getMonthlyTrends(int $months = 12): array
    {
        return Appreciation::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths($months))
            ->withTrashed(false)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getDepartmentStats(): array
    {
        return DB::table('appreciations')
            ->join('users', 'appreciations.receiver_id', '=', 'users.id')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->select('departments.name', 'departments.name_ar', DB::raw('COUNT(appreciations.id) as total'))
            ->whereNull('appreciations.deleted_at')
            ->groupBy('departments.id', 'departments.name', 'departments.name_ar')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function getTotalCount(): int
    {
        return Appreciation::count();
    }

    public function getLatestForUser(int $userId, int $limit = 5): Collection
    {
        return Appreciation::with(['sender.department'])
            ->where('receiver_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getPublicFeed(int $perPage = 20): LengthAwarePaginator
    {
        return Appreciation::with(['sender.department', 'receiver.department'])
            ->public()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
