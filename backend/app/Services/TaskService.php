<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly AIService $aiService,
    ) {
    }

    public function allFor(User $user, array $filters = [])
    {
        return $this->tasks->all($this->scopeFilters($user, $filters));
    }

    public function dashboardFor(User $user, array $filters = []): array
    {
        return $this->tasks->counts($this->scopeFilters($user, $filters));
    }

    public function find(int $id): Task
    {
        return $this->tasks->find($id);
    }

    public function store(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            $task = $this->tasks->create($data);
            $aiData = $this->aiService->generateSummary($task);

            return $this->tasks->update($task->id, $aiData);
        });
    }

    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $updatedTask = $this->tasks->update($task->id, $data);
            $fieldsRequiringAIRefresh = ['title', 'description', 'priority', 'status', 'due_date'];

            if (array_intersect($fieldsRequiringAIRefresh, array_keys($data))) {
                $updatedTask = $this->tasks->update(
                    $updatedTask->id,
                    $this->aiService->generateSummary($updatedTask)
                );
            }

            return $updatedTask;
        });
    }

    public function updateStatus(Task $task, string $status): Task
    {
        return $this->tasks->update($task->id, ['status' => $status]);
    }

    public function delete(Task $task): bool
    {
        return (bool) $this->tasks->delete($task->id);
    }

    public function aiSummary(Task $task): array
    {
        // Always regenerate AI summary to reflect current task data
        $aiData = $this->aiService->generateSummary($task);
        $this->tasks->update($task->id, $aiData);

        return $aiData;
    }

    private function scopeFilters(User $user, array $filters): array
    {
        if (! $user->isAdmin()) {
            $filters['assigned_to'] = $user->id;
        }

        return $filters;
    }
}
