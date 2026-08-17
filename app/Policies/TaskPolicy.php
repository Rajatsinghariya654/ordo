<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Koi bhi apne + apni workspace ke tasks dekh sakta hai
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Task dekh sakta hai agar: khud creator hai, assignee hai,
     * ya us workspace ka member hai, ya admin hai
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($task->creator_id === $user->id || $task->assignee_id === $user->id) {
            return true;
        }

        if ($task->workspace_id && $user->workspaces->contains($task->workspace_id)) {
            return true;
        }

        return false;
    }

    /**
     * Koi bhi active user task bana sakta hai
     */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Update sirf: creator, assignee, workspace manager/owner, ya admin
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($task->creator_id === $user->id || $task->assignee_id === $user->id) {
            return true;
        }

        if ($task->workspace_id) {
            $membership = $user->workspaces->firstWhere('id', $task->workspace_id);
            if ($membership && in_array($membership->pivot->role, ['owner', 'manager'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete sirf: creator, workspace owner, ya admin (assignee nahi)
     */
    public function delete(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($task->creator_id === $user->id) {
            return true;
        }

        if ($task->workspace_id) {
            $membership = $user->workspaces->firstWhere('id', $task->workspace_id);
            if ($membership && $membership->pivot->role === 'owner') {
                return true;
            }
        }

        return false;
    }
}