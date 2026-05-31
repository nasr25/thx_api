<?php

namespace App\Services;

use App\Models\Appreciation;
use App\Models\User;
use App\Repositories\Contracts\AppreciationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    public function __construct(
        protected AppreciationRepositoryInterface $appreciationRepository,
        protected UserRepositoryInterface $userRepository,
    ) {}

    public function getOverview(): array
    {
        $totalAppreciations  = Appreciation::count();
        $monthlyAppreciations = Appreciation::thisMonth()->count();
        $totalEmployees      = User::active()->count();
        $activeThisMonth     = Appreciation::thisMonth()
            ->select('sender_id')
            ->distinct()
            ->count('sender_id');

        return [
            'total_appreciations'   => $totalAppreciations,
            'monthly_appreciations' => $monthlyAppreciations,
            'total_employees'       => $totalEmployees,
            'active_this_month'     => $activeThisMonth,
            'engagement_rate'       => $totalEmployees > 0
                ? round(($activeThisMonth / $totalEmployees) * 100, 1)
                : 0,
        ];
    }

    public function getMonthlyTrends(int $months = 12): array
    {
        $trends = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $count = Appreciation::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $trends[] = [
                'month'  => $date->format('Y-m'),
                'label'  => $date->translatedFormat('M Y'),
                'label_ar' => $date->locale('ar')->translatedFormat('M Y'),
                'count'  => $count,
            ];
        }
        return $trends;
    }

    public function getDepartmentStats(): array
    {
        return DB::table('appreciations')
            ->join('users as receivers', 'appreciations.receiver_id', '=', 'receivers.id')
            ->leftJoin('departments', 'receivers.department_id', '=', 'departments.id')
            ->select(
                DB::raw('COALESCE(departments.name, "No Department") as name'),
                DB::raw('COALESCE(departments.name_ar, "لا يوجد قسم") as name_ar'),
                DB::raw('COUNT(appreciations.id) as total')
            )
            ->whereNull('appreciations.deleted_at')
            ->groupBy('departments.id', 'departments.name', 'departments.name_ar')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function getTopAppreciated(int $limit = 10): array
    {
        return User::withCount('receivedAppreciations')
            ->active()
            ->orderByDesc('received_appreciations_count')
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'id'          => $u->id,
                'full_name'   => $u->full_name,
                'full_name_ar' => $u->full_name_ar,
                'department'  => $u->department?->name,
                'department_ar' => $u->department?->name_ar,
                'photo_url'   => $u->profile_photo_url,
                'count'       => $u->received_appreciations_count,
            ])
            ->toArray();
    }

    public function getTopSenders(int $limit = 10): array
    {
        return User::withCount('sentAppreciations')
            ->active()
            ->orderByDesc('sent_appreciations_count')
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'id'        => $u->id,
                'full_name' => $u->full_name,
                'count'     => $u->sent_appreciations_count,
            ])
            ->toArray();
    }

    public function exportCsv(string $period = 'month'): string
    {
        $query = Appreciation::with(['sender.department', 'receiver.department']);

        if ($period === 'month') {
            $query->thisMonth();
        } elseif ($period === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        $appreciations = $query->get();

        $headers = ['ID', 'Sender', 'Receiver', 'Department', 'Message', 'Date'];
        $rows    = $appreciations->map(fn ($a) => [
            $a->id,
            $a->sender->full_name,
            $a->receiver->full_name,
            $a->receiver->department?->name ?? 'N/A',
            $a->message ?? '',
            $a->created_at->format('Y-m-d H:i'),
        ])->toArray();

        $output  = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $output .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return $output;
    }
}
