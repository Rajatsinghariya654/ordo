<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * GET /api/v1/tasks
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::with(['creator', 'assignee', 'subtasks'])
            ->orderBy('created_at', 'desc');

        if ($request->has('workspace_id')) {
            $query->where('workspace_id', $request->workspace_id);
        } else {
            $query->where('creator_id', $request->user()->id);
        }

        $tasks = $query->paginate(10);

        return TaskResource::collection($tasks);
    }

    /**
     * POST /api/v1/tasks
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validated();
        $data['creator_id'] = $request->user()->id;

        $task = Task::create($data);
        $task->refresh();
        $task->load(['creator', 'assignee', 'subtasks']);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/tasks/{task}
     */
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        $task->load(['creator', 'assignee', 'subtasks']);

        return new TaskResource($task);
    }

    /**
     * PUT/PATCH /api/v1/tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());
        $task->refresh();
        $task->load(['creator', 'assignee', 'subtasks']);

        return new TaskResource($task);
    }

    /**
     * DELETE /api/v1/tasks/{task}
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(null, 204);
    }
}