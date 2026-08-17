<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskGroupController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AiEngineController;
use App\Http\Controllers\WorkspaceController;

Route::get('/', function () {
    return redirect()->route('login');
});

// ─── Guest Routes ───────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

    Route::get('/admin-login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin-login', [AuthController::class, 'adminLogin'])->name('admin.login.submit');
});

// ─── Authenticated Routes (any active user) ──────────────────────
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/join', [WorkspaceController::class, 'joinByCode'])->name('workspaces.join-by-code');
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
    Route::post('/workspaces/join-requests/{joinRequest}/approve', [WorkspaceController::class, 'approveJoinRequest'])->name('workspaces.join-requests.approve');
    Route::post('/workspaces/join-requests/{joinRequest}/reject', [WorkspaceController::class, 'rejectJoinRequest'])->name('workspaces.join-requests.reject');
    Route::post('/workspaces/{workspace}/leave', [WorkspaceController::class, 'applyLeave'])->name('workspaces.leave-request');
    Route::post('/workspaces/leave-requests/{leaveRequest}/approve', [WorkspaceController::class, 'approveLeaveRequest'])->name('workspaces.leave-requests.approve');
    Route::post('/workspaces/leave-requests/{leaveRequest}/reject', [WorkspaceController::class, 'rejectLeaveRequest'])->name('workspaces.leave-requests.reject');
    Route::patch('/workspaces/{workspace}/members/{user}/role', [WorkspaceController::class, 'updateMemberRole'])->name('workspaces.members.update-role');
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.remove');

    Route::get('/tasks', [TaskController::class, 'board'])->name('tasks.board');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::delete('/task-attachments/{attachment}', [TaskController::class, 'deleteAttachment'])->name('task-attachments.destroy');
    Route::post('/task-groups', [TaskGroupController::class, 'store'])->name('task-groups.store');
    Route::delete('/task-groups/{taskGroup}', [TaskGroupController::class, 'destroy'])->name('task-groups.destroy');
    Route::post('/task-groups/generate-ai', [TaskGroupController::class, 'generateWithAi'])->name('task-groups.generate-ai');
    Route::post('/task-groups/{taskGroup}/regenerate', [TaskGroupController::class, 'regenerateNextWeek'])->name('task-groups.regenerate');
    Route::get('/nearby-tasks', [TaskController::class, 'nearby'])->name('tasks.nearby');
    Route::get('/my-activity', [DashboardController::class, 'myActivity'])->name('my-activity');
    Route::post('/request-account-switch', [DashboardController::class, 'requestAccountSwitch'])->name('request-account-switch');
    Route::get('/my-analytics', [DashboardController::class, 'myAnalytics'])->name('my-analytics-page');
    Route::post('/request-admin-access', [DashboardController::class, 'requestAdminAccess'])->name('request-admin-access');
    Route::post('/ai/parse-intent', [AiEngineController::class, 'parseIntent'])->name('ai.parse-intent');
    Route::post('/ai/quick-add-plain', [AiEngineController::class, 'quickAddPlain'])->name('ai.quick-add-plain');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroyAccount'])->name('profile.destroy');
});

// ─── Admin-Only Routes ────────────────────────────────────────────
Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/workspaces', [AdminController::class, 'workspaces'])->name('workspaces');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/suspend', [AdminController::class, 'suspendUser'])->name('users.suspend');
    Route::post('/users/{user}/block', [AdminController::class, 'blockUser'])->name('users.block');
    Route::post('/users/{user}/reactivate', [AdminController::class, 'reactivateUser'])->name('users.reactivate');
    Route::post('/users/{user}/remove-admin-access', [AdminController::class, 'removeAdminAccess'])->name('users.remove-admin-access');

    Route::get('/switch-requests', [AdminController::class, 'switchRequests'])->name('switch-requests');
    Route::post('/switch-requests/{switchRequest}/approve', [AdminController::class, 'approveSwitchRequest'])->name('switch-requests.approve');
    Route::post('/switch-requests/{switchRequest}/reject', [AdminController::class, 'rejectSwitchRequest'])->name('switch-requests.reject');

    Route::get('/moderation-logs', [AdminController::class, 'moderationLogs'])->name('moderation-logs');
    Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs');

    Route::get('/settings', [AdminController::class, 'settingsForm'])->name('settings');
    Route::post('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');

    Route::get('/create-admin', [AdminController::class, 'createAdminForm'])->name('create-admin');
    Route::post('/create-admin', [AdminController::class, 'createAdmin'])->name('create-admin.submit');

    Route::get('/admin-requests', [AdminController::class, 'adminRequests'])->name('admin-requests');
    Route::post('/admin-requests/{adminRequest}/approve', [AdminController::class, 'approveAdminRequest'])->name('admin-requests.approve');
    Route::post('/admin-requests/{adminRequest}/reject', [AdminController::class, 'rejectAdminRequest'])->name('admin-requests.reject');
});

// ─── Logout ──────────────────────────────────────────────────────
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
