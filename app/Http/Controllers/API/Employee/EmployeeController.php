<?php

namespace App\Http\Controllers\API\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppreciationResource;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AppreciationRepositoryInterface;
use App\Services\EmployeeDirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected AppreciationRepositoryInterface $appreciationRepository,
        protected EmployeeDirectoryService $directoryService,
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

        $term      = $request->input('q');
        $currentId = $request->user()?->id;

        // 1. If an external directory endpoint is configured, search it.
        //    Each result is upserted into the local users table (so it has a
        //    real ID and can receive appreciations).
        if ($this->directoryService->isConfigured()) {
            $external = $this->directoryService->search($term)
                ->reject(fn ($u) => $u->id === $currentId);

            if ($external->isNotEmpty()) {
                return response()->json([
                    'success' => true,
                    'source'  => 'directory',
                    'data'    => UserResource::collection($external),
                ]);
            }
        }

        // 2. Fall back to local database search.
        $results = $this->userRepository->search(
            $term,
            (int) $request->get('per_page', 10)
        );

        return response()->json([
            'success' => true,
            'source'  => 'local',
            'data'    => UserResource::collection($results),
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        // Only an admin (or the employee themselves) may view a full profile/dashboard.
        if (!$this->canViewProfile($request->user(), $id)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

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
        if (!$this->canViewProfile($request->user(), $id)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

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

    /**
     * A profile/dashboard at /employees/{id} can be viewed only by an admin
     * or by the employee themselves. Everyone else is forbidden.
     */
    private function canViewProfile(?\App\Models\User $viewer, int $targetId): bool
    {
        if (!$viewer) {
            return false;
        }
        return $viewer->id === $targetId
            || $viewer->hasAnyRole(['admin', 'super-admin']);
    }
}
