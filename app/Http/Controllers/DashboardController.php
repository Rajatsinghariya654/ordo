<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AdminAccessRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $tasks = Task::where('creator_id', $user->id)
            ->orWhere('assignee_id', $user->id)
            ->with(['creator', 'assignee', 'subtasks'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $stats = [
            'total' => Task::where('creator_id', $user->id)->count(),
            'completed' => Task::where('creator_id', $user->id)->where('status', 'completed')->count(),
            'in_progress' => Task::where('creator_id', $user->id)->where('status', 'in_progress')->count(),
            'todo' => Task::where('creator_id', $user->id)->where('status', 'todo')->count(),
        ];

        $overdueCount = Task::where(function ($query) use ($user) {
            $query->where('creator_id', $user->id)->orWhere('assignee_id', $user->id);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        $managedWorkspaceIds = $user->workspaces()
            ->wherePivotIn('role', ['owner', 'manager'])
            ->pluck('workspaces.id')
            ->merge($user->ownedWorkspaces()->pluck('id'))
            ->unique();

        $incomingJoinRequests = \App\Models\WorkspaceJoinRequest::whereIn('workspace_id', $managedWorkspaceIds)
            ->where('status', 'pending')
            ->with(['user', 'workspace'])
            ->latest()
            ->get();

        $incomingLeaveRequests = \App\Models\WorkspaceLeaveRequest::whereIn('workspace_id', $managedWorkspaceIds)
            ->where('status', 'pending')
            ->with(['user', 'workspace'])
            ->latest()
            ->get();

        return view('dashboard', compact('tasks', 'stats', 'overdueCount', 'incomingJoinRequests', 'incomingLeaveRequests'));
    }

    public function requestAdminAccess(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        AdminAccessRequest::create([
            'user_id' => Auth::id(),
            'reason' => $validated['reason'],
        ]);

        ActivityLog::record('admin_access_requested', 'requested admin access');

        return back()->with('success', 'Your admin access request has been submitted for review.');
    }

    public function myActivity(): View
    {
        $logs = ActivityLog::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('my-activity', compact('logs'));
    }

    public function requestAccountSwitch(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $targetType = $user->account_type === 'personal' ? 'business' : 'personal';

        $existingPending = \App\Models\AccountSwitchRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return back()->withErrors(['switch' => 'You already have a pending switch request.']);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        \App\Models\AccountSwitchRequest::create([
            'user_id' => $user->id,
            'requested_type' => $targetType,
            'status' => 'pending',
            'reason' => $validated['reason'],
        ]);

        ActivityLog::record('account_switch_requested', "requested switch to {$targetType} account");

        return back()->with('success', 'Your account switch request has been submitted for review.');
    }

    public function myAnalytics(Request $request): View
    {
        $user = Auth::user();
        $months = (int) $request->query('months', 6);
        $months = in_array($months, [3, 6, 12]) ? $months : 6;

        $totalTasks = Task::where('creator_id', $user->id)->count();
        $completedTasks = Task::where('creator_id', $user->id)->where('status', 'completed')->count();
        $pendingTasks = $totalTasks - $completedTasks;
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        $monthlyCompleted = Task::where('creator_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->selectRaw('YEAR(completed_at) as y, MONTH(completed_at) as m, COUNT(*) as count')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn($row) => $row->y . '-' . $row->m);

        $chartLabels = [];
        $chartData = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->year . '-' . $date->month;
            $chartLabels[] = $date->format('M Y');
            $chartData[] = $monthlyCompleted->get($key)->count ?? 0;
        }

        return view('my-analytics', compact(
            'totalTasks',
            'completedTasks',
            'pendingTasks',
            'completionRate',
            'chartLabels',
            'chartData',
            'months'
        ));
    }
}
