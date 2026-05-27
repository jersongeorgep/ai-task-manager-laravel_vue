<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('task')
            ? $this->user()?->can('updateStatus', $this->route('task')) ?? false
            : false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,in_progress,completed'],
        ];
    }
}
