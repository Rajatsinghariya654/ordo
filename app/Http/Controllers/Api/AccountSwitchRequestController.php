<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountSwitchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountSwitchRequestController extends Controller
{
    /**
     * POST /api/v1/account-switch-requests
     * User apna switch request bhejta hai
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requested_type' => ['required', 'in:personal,business'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // Agar already same type hai to request ki zaroorat nahi
        if ($user->account_type === $validated['requested_type']) {
            return response()->json([
                'message' => 'You are already on this account type.',
            ], 422);
        }

        // Agar ek pending request already hai to dobara na bheje
        $existing = $user->switchRequests()->where('status', 'pending')->first();
        if ($existing) {
            return response()->json([
                'message' => 'You already have a pending switch request.',
            ], 422);
        }

        $switchRequest = AccountSwitchRequest::create([
            'user_id' => $user->id,
            'requested_type' => $validated['requested_type'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Switch request submitted. Waiting for admin approval.',
            'data' => $switchRequest,
        ], 201);
    }

    /**
     * GET /api/v1/admin/account-switch-requests
     * Admin — saari pending requests dekhe
     */
    public function index(): JsonResponse
    {
        $requests = AccountSwitchRequest::with('user')
            ->pending()
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $requests]);
    }

    /**
     * POST /api/v1/admin/account-switch-requests/{switchRequest}/approve
     */
    public function approve(Request $request, AccountSwitchRequest $switchRequest): JsonResponse
    {
        if ($switchRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been reviewed.'], 422);
        }

        // Permanent switch — user ka account_type change ho jaata hai hamesha ke liye
        $switchRequest->user->update(['account_type' => $switchRequest->requested_type]);

        $switchRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Switch request approved. User account type updated.',
            'data' => $switchRequest->fresh(),
        ]);
    }

    /**
     * POST /api/v1/admin/account-switch-requests/{switchRequest}/reject
     */
    public function reject(Request $request, AccountSwitchRequest $switchRequest): JsonResponse
    {
        if ($switchRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been reviewed.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $switchRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reason' => $validated['reason'] ?? $switchRequest->reason,
        ]);

        return response()->json([
            'message' => 'Switch request rejected.',
            'data' => $switchRequest->fresh(),
        ]);
    }
}