<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Task::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['sometimes', 'in:pending,in_progress,completed'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
