<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('task')
            ? $this->user()?->can('update', $this->route('task')) ?? false
            : false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'status' => ['sometimes', 'in:pending,in_progress,completed'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
