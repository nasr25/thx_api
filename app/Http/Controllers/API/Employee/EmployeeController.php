<?php

namespace App\Http\Controllers\API\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppreciationResource;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AppreciationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected AppreciationRepositoryInterface $appreciationRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $employees = $this->userRepository->getAll(
            $request->only(['search', 'department_id', 'is_active']),
            (int) $request->get('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($employees),
            'meta'    => [
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'per_page'     => $employees->perPage(),
                'total'        => $employees->total(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $results = $this->userRepository->search(
            $request->input('q'),
            (int) $request->get('per_page', 10)
        );

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($results),
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $employee = $this->userRepository->findById($id);

        if (!$employee || !$employee->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('messages.user_not_found'),
            ], 404);
        }

        $stats = [
            'total_received'   => $employee->receivedAppreciations()->count(),
            'monthly_received' => $employee->receivedAppreciations()->thisMonth()->count(),
        ];

        $latest = $this->appreciationRepository->getLatestForUser($employee->id, 10);

        return response()->json([
            'success' => true,
            'data' => [
                'employee'            => new UserResource($employee),
                'stats'               => $stats,
                'latest_appreciations' => AppreciationResource::collection($latest),
            ],
        ]);
    }

    public function appreciations(int $id, Request $request): JsonResponse
    {
        $employee = $this->userRepository->findById($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => __('messages.user_not_found'),
            ], 404);
        }

        $appreciations = $this->appreciationRepository->getForUser(
            $employee->id,
            (int) $request->get('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data'    => AppreciationResource::collection($appreciations),
            'meta'    => [
                'current_page' => $appreciations->currentPage(),
                'last_page'    => $appreciations->lastPage(),
                'total'        => $appreciations->total(),
            ],
        ]);
    }
}
