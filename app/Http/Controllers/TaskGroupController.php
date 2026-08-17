<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiLog;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskGroup;
use App\Traits\GeocodesLocations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TaskGroupController extends Controller
{
    use GeocodesLocations;

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $group = TaskGroup::create([
            'creator_id' => Auth::id(),
            'title' => $validated['title'],
            'color' => $validated['color'] ?? '#7c6ff0',
        ]);

        ActivityLog::record('group_created', "created group \"{$group->title}\"");

        return back()->with('success', 'Group created successfully.')->with('new_group_id', $group->id);
    }

    public function destroy(TaskGroup $taskGroup): RedirectResponse
    {
        $title = $taskGroup->title;
        $taskGroup->delete(); // tasks stay, group_id becomes null (nullOnDelete)

        ActivityLog::record('group_deleted', "deleted group \"{$title}\"");

        return back()->with('success', 'Group deleted. Its tasks were kept.');
    }

    public function generateWithAi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_title' => ['required', 'string', 'max:255'],
            'plan_text' => ['required', 'string', 'max:3000'],
            'workspace_id' => ['nullable', 'exists:workspaces,id'],
            'is_recurring' => ['nullable'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
            ],
        ]);

        if (! empty($validated['workspace_id'])) {
            $user = Auth::user();
            if (! $user->isInWorkspace($validated['workspace_id']) && ! $user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'You are not a member of the selected workspace.'], 403);
            }
        }

        $isRecurring = $request->boolean('is_recurring');
        $startTime = microtime(true);

        $group = TaskGroup::create([
            'creator_id' => Auth::id(),
            'title' => $validated['group_title'],
            'is_recurring' => $isRecurring,
        ]);

        $apiKey = SystemSetting::getValue('gemini_api_key') ?: config('services.gemini.key');

        $today = now();
        $referenceDates = [];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayName) {
            $referenceDates[$dayName] = $today->copy()->next($dayName)->format('Y-m-d');
            if ($today->format('l') === $dayName) {
                $referenceDates[$dayName] = $today->format('Y-m-d');
            }
        }

        $prompt = <<<EOT
You are a planning assistant. The user will describe a recurring or multi-part plan in free text (e.g. a gym schedule, a travel itinerary, a wedding checklist).
Break it down into individual tasks.

Today's date is {$today->format('Y-m-d')} ({$today->format('l')}).
Use these exact upcoming calendar dates when a weekday name is mentioned (use the date, do not guess):
{$this->formatReferenceDates($referenceDates)}

Return ONLY valid JSON (no markdown, no explanation), an array of task objects, in this exact shape:
[
  {
    "title": "short task title",
    "description": "any extra detail",
    "due_date": "YYYY-MM-DD HH:MM" or null,
    "priority": "low" | "medium" | "high",
    "location_name": "a specific, searchable place name and city (e.g. 'Amber Fort, Jaipur') if a place is mentioned for this specific item, otherwise null"
  }
]

