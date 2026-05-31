<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'overview'          => $this->analyticsService->getOverview(),
                'monthly_trends'    => $this->analyticsService->getMonthlyTrends(12),
                'department_stats'  => $this->analyticsService->getDepartmentStats(),
                'top_appreciated'   => $this->analyticsService->getTopAppreciated(10),
                'top_senders'       => $this->analyticsService->getTopSenders(10),
            ],
        ]);
    }
}
