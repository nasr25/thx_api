<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    public function analytics(Request $request): JsonResponse
    {
        $months = (int) $request->get('months', 12);

        return response()->json([
            'success' => true,
            'data' => [
                'overview'         => $this->analyticsService->getOverview(),
                'monthly_trends'   => $this->analyticsService->getMonthlyTrends($months),
                'department_stats' => $this->analyticsService->getDepartmentStats(),
                'top_appreciated'  => $this->analyticsService->getTopAppreciated(10),
                'top_senders'      => $this->analyticsService->getTopSenders(10),
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $request->validate(['period' => 'in:month,quarter,year,all']);
        $period = $request->get('period', 'month');

        $csv = $this->analyticsService->exportCsv($period);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=appreciations_{$period}_" . now()->format('Y-m-d') . '.csv',
        ]);
    }
}
