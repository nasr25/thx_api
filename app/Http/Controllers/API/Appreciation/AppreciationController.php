<?php

namespace App\Http\Controllers\API\Appreciation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appreciation\SendAppreciationRequest;
use App\Http\Resources\AppreciationResource;
use App\Repositories\Contracts\AppreciationRepositoryInterface;
use App\Services\AppreciationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppreciationController extends Controller
{
    public function __construct(
        protected AppreciationService $appreciationService,
        protected AppreciationRepositoryInterface $appreciationRepository,
    ) {}

    public function send(SendAppreciationRequest $request): JsonResponse
    {
        try {
            $appreciation = $this->appreciationService->send(
                $request->user(),
                (int) $request->input('receiver_id'),
                $request->input('message'),
                (bool) $request->input('is_public', true)
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.appreciation_sent'),
                'data'    => new AppreciationResource($appreciation),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }

    public function received(Request $request): JsonResponse
    {
        $appreciations = $this->appreciationRepository->getForUser(
            $request->user()->id,
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

    public function sent(Request $request): JsonResponse
    {
        $appreciations = $this->appreciationRepository->getSentByUser(
            $request->user()->id,
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

    public function feed(Request $request): JsonResponse
    {
        $feed = $this->appreciationRepository->getPublicFeed(
            (int) $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data'    => AppreciationResource::collection($feed),
            'meta'    => [
                'current_page' => $feed->currentPage(),
                'last_page'    => $feed->lastPage(),
                'total'        => $feed->total(),
            ],
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $stats = $this->appreciationService->getDashboardStats($request->user());

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'                => $stats['stats'],
                'latest_appreciations' => AppreciationResource::collection($stats['latest_appreciations']),
                'leaderboard'          => $stats['leaderboard'],
            ],
        ]);
    }
}
