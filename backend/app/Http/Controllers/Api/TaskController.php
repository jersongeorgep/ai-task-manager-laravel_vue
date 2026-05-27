<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function index(TaskFilterRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        return TaskResource::collection(
            $this->tasks->allFor($request->user(), $request->validated())
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->store($request->validated());

        return (new TaskResource($task->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load('user'));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        return new TaskResource(
            $this->tasks->update($task, $request->validated())->load('user')
        );
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): TaskResource
    {
        return new TaskResource(
            $this->tasks->updateStatus($task, $request->validated('status'))->load('user')
        );
    }

    public function aiSummary(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json([
            'data' => $this->tasks->aiSummary($task),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->tasks->delete($task);

        return response()->json(null, 204);
    }
}