Create one task per distinct item/day/step/place mentioned. If times are given (e.g. 6pm to 8pm), use the start time for due_date and mention the full time range in the description.
If the plan mentions specific places (landmarks, addresses, venues, cities), extract each into that item's location_name so it can be mapped.
User's plan:
"{$validated['plan_text']}"
EOT;

        try {
            $response = Http::timeout(30)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . config('services.gemini.model') . ':generateContent?key=' . $apiKey,
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.3],
                ]
            );

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                AiLog::create([
                    'user_id' => Auth::id(),
                    'prompt_text' => $validated['plan_text'],
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
            $parsedTasks = json_decode($cleanText, true);

            if (! is_array($parsedTasks) || empty($parsedTasks)) {
                AiLog::create([
                    'user_id' => Auth::id(),
                    'prompt_text' => $validated['plan_text'],
                    'ai_response_json' => $rawText,
                    'tokens_used' => $tokensUsed,
                    'execution_time_ms' => $executionTime,
                    'status' => 'failed',
                ]);

                return response()->json(['success' => false, 'message' => 'Could not understand that plan. Try adding more detail.'], 422);
            }

            $createdCount = 0;
            $template = [];

            foreach ($parsedTasks as $item) {
                if (empty($item['title'])) {
                    continue;
                }

                $taskData = [
                    'creator_id' => Auth::id(),
                    'workspace_id' => $validated['workspace_id'] ?? null,
                    'group_id' => $group->id,
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'priority' => in_array($item['priority'] ?? '', ['low', 'medium', 'high']) ? $item['priority'] : 'medium',
                    'due_date' => $item['due_date'] ?? null,
                    'status' => 'todo',
                ];

                if (! empty($item['location_name'])) {
                    $geo = $this->geocodeLocation($item['location_name']);
                    if ($geo) {
                        $taskData = array_merge($taskData, $geo);
                    }
                }

                $task = Task::create($taskData);
                $createdCount++;

                if ($isRecurring && !empty($item['due_date'])) {
                    $dueDate = \Carbon\Carbon::parse($item['due_date']);
                    $template[] = [
                        'title' => $item['title'],
                        'description' => $item['description'] ?? null,
                        'priority' => $task->priority,
                        'weekday' => $dueDate->dayOfWeekIso,
                        'time' => $dueDate->format('H:i'),
                    ];
                }

                if ($request->hasFile('attachments')) {
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
            }

            if ($isRecurring && !empty($template)) {
                $group->update(['recurrence_template' => $template]);
            }

            AiLog::create([
                'user_id' => Auth::id(),
                'prompt_text' => $validated['plan_text'],
                'ai_response_json' => $cleanText,
                'tokens_used' => $tokensUsed,
                'execution_time_ms' => $executionTime,
                'status' => 'success',
            ]);

            ActivityLog::record('group_ai_generated', "generated {$createdCount} tasks in group \"{$group->title}\" using AI");

            return response()->json([
                'success' => true,
                'group_id' => $group->id,
                'group_title' => $group->title,
                'tasks_created' => $createdCount,
            ]);

        } catch (\Exception $e) {
            $group->delete();

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            AiLog::create([
                'user_id' => Auth::id(),
                'prompt_text' => $validated['plan_text'],
                'ai_response_json' => $e->getMessage(),
                'tokens_used' => 0,
                'execution_time_ms' => $executionTime,
                'status' => 'error',
            ]);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function regenerateNextWeek(TaskGroup $taskGroup): RedirectResponse
    {
        if (! $taskGroup->is_recurring || empty($taskGroup->recurrence_template)) {
            return back()->withErrors(['group' => 'This group has no recurring schedule.']);
        }

        $nextWeekStart = now()->addWeek()->startOfWeek(\Carbon\Carbon::MONDAY);
        $createdCount = 0;

        foreach ($taskGroup->recurrence_template as $item) {
            $dueDate = $nextWeekStart->copy()->addDays($item['weekday'] - 1)->setTimeFromTimeString($item['time']);

            $alreadyExists = Task::where('group_id', $taskGroup->id)
                ->where('title', $item['title'])
                ->whereDate('due_date', $dueDate->toDateString())
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            Task::create([
                'creator_id' => Auth::id(),
                'group_id' => $taskGroup->id,
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
                'priority' => $item['priority'] ?? 'medium',
                'due_date' => $dueDate,
                'status' => 'todo',
            ]);
            $createdCount++;
        }

        ActivityLog::record('group_regenerated', "generated {$createdCount} tasks for next week in group \"{$taskGroup->title}\"");

        return back()->with('success', "Generated {$createdCount} tasks for next week in \"{$taskGroup->title}\".");
    }

    private function formatReferenceDates(array $dates): string
    {
        $lines = [];
        foreach ($dates as $day => $date) {
            $lines[] = "{$day} = {$date}";
        }
        return implode("\n", $lines);
    }
}