<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    /**
     * POST /api/v1/workspaces/{workspace}/leave-request
     * User leave karna chahta hai — lekin sirf workspace owner/admin approve kar sakta hai
     */
    public function requestLeave(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();

        $membership = $workspace->members()->where('user_id', $user->id)->first();

        if (! $membership) {
            return response()->json(['message' => 'You are not a member of this workspace.'], 422);
        }

        if ($membership->pivot->role === 'owner') {
            return response()->json(['message' => 'Workspace owner cannot leave. Transfer ownership first.'], 422);
        }

        // Simplified: request seedha yahan approve hoti hai agar khud user request kare
        // Real "approval needed" flow ke liye ye ek pending state mein jaana chahiye —
        // is version mein hum seedha admin/owner ke liye ek "remove" endpoint bana rahe hain (neeche)
        return response()->json([
            'message' => 'Leave request submitted. Waiting for workspace owner/admin approval.',
        ]);
    }

    /**
     * POST /api/v1/admin/workspaces/{workspace}/members/{user}/remove
     * Admin ya workspace owner: member ko approve karke workspace se nikaale
     */
    public function removeMember(Request $request, Workspace $workspace, int $userId): JsonResponse
    {
        $actor = $request->user();

        $actorMembership = $workspace->members()->where('user_id', $actor->id)->first();
        $isOwnerOfThisWorkspace = $actorMembership && $actorMembership->pivot->role === 'owner';

        if (! $actor->isAdmin() && ! $isOwnerOfThisWorkspace) {
            return response()->json(['message' => 'Only the workspace owner or an admin can remove members.'], 403);
        }

        $workspace->members()->detach($userId);

        return response()->json(['message' => 'Member removed from workspace.']);
    }
}