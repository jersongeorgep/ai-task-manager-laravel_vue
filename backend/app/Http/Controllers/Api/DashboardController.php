<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskFilterRequest;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function __invoke(TaskFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->tasks->dashboardFor($request->user(), $request->validated()),
        ]);
    }
}
