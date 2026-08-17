<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceJoinRequest;
use App\Models\WorkspaceLeaveRequest;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    /**
     * Display My Workspaces dashboard page.
     */
    public function index()
    {
        $user = auth()->user();

        // Workspaces the user is member of (with role pivot)
        $joinedWorkspaces = $user->workspaces()->with(['owner'])->withCount('members')->get();

        // For business accounts: owned workspaces
        $ownedWorkspaces = $user->isBusiness()
            ? $user->ownedWorkspaces()->withCount('members')->get()
            : collect();

        // User's own pending join requests
        $myJoinRequests = $user->workspaceJoinRequests()
            ->with(['workspace.owner'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        // User's own pending leave requests
        $myLeaveRequests = $user->workspaceLeaveRequests()
            ->with(['workspace'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        // For Business Owners & Managers: pending incoming join & leave requests across their managed workspaces
        $managedWorkspaceIds = $user->workspaces()
            ->wherePivotIn('role', ['owner', 'manager'])
            ->pluck('workspaces.id')
            ->merge($ownedWorkspaces->pluck('id'))
            ->unique();

        $incomingJoinRequests = WorkspaceJoinRequest::whereIn('workspace_id', $managedWorkspaceIds)
            ->where('status', 'pending')
            ->with(['user', 'workspace'])
            ->latest()
            ->get();

        $incomingLeaveRequests = WorkspaceLeaveRequest::whereIn('workspace_id', $managedWorkspaceIds)
            ->where('status', 'pending')
            ->with(['user', 'workspace'])
            ->latest()
            ->get();

        return view('workspaces.index', compact(
            'joinedWorkspaces',
            'ownedWorkspaces',
            'myJoinRequests',
            'myLeaveRequests',
            'incomingJoinRequests',
            'incomingLeaveRequests'
        ));
    }

    /**
     * Business user creates a new Workspace.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user->isBusiness() && ! $user->isAdmin()) {
            return back()->with('error', 'Only Business Accounts can create new workspaces. Personal users can join workspaces via invite code.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => $request->name,
        ]);

        // Attach owner to workspace_user with 'owner' role
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        ActivityLog::record('workspace_created', "Created workspace: {$workspace->name}");

        return back()->with('success', "Workspace '{$workspace->name}' created successfully!");
    }

    /**
     * Personal/Any user joins workspace by entering invite code.
     */
    public function joinByCode(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string',
        ]);

        $code = strtoupper(trim($request->invite_code));
        $workspace = Workspace::where('invite_code', $code)->first();

        if (! $workspace) {
            return back()->with('error', 'Invalid invite code. Please check and try again.');
        }

        $user = auth()->user();

        // Check if already a member
        if ($workspace->members()->where('users.id', $user->id)->exists()) {
            return back()->with('error', "You are already a member of '{$workspace->name}'.");
        }

        // Check if pending request exists
        $existingRequest = WorkspaceJoinRequest::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->with('error', "You already have a pending join request for '{$workspace->name}'.");
        }

        // Create pending join request
        WorkspaceJoinRequest::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        ActivityLog::record('workspace_join_request_sent', "Requested to join workspace: {$workspace->name}");

        return back()->with('success', "Join request for '{$workspace->name}' submitted successfully! Waiting for Owner/Manager approval.");
    }

    /**
     * Approve a join request.
     */
    public function approveJoinRequest(WorkspaceJoinRequest $joinRequest)
    {
        $user = auth()->user();
        $workspace = $joinRequest->workspace;

        if (! $workspace->canManageMembers($user) && ! $user->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }

        // Attach user to workspace with default role 'member'
        $workspace->members()->syncWithoutDetaching([
            $joinRequest->user_id => ['role' => 'member'],
        ]);

        $joinRequest->update([
            'status' => 'approved',
            'reviewed_by' => $user->id,
        ]);

        ActivityLog::record('workspace_join_approved', "Approved {$joinRequest->user->name} to join workspace '{$workspace->name}'");

        return back()->with('success', "Join request approved for {$joinRequest->user->name}.");
    }

    /**
     * Reject a join request.
     */
    public function rejectJoinRequest(WorkspaceJoinRequest $joinRequest)
    {
        $user = auth()->user();
        $workspace = $joinRequest->workspace;

        if (! $workspace->canManageMembers($user) && ! $user->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $joinRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $user->id,
        ]);

        ActivityLog::record('workspace_join_rejected', "Rejected {$joinRequest->user->name}'s request for workspace '{$workspace->name}'");

        return back()->with('success', "Join request rejected.");
    }

    /**
     * Submit leave application for a workspace.
     */
    public function applyLeave(Request $request, Workspace $workspace)
    {
        $user = auth()->user();

        if (! $user->isInWorkspace($workspace->id)) {
            return back()->with('error', 'You are not a member of this workspace.');
        }

        if ($workspace->isOwner($user)) {
            return back()->with('error', 'Workspace Owners cannot leave their workspace. Transfer ownership or delete the workspace.');
        }

        $request->validate([
            'reason' => 'required|string|min:2|max:1000',
            'leave_date' => 'required|date|after_or_equal:today',
        ]);

        // Check for existing pending leave request
        $existingLeave = WorkspaceLeaveRequest::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingLeave) {
            return back()->with('error', 'You already have a pending leave request for this workspace.');
        }

        WorkspaceLeaveRequest::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'reason' => $request->reason,
            'leave_date' => $request->leave_date,
            'status' => 'pending',
        ]);

        ActivityLog::record('workspace_leave_requested', "Applied to leave workspace '{$workspace->name}' on {$request->leave_date}");

        return back()->with('success', 'Leave request submitted successfully. Awaiting approval from Owner/Manager.');
    }

    /**
     * Approve leave request.
     */
    public function approveLeaveRequest(WorkspaceLeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $workspace = $leaveRequest->workspace;

        if (! $workspace->canManageMembers($user) && ! $user->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }

        // Remove user from workspace members
        $workspace->members()->detach($leaveRequest->user_id);

        $leaveRequest->update([
            'status' => 'approved',
            'reviewed_by' => $user->id,
        ]);

        ActivityLog::record('workspace_leave_approved', "Approved leave application for {$leaveRequest->user->name} in workspace '{$workspace->name}'");

        return back()->with('success', "Leave request approved. {$leaveRequest->user->name} has been removed from the workspace.");
    }

    /**
     * Reject leave request.
     */
    public function rejectLeaveRequest(WorkspaceLeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $workspace = $leaveRequest->workspace;

        if (! $workspace->canManageMembers($user) && ! $user->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $user->id,
        ]);

        ActivityLog::record('workspace_leave_rejected', "Rejected leave application for {$leaveRequest->user->name} in workspace '{$workspace->name}'");

        return back()->with('success', 'Leave request rejected.');
    }

    /**
     * Show Workspace details & member management page.
     */
    public function show(Workspace $workspace)
    {
        $user = auth()->user();

        if (! $user->isInWorkspace($workspace->id) && ! $user->isAdmin()) {
            abort(403, 'You do not have access to this workspace.');
        }

        $workspace->load([
            'owner',
            'members' => function ($query) {
                $query->orderBy('workspace_user.role');
            },
            'pendingJoinRequests.user',
            'pendingLeaveRequests.user',
        ]);

        $currentUserRole = $workspace->userRole($user);

        // Fetch workspace tasks grouped by status
        $workspaceColumns = [];
        foreach (['todo', 'in_progress', 'review', 'completed'] as $status) {
            $workspaceColumns[$status] = \App\Models\Task::where('workspace_id', $workspace->id)
                ->where('status', $status)
                ->with(['assignee', 'creator', 'attachments', 'group'])
                ->orderBy('due_date')
                ->get();
        }

        $taskGroups = \App\Models\TaskGroup::where('creator_id', $user->id)->get();

        return view('workspaces.show', compact('workspace', 'currentUserRole', 'workspaceColumns', 'taskGroups'));
    }

    /**
     * Update member role (Owner only).
     */
    public function updateMemberRole(Request $request, Workspace $workspace, User $user)
    {
        $currentUser = auth()->user();

        if (! $workspace->isOwner($currentUser) && ! $currentUser->isAdmin()) {
            return back()->with('error', 'Only the Workspace Owner can change member roles.');
        }

        if ($workspace->isOwner($user)) {
            return back()->with('error', 'Cannot change role of Workspace Owner.');
        }

        $request->validate([
            'role' => 'required|in:manager,member',
        ]);

        $workspace->members()->updateExistingPivot($user->id, [
            'role' => $request->role,
        ]);

        ActivityLog::record('workspace_role_changed', "Changed {$user->name}'s role to {$request->role} in workspace '{$workspace->name}'");

        return back()->with('success', "Updated {$user->name}'s role to " . ucfirst($request->role) . ".");
    }

    /**
     * Remove member from workspace (Owner or Manager).
     */
    public function removeMember(Workspace $workspace, User $user)
    {
        $currentUser = auth()->user();

        if (! $workspace->canManageMembers($currentUser) && ! $currentUser->isAdmin()) {
            return back()->with('error', 'Unauthorized action.');
        }

        if ($workspace->isOwner($user)) {
            return back()->with('error', 'Workspace Owner cannot be removed.');
        }

        // Managers cannot remove other managers or owners
        if ($workspace->isManager($currentUser) && ($workspace->isManager($user) || $workspace->isOwner($user))) {
            return back()->with('error', 'Managers can only remove standard members.');
        }

        $workspace->members()->detach($user->id);

        ActivityLog::record('workspace_member_removed', "Removed {$user->name} from workspace '{$workspace->name}'");

        return back()->with('success', "{$user->name} has been removed from workspace '{$workspace->name}'.");
    }
}
