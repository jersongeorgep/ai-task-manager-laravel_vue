<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AIService
{
    public function generateSummary(Task $task): array
    {
        try {
            if (config('services.openai.key')) {
                return $this->generateWithOpenAI($task);
            }
            if (config('services.gemini.key')) {
                return $this->generateWithGemini($task);
            }
        } catch (Throwable $exception) {
            Log::warning('AI summary generation failed; using fallback.', [
                'task_id' => $task->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->generateFallback($task);
    }

    private function generateWithOpenAI(Task $task): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(20)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You summarize task-management items and classify urgency. Return only valid JSON with ai_summary and ai_priority keys. ai_priority must be low, medium, or high.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->prompt($task),
                    ],
                ],
                'temperature' => 0.2,
            ])
            ->throw()
            ->json();

        $content = $response['choices'][0]['message']['content'] ?? '';
        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return $this->normalizeResponse($data);
    }

    private function generateWithGemini(Task $task): array
    {
        $response = Http::timeout(20)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/' . config('services.gemini.model') . ':generateContent', [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $this->prompt($task),
                            ],
                        ],
                    ],
                ],
                'systemInstruction' => [
                    'parts' => [
                        [
                            'text' => 'You summarize task-management items and classify urgency. Return only valid JSON with ai_summary and ai_priority keys. ai_priority must be low, medium, or high.',
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                ],
            ], [
                'key' => config('services.gemini.key'),
            ])
            ->throw()
            ->json();

        $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return $this->normalizeResponse($data);
    }

    private function generateFallback(Task $task): array
    {
        $description = trim((string) $task->description);
        $summarySource = $description !== '' ? $description : $task->title;
        $summary = str($summarySource)->squish()->limit(160)->toString();

        return [
            'ai_summary' => $summary,
            'ai_priority' => $this->fallbackPriority($task),
        ];
    }

    private function prompt(Task $task): string
    {
        return implode("\n", [
            "Task title: {$task->title}",
            "Description: {$task->description}",
            "User priority: {$task->priority}",
            "Current status: {$task->status}",
            'Return a concise one-sentence summary and inferred priority.',
        ]);
    }

    private function normalizeResponse(array $data): array
    {
        $priority = in_array($data['ai_priority'] ?? null, ['low', 'medium', 'high'], true)
            ? $data['ai_priority']
            : 'medium';

        return [
            'ai_summary' => str((string) ($data['ai_summary'] ?? 'Summary unavailable.'))->squish()->limit(500)->toString(),
            'ai_priority' => $priority,
        ];
    }

    private function fallbackPriority(Task $task): string
    {
        if ($task->priority === 'high') {
            return 'high';
        }

        if ($task->due_date && $task->due_date->isPast() && $task->status !== 'completed') {
            return 'high';
        }

        if ($task->due_date && $task->due_date->diffInDays(now(), false) >= -2) {
            return 'medium';
        }

        return $task->priority ?: 'medium';
    }
}
