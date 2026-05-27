<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'assigned_to',
        'ai_summary',
        'ai_priority',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['assigned_to'] ?? null, fn (Builder $query, $userId) => $query->where('assigned_to', $userId))
            ->when($filters['priority'] ?? null, fn (Builder $query, $priority) => $query->where('priority', $priority))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['ai_priority'] ?? null, fn (Builder $query, $priority) => $query->where('ai_priority', $priority))
            ->when($filters['due_before'] ?? null, fn (Builder $query, $date) => $query->whereDate('due_date', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });
    }
}
