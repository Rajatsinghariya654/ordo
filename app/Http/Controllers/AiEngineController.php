<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiLog;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskSubtask;
use App\Traits\GeocodesLocations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AiEngineController extends Controller
{
    use GeocodesLocations;

    protected function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
            ],
        ];
    }

    protected function storeAttachments(Request $request, Task $task): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('task-attachments', 'public');

            TaskAttachment::create([
                'task_id' => $task->id,
                'uploaded_by' => Auth::id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
            ]);
        }
    }

    public function parseIntent(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'prompt' => ['required', 'string', 'max:500'],
        ], $this->attachmentRules()));

        $prompt = $validated['prompt'];
        $startTime = microtime(true);

        $apiKey = SystemSetting::getValue('gemini_api_key') ?: config('services.gemini.key');
        $customPrompt = SystemSetting::getValue('ai_system_prompt');

        $today = now()->format('Y-m-d H:i');

        $systemPrompt = $customPrompt ?: <<<EOT
You are a task-parsing assistant. Convert the user's single-line input into a structured task.
Today's date and time is: {$today}

Return ONLY valid JSON (no markdown, no code fences, no explanation) in exactly this shape:
{
  "title": "short task title",
  "description": "a slightly expanded description, or empty string if nothing extra to add",
  "priority": "low" | "medium" | "high",
  "due_date": "YYYY-MM-DD HH:MM" or null if no date/time was mentioned,
  "location_name": "a specific, searchable place name and city (e.g. 'Amber Fort, Jaipur') if a location is mentioned, otherwise null",
  "subtasks": ["subtask 1", "subtask 2"] (0 to 5 short subtask strings, only if the task is complex enough to warrant breaking down; otherwise an empty array)
}

Infer priority from urgency words (urgent, ASAP, important => high; whenever, no rush => low; default => medium).
Infer due_date from relative time expressions relative to today's date/time above.
If a place, landmark, address, or city is mentioned, extract it into location_name as a specific searchable name.
User input: "{$prompt}"
EOT;

        try {
            $response = Http::timeout(20)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . config('services.gemini.model') . ':generateContent?key=' . $apiKey,
                [
                    'contents' => [
                        ['parts' => [['text' => $systemPrompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                    ],
                ]
            );

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                AiLog::create([
                    'user_id' => Auth::id(),
                    'prompt_text' => $prompt,
                    'ai_response_json' => json_encode($response->json()),
                    'tokens_used' => 0,
                    'execution_time_ms' => $executionTime,
                    'status' => 'failed',
                ]);

                return response()->json(['success' => false, 'message' => 'AI service is unavailable right now.'], 502);
            }

            $data = $response->json();
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? 0;

            $cleanText = trim(preg_replace('/^```json\s*|\s*```$/m', '', $rawText));
            $parsed = json_decode($cleanText, true);

            if (! $parsed || empty($parsed['title'])) {
                AiLog::create([
                    'user_id' => Auth::id(),
                    'prompt_text' => $prompt,
                    'ai_response_json' => $rawText,
                    'tokens_used' => $tokensUsed,
                    'execution_time_ms' => $executionTime,
                    'status' => 'failed',
                ]);

                return response()->json(['success' => false, 'message' => 'Could not understand that. Try rephrasing.'], 422);
            }

            $taskData = [
                'creator_id' => Auth::id(),
                'title' => $parsed['title'],
                'description' => $parsed['description'] ?? null,
                'priority' => in_array($parsed['priority'] ?? '', ['low', 'medium', 'high']) ? $parsed['priority'] : 'medium',
                'due_date' => $parsed['due_date'] ?? null,
                'status' => 'todo',
            ];

            if (! empty($parsed['location_name'])) {
                $geo = $this->geocodeLocation($parsed['location_name']);
                if ($geo) {
                    $taskData = array_merge($taskData, $geo);
                }
            }

            $task = Task::create($taskData);

            if (! empty($parsed['subtasks']) && is_array($parsed['subtasks'])) {
                foreach ($parsed['subtasks'] as $index => $subtaskTitle) {
                    TaskSubtask::create([
                        'task_id' => $task->id,
                        'title' => $subtaskTitle,
                        'is_completed' => false,
                        'order' => $index,
                    ]);
                }
            }

            $this->storeAttachments($request, $task);

            AiLog::create([
                'user_id' => Auth::id(),
                'prompt_text' => $prompt,
                'ai_response_json' => $cleanText,
                'tokens_used' => $tokensUsed,
                'execution_time_ms' => $executionTime,
                'status' => 'success',
            ]);

            ActivityLog::record('task_created_ai', "created task \"{$task->title}\" using AI Quick-Add");

            return response()->json([
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date,
                    'subtasks_count' => count($parsed['subtasks'] ?? []),
                ],
            ]);

        } catch (\Exception $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            AiLog::create([
                'user_id' => Auth::id(),
                'prompt_text' => $prompt,
                'ai_response_json' => $e->getMessage(),
                'tokens_used' => 0,
                'execution_time_ms' => $executionTime,
                'status' => 'error',
            ]);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function quickAddPlain(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'prompt' => ['required', 'string', 'max:2000'],
        ], $this->attachmentRules()));

        $text = trim($validated['prompt']);
        $lines = preg_split('/\r\n|\r|\n/', $text, 2);

        $title = trim($lines[0]);
        $description = isset($lines[1]) ? trim($lines[1]) : null;

        $task = Task::create([
            'creator_id' => Auth::id(),
            'title' => $title,
            'description' => $description,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $this->storeAttachments($request, $task);

        ActivityLog::record('task_created', "created task \"{$task->title}\"");

        return response()->json([
            'success' => true,
            'task' => ['id' => $task->id, 'title' => $task->title],
        ]);
    }
}