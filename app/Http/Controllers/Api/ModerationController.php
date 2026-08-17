<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModerationLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    /**
     * POST /api/v1/admin/moderation/{user}/remove-from-team
     * Admin kisi user ko specific workspace se nikaal de
     */
    public function removeFromTeam(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $workspace = Workspace::findOrFail($validated['workspace_id']);
        $workspace->members()->detach($user->id);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => $request->user()->id,
            'workspace_id' => $workspace->id,
            'action_type' => 'removed_from_team',
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'message' => "{$user->name} has been removed from {$workspace->name}.",
        ]);
    }

    /**
     * POST /api/v1/admin/moderation/{user}/suspend
     */
    public function suspend(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update(['is_suspended' => true]);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => $request->user()->id,
            'action_type' => 'suspended',
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => "{$user->name}'s account has been suspended.",
        ]);
    }

    /**
     * POST /api/v1/admin/moderation/{user}/block
     */
    public function block(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update(['is_blocked' => true]);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => $request->user()->id,
            'action_type' => 'blocked',
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => "{$user->name}'s account has been permanently blocked.",
        ]);
    }

    /**
     * POST /api/v1/admin/moderation/{user}/reactivate
     */
    public function reactivate(Request $request, User $user): JsonResponse
    {
        $user->update(['is_suspended' => false, 'is_blocked' => false]);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => $request->user()->id,
            'action_type' => 'reactivated',
            'reason' => $request->input('reason'),
        ]);

        return response()->json([
            'message' => "{$user->name}'s account has been reactivated.",
        ]);
    }

    /**
     * GET /api/v1/admin/moderation/logs
     * Audit trail — saari moderation actions ki history
     */
    public function logs(): JsonResponse
    {
        $logs = ModerationLog::with(['targetUser', 'admin', 'workspace'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($logs);
    }
}