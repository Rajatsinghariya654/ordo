<?php

namespace App\Http\Controllers;

use App\Models\AccountSwitchRequest;
use App\Models\ActivityLog;
use App\Models\AdminAccessRequest;
use App\Models\ModerationLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_users' => User::count(),
            'personal_users' => User::where('account_type', 'personal')->count(),
            'business_users' => User::where('account_type', 'business')->count(),
            'total_tasks' => Task::count(),
            'pending_switch_requests' => AccountSwitchRequest::where('status', 'pending')->count(),
            'suspended_users' => User::where('is_suspended', true)->orWhere('is_blocked', true)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function workspaces(): View
    {
        $workspaces = \App\Models\Workspace::with('owner')->withCount('members')->latest()->paginate(15);

        return view('admin.workspaces', compact('workspaces'));
    }

    public function users(): View
    {
        $users = User::orderBy('created_at', 'desc')->paginate(15);

        return view('admin.users', compact('users'));
    }

    public function suspendUser(Request $request, User $user): RedirectResponse
    {
        if (! $user->canBeModeratedBy(auth()->user())) {
            return back()->withErrors(['moderation' => 'You can only moderate admins you personally granted access to.']);
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $user->update(['is_suspended' => true]);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => auth()->id(),
            'action_type' => 'suspended',
            'reason' => $validated['reason'],
        ]);

        ActivityLog::record('user_suspended', "suspended {$user->name}'s account");

        return back()->with('success', "{$user->name} has been suspended.");
    }

    public function blockUser(Request $request, User $user): RedirectResponse
    {
        if (! $user->canBeModeratedBy(auth()->user())) {
            return back()->withErrors(['moderation' => 'You can only moderate admins you personally granted access to.']);
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $user->update(['is_blocked' => true]);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => auth()->id(),
            'action_type' => 'blocked',
            'reason' => $validated['reason'],
        ]);

        ActivityLog::record('user_blocked', "blocked {$user->name}'s account");

        return back()->with('success', "{$user->name} has been blocked.");
    }

    public function reactivateUser(User $user): RedirectResponse
    {
        $user->update(['is_suspended' => false, 'is_blocked' => false]);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => auth()->id(),
            'action_type' => 'reactivated',
        ]);

        ActivityLog::record('user_reactivated', "reactivated {$user->name}'s account");

        return back()->with('success', "{$user->name} has been reactivated.");
    }

    public function switchRequests(): View
    {
        $requests = AccountSwitchRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return view('admin.switch-requests', compact('requests'));
    }

    public function approveSwitchRequest(AccountSwitchRequest $switchRequest): RedirectResponse
    {
        // Conflict-of-interest guard: koi bhi admin apna khud ka switch request approve nahi kar sakta
        if ($switchRequest->user_id === auth()->id()) {
            return back()->withErrors(['switch' => 'You cannot approve your own switch request. Ask another admin to review it.']);
        }

        $updateData = ['account_type' => $switchRequest->requested_type];

        // Agar requester khud Admin hai, to switch approve hone ka matlab hai
        // wo admin role se step down karke normal (personal/business) user ban raha hai
        if ($switchRequest->user->isAdmin()) {
            $updateData['is_admin'] = false;
        }

        $switchRequest->user->update($updateData);

        $switchRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
        ]);

        $logMessage = "approved account switch for {$switchRequest->user->name} to {$switchRequest->requested_type}";
        if (isset($updateData['is_admin'])) {
            $logMessage .= ' (admin privileges revoked as part of this switch)';
        }

        ActivityLog::record('switch_request_approved', $logMessage);

        return back()->with('success', 'Switch request approved.');
    }


    public function rejectSwitchRequest(AccountSwitchRequest $switchRequest): RedirectResponse
    {
        $switchRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
        ]);

        ActivityLog::record('switch_request_rejected', "rejected account switch request from {$switchRequest->user->name}");

        return back()->with('success', 'Switch request rejected.');
    }

    public function moderationLogs(): View
    {
        $logs = ModerationLog::with(['targetUser', 'admin', 'workspace'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.moderation-logs', compact('logs'));
    }

    public function createAdminForm(): View
    {
        return view('admin.create-admin');
    }

    public function createAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'security_code' => ['required'],
        ]);

        if ($validated['security_code'] !== config('services.admin_signup.code')) {
            return back()->withErrors(['security_code' => 'Invalid security code.'])->onlyInput('name', 'email');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'account_type' => 'personal',
            'is_admin' => true,
            'admin_granted_by' => auth()->id()
        ]);

        ActivityLog::record('admin_created', "created a new admin account: {$user->name}");

        return redirect()->route('admin.dashboard')->with('success', 'New admin account created successfully.');
    }

    public function adminRequests(): View
    {
        $requests = AdminAccessRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return view('admin.admin-requests', compact('requests'));
    }

    public function approveAdminRequest(AdminAccessRequest $adminRequest): RedirectResponse
    {
        $adminRequest->user->update(['is_admin' => true, 'admin_granted_by' => auth()->id()]);

        $adminRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
        ]);

        ActivityLog::record('admin_access_approved', "granted admin access to {$adminRequest->user->name}");

        return back()->with('success', 'Admin access approved.');
    }

    public function rejectAdminRequest(AdminAccessRequest $adminRequest): RedirectResponse
    {
        $adminRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
        ]);

        ActivityLog::record('admin_access_rejected', "rejected admin access request from {$adminRequest->user->name}");

        return back()->with('success', 'Admin access request rejected.');
    }

    public function activityLogs(Request $request): View
    {
        $search = $request->query('search');

        $logs = ActivityLog::with('user')
            ->when($search, function ($query, $search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activity-logs', compact('logs', 'search'));
    }

    public function settingsForm(): View
    {
        $settings = [
            'gemini_api_key' => \App\Models\SystemSetting::getValue('gemini_api_key', ''),
            'ai_system_prompt' => \App\Models\SystemSetting::getValue('ai_system_prompt', ''),
            'default_gps_radius' => \App\Models\SystemSetting::getValue('default_gps_radius', '5'),
            'maintenance_mode' => \App\Models\SystemSetting::getValue('maintenance_mode', '0'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function settingsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
            'ai_system_prompt' => ['nullable', 'string', 'max:2000'],
            'default_gps_radius' => ['required', 'numeric', 'min:1', 'max:50'],
            'maintenance_mode' => ['nullable'],
        ]);

        \App\Models\SystemSetting::setValue('gemini_api_key', $validated['gemini_api_key'] ?? '', auth()->id());
        \App\Models\SystemSetting::setValue('ai_system_prompt', $validated['ai_system_prompt'] ?? '', auth()->id());
        \App\Models\SystemSetting::setValue('default_gps_radius', $validated['default_gps_radius'], auth()->id());
        \App\Models\SystemSetting::setValue('maintenance_mode', $request->has('maintenance_mode') ? '1' : '0', auth()->id());

        ActivityLog::record('system_settings_updated', 'updated system settings');

        return back()->with('success', 'Settings updated successfully.');
    }

    public function analytics(): View
    {
        $totalRequests = \App\Models\AiLog::count();
        $successCount = \App\Models\AiLog::where('status', 'success')->count();
        $successRate = $totalRequests > 0 ? round(($successCount / $totalRequests) * 100, 1) : 0;
        $avgResponseTime = round(\App\Models\AiLog::avg('execution_time_ms') ?? 0);
        $totalTokens = \App\Models\AiLog::sum('tokens_used');

        $dailyUsage = \App\Models\AiLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('D, d M');
            $chartData[] = $dailyUsage->get($date)->count ?? 0;
        }

        $statusBreakdown = \App\Models\AiLog::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentLogs = \App\Models\AiLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return view('admin.analytics', compact(
            'totalRequests',
            'successRate',
            'avgResponseTime',
            'totalTokens',
            'chartLabels',
            'chartData',
            'statusBreakdown',
            'recentLogs'
        ));
    }

    public function removeAdminAccess(User $user): RedirectResponse
    {
        if (! $user->canBeModeratedBy(auth()->user())) {
            return back()->withErrors(['moderation' => 'You can only remove admin access from admins you personally granted it to.']);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors(['moderation' => 'You cannot remove your own admin access.']);
        }

        $user->update(['is_admin' => false, 'admin_granted_by' => null]);

        ModerationLog::create([
            'target_user_id' => $user->id,
            'action_by' => auth()->id(),
            'action_type' => 'removed_from_team',
            'reason' => 'Admin access revoked',
        ]);

        ActivityLog::record('admin_access_removed', "removed admin access from {$user->name}");

        return back()->with('success', "{$user->name}'s admin access has been removed.");
    }
}
