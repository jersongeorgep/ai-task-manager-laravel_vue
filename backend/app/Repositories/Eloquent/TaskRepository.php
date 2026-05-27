<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{
    public function all(array $filters = [])
    {
        return $this->query($filters)
            ->latest()
            ->paginate(10);
    }

    public function counts(array $filters = []): array
    {
        $query = $this->query($filters);

        return [
            'total_tasks' => (clone $query)->count(),
            'completed_tasks' => (clone $query)->where('status', 'completed')->count(),
            'pending_tasks' => (clone $query)->where('status', 'pending')->count(),
            'high_priority_tasks' => (clone $query)->where(function ($query) {
                $query->where('priority', 'high')
                    ->orWhere('ai_priority', 'high');
            })->count(),
        ];
    }

    public function find(int $id)
    {
        return Task::with('user')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Task::create($data);
    }

    public function update(int $id, array $data)
    {
        $task = $this->find($id);

        $task->update($data);

        return $task;
    }

    public function delete(int $id)
    {
        return $this->find($id)->delete();
    }

    private function query(array $filters = [])
    {
        return Task::query()
            ->with('user')
            ->filter($filters);
    }
}
