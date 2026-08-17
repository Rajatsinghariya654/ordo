<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function board(Request $request): View
    {
        $user = Auth::user();
        $selectedWorkspaceId = $request->query('workspace_id');

        // Get IDs of all workspaces user belongs to
        $userWorkspaceIds = $user->workspaces()->pluck('workspaces.id');

        $columns = [];

        foreach (['todo', 'in_progress', 'review', 'completed'] as $status) {
            $query = Task::where('status', $status);

            if ($selectedWorkspaceId === 'personal') {
                $query->whereNull('workspace_id')
                    ->where(function ($q) use ($user) {
                        $q->where('creator_id', $user->id)->orWhere('assignee_id', $user->id);
                    });
            } elseif ($selectedWorkspaceId && is_numeric($selectedWorkspaceId)) {
                $query->where('workspace_id', $selectedWorkspaceId);
            } else {
                $query->where(function ($q) use ($user, $userWorkspaceIds) {
                    $q->where('creator_id', $user->id)
                        ->orWhere('assignee_id', $user->id)
                        ->orWhereIn('workspace_id', $userWorkspaceIds);
                });
            }

            $columns[$status] = $query->with(['assignee', 'attachments', 'group', 'workspace'])
                ->orderBy('due_date')
                ->get();
        }

        $groupsQuery = \App\Models\TaskGroup::query();

        if ($selectedWorkspaceId === 'personal') {
            $groupsQuery->where('creator_id', $user->id)
                ->where(function ($q) use ($user) {
                    $q->whereDoesntHave('tasks')
                        ->orWhereHas('tasks', function ($sq) use ($user) {
                            $sq->whereNull('workspace_id')
                                ->where(function ($ssq) use ($user) {
                                    $ssq->where('creator_id', $user->id)->orWhere('assignee_id', $user->id);
                                });
                        });
                })
                ->withCount(['tasks' => function ($q) use ($user) {
                    $q->whereNull('workspace_id')
                        ->where(function ($sq) use ($user) {
                            $sq->where('creator_id', $user->id)->orWhere('assignee_id', $user->id);
                        });
                }]);
        } elseif ($selectedWorkspaceId && is_numeric($selectedWorkspaceId)) {
            $groupsQuery->whereHas('tasks', function ($q) use ($selectedWorkspaceId) {
                    $q->where('workspace_id', $selectedWorkspaceId);
                })
                ->withCount(['tasks' => function ($q) use ($selectedWorkspaceId) {
                    $q->where('workspace_id', $selectedWorkspaceId);
                }]);
        } else {
            $groupsQuery->where(function ($q) use ($user, $userWorkspaceIds) {
                    $q->where('creator_id', $user->id)
                        ->orWhereHas('tasks', function ($sq) use ($userWorkspaceIds) {
                            $sq->whereIn('workspace_id', $userWorkspaceIds);
                        });
                })
                ->withCount(['tasks' => function ($q) use ($user, $userWorkspaceIds) {
                    $q->where(function ($sq) use ($user, $userWorkspaceIds) {
                        $sq->where('creator_id', $user->id)
                            ->orWhere('assignee_id', $user->id)
                            ->orWhereIn('workspace_id', $userWorkspaceIds);
                    });
                }]);
        }

        $groups = $groupsQuery->orderBy('title')->get();

        $workspaces = $user->workspaces;

        return view('tasks.board', compact('columns', 'groups', 'workspaces', 'selectedWorkspaceId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['nullable', 'exists:workspaces,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:255'],
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
                return back()->with('error', 'You are not a member of the selected workspace.');
            }
        }

        $taskData = collect($validated)->except('attachments')->toArray();
        $taskData['creator_id'] = Auth::id();
        $taskData['status'] = 'todo';

        $task = Task::create($taskData);

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

        ActivityLog::record('task_created', "created task \"{$task->title}\"");

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $user = Auth::user();

        // Permission check for workspace tasks vs personal tasks
        if ($task->workspace_id) {
            $workspace = $task->workspace;
            if ($workspace) {
                $role = $workspace->userRole($user);
                if (! in_array($role, ['owner', 'manager']) && $task->creator_id !== $user->id && ! $user->isAdmin()) {
                    return back()->with('error', 'Standard members can only edit their own created tasks.');
                }
            }
        } elseif ($task->creator_id !== $user->id && $task->assignee_id !== $user->id && ! $user->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $remainingSlots = 3 - $task->attachments()->count();

        $validated = $request->validate([
            'workspace_id' => ['nullable', 'exists:workspaces,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:' . max($remainingSlots, 0)],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
            ],
        ]);

        $taskData = collect($validated)->except('attachments')->toArray();
        $task->update($taskData);

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

        ActivityLog::record('task_updated', "edited task \"{$task->title}\"");

        return back()->with('success', 'Task updated successfully.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,review,completed'],
        ]);

        $newStatus = $validated['status'];

        if ($newStatus === 'in_progress' && ! $task->started_at) {
            $validated['started_at'] = now();
        }

        if ($newStatus === 'completed') {
            $validated['completed_at'] = now();
        } elseif ($task->status === 'completed' && $newStatus !== 'completed') {
            $validated['completed_at'] = null;
        }

        $task->update($validated);

        $statusLabel = str_replace('_', ' ', $newStatus);
        $locationSuffix = ($newStatus === 'completed' && $task->location_name)
            ? " at {$task->location_name}"
            : '';

        ActivityLog::record('task_status_updated', "moved task \"{$task->title}\" to {$statusLabel}{$locationSuffix}");

        return back()->with('success', 'Task status updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $user = Auth::user();

        // Permission check for workspace tasks vs personal tasks
        if ($task->workspace_id) {
            $workspace = $task->workspace;
            if ($workspace) {
                $role = $workspace->userRole($user);
                if (! in_array($role, ['owner', 'manager']) && $task->creator_id !== $user->id && ! $user->isAdmin()) {
                    return back()->with('error', 'Standard members can only delete their own created tasks.');
                }
            }
        } elseif ($task->creator_id !== $user->id && $task->assignee_id !== $user->id && ! $user->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $title = $task->title;

        foreach ($task->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $task->delete();

        ActivityLog::record('task_deleted', "deleted task \"{$title}\"");

        return back()->with('success', 'Task deleted.');
    }

    public function nearby(Request $request): View
    {
        $user = Auth::user();
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $radius = (float) $request->query('radius', 5);

        $tasks = collect();

        if ($lat && $lng) {
            $tasks = Task::selectRaw(
                "tasks.*, ( 6371 * acos( cos( radians(?) ) * cos( radians(latitude) ) * cos( radians(longitude) - radians(?) ) + sin( radians(?) ) * sin( radians(latitude) ) ) ) AS distance",
                [$lat, $lng, $lat]
            )
                ->where(function ($query) use ($user) {
                    $query->where('creator_id', $user->id)->orWhere('assignee_id', $user->id);
                })
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->havingRaw('distance <= ?', [$radius])
                ->orderBy('distance')
                ->get();
        }

        return view('tasks.nearby', compact('tasks', 'lat', 'lng', 'radius'));
    }

    public function deleteAttachment(TaskAttachment $attachment): RedirectResponse
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }
}
