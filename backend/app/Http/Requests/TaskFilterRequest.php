<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'status' => ['sometimes', 'in:pending,in_progress,completed'],
            'ai_priority' => ['sometimes', 'in:low,medium,high'],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
            'due_before' => ['sometimes', 'date'],
        ];
    }
}
