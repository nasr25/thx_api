<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppreciationResource;
use App\Repositories\Contracts\AppreciationRepositoryInterface;
use App\Services\AppreciationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppreciationManagementController extends Controller
{
    public function __construct(
        protected AppreciationService $appreciationService,
        protected AppreciationRepositoryInterface $appreciationRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $appreciations = $this->appreciationRepository->getPublicFeed(
            (int) $request->get('per_page', 20)
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

    public function destroy(int $id, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $this->appreciationService->delete(
                $id,
                $request->user(),
                $request->input('reason', '')
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.appreciation_deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 404);
        }
    }
}
