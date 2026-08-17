<?php

use App\Http\Controllers\Api\AccountSwitchRequestController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Public Routes ──────────────────────────────────────────────
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // ─── Protected Routes (any active user) ────────────────────────
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/me', function (\Illuminate\Http\Request $request) {
            return $request->user();
        });

        Route::apiResource('tasks', TaskController::class)->names('api.tasks');

        Route::post('/account-switch-requests', [AccountSwitchRequestController::class, 'store']);

        Route::post('/workspaces/{workspace}/leave-request', [WorkspaceController::class, 'requestLeave']);
    });

    // ─── Admin-Only Routes ──────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'active', 'admin'])->prefix('admin')->group(function () {
        Route::get('/account-switch-requests', [AccountSwitchRequestController::class, 'index']);
        Route::post('/account-switch-requests/{switchRequest}/approve', [AccountSwitchRequestController::class, 'approve']);
        Route::post('/account-switch-requests/{switchRequest}/reject', [AccountSwitchRequestController::class, 'reject']);

        Route::post('/moderation/{user}/remove-from-team', [ModerationController::class, 'removeFromTeam']);
        Route::post('/moderation/{user}/suspend', [ModerationController::class, 'suspend']);
        Route::post('/moderation/{user}/block', [ModerationController::class, 'block']);
        Route::post('/moderation/{user}/reactivate', [ModerationController::class, 'reactivate']);
        Route::get('/moderation/logs', [ModerationController::class, 'logs']);

        Route::post('/workspaces/{workspace}/members/{userId}/remove', [WorkspaceController::class, 'removeMember']);
    });

});