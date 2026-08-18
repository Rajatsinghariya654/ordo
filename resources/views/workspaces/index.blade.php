@extends('layouts.app')

@section('title', 'My Workspaces - Ordo')
@section('page-title', 'My Workspaces & Teams')

@section('content')
<div class="space-y-8" x-data="{ helpOpen: false }">
    
    {{-- Header --}}
    <div class="flex flex-col bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-start justify-between gap-2 mb-4">
            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <x-heroicon-o-building-office-2 class="w-7 h-7 text-primary-600" />
                My Workspaces & Teams
            </h1>
            <button @click="helpOpen = !helpOpen" class="md:hidden flex-shrink-0 w-9 h-9 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-600 flex items-center justify-center transition" title="Info">
                <x-heroicon-o-information-circle class="w-5 h-5" />
            </button>
        </div>
        <p class="text-xs text-gray-500 mt-1 hidden md:block">
            Manage team workspaces, join existing teams with an invite code, or process member join/leave applications.
        </p>
        <div x-show="helpOpen" class="md:hidden mt-3 p-3 bg-primary-50 border border-primary-200 rounded-lg text-xs text-primary-700">
            Manage team workspaces, join existing teams with an invite code, or process member join/leave applications.
        </div>

        <div class="mt-4">

            {{-- Join via Code Quick Form --}}
            <form action="{{ route('workspaces.join-by-code') }}" method="POST" class="flex flex-col md:flex-row md:items-center gap-2">
                @csrf
                <div class="relative flex-1 md:flex-initial">
                    <x-heroicon-o-key class="w-4 h-4 text-primary-500 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input type="text" name="invite_code" placeholder="Enter Invite Code (e.g. ABC12345)" required
                        class="w-full pl-9 pr-3.5 py-2.5 bg-white border border-primary-300 rounded-xl text-xs font-mono font-bold text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 uppercase transition shadow-sm">
                </div>
                <button type="submit" class="w-full md:w-auto px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl text-xs transition shadow flex items-center justify-center gap-1.5 whitespace-nowrap">
                    <x-heroicon-o-arrow-right-end-on-rectangle class="w-4 h-4" />
                    Join Workspace
                </button>
            </form>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center gap-2">
            <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 flex-shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm flex items-center gap-2">
            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-600 flex-shrink-0" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Business User: Create Workspace Card --}}
    @if (auth()->user()->isBusiness() || auth()->user()->isAdmin())
        <div class="bg-gradient-to-r from-primary-900 to-indigo-900 text-white p-6 rounded-2xl shadow-md">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h2 class="text-base font-bold flex items-center gap-2">
                        <x-heroicon-o-plus-circle class="w-5 h-5 text-primary-400" />
                        Create a New Business Workspace
                    </h2>
                    <p class="text-xs text-primary-200">
                        Business accounts can create workspaces, generate invite codes, and assign Manager & Member roles.
                    </p>
                </div>
                <form action="{{ route('workspaces.store') }}" method="POST" class="flex items-center gap-3 w-full md:w-auto">
                    @csrf
                    <input type="text" name="name" placeholder="Workspace Name (e.g. Tech Division)" required
                        class="px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-xs text-white placeholder-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-400 w-full md:w-64">
                    <button type="submit" class="px-4 py-2 bg-white text-primary-900 hover:bg-primary-50 font-bold rounded-xl text-xs transition shadow flex items-center gap-1.5 whitespace-nowrap">
                        <x-heroicon-o-sparkles class="w-4 h-4 text-primary-600" />
                        Create Workspace
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Incoming Requests Section (For Owners / Managers) --}}
    @if ($incomingJoinRequests->count() > 0 || $incomingLeaveRequests->count() > 0)
        <div class="space-y-4">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <x-heroicon-o-bell-alert class="w-5 h-5 text-amber-500" />
                Action Required: Pending Applications
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Incoming Join Requests --}}
                @if ($incomingJoinRequests->count() > 0)
                    <div class="bg-white rounded-2xl p-5 border border-amber-200 shadow-sm space-y-4">
                        <h3 class="text-xs font-semibold text-gray-800 flex items-center gap-2">
                            <x-heroicon-o-user-plus class="w-4 h-4 text-amber-600" />
                            Join Requests ({{ $incomingJoinRequests->count() }})
                        </h3>
                        <div class="divide-y divide-gray-100">
                            @foreach ($incomingJoinRequests as $req)
                                <div class="py-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $req->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $req->user->email }} • Want to join <span class="font-medium text-gray-700">{{ $req->workspace->name }}</span></p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $req->created_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('workspaces.join-requests.approve', $req) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('workspaces.join-requests.reject', $req) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-rose-50 hover:text-rose-600 text-gray-600 rounded-lg text-xs font-medium transition">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Incoming Leave Requests --}}
                @if ($incomingLeaveRequests->count() > 0)
                    <div class="bg-white rounded-2xl p-5 border border-rose-200 shadow-sm space-y-4">
                        <h3 class="text-xs font-semibold text-gray-800 flex items-center gap-2">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="w-4 h-4 text-rose-600" />
                            Leave Applications ({{ $incomingLeaveRequests->count() }})
                        </h3>
                        <div class="divide-y divide-gray-100">
                            @foreach ($incomingLeaveRequests as $req)
                                <div class="py-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $req->user->name }}</p>
                                            <p class="text-xs text-gray-500">Leaving <span class="font-medium text-gray-700">{{ $req->workspace->name }}</span> on <span class="font-semibold text-rose-600">{{ $req->leave_date->format('M d, Y') }}</span></p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('workspaces.leave-requests.approve', $req) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('workspaces.leave-requests.reject', $req) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-rose-50 hover:text-rose-600 text-gray-600 rounded-lg text-xs font-medium transition">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 p-2.5 rounded-lg text-xs text-gray-600 italic">
                                        "{{ $req->reason }}"
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Workspaces List --}}
    <div class="space-y-4">
        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-rectangle-stack class="w-5 h-5 text-primary-600" />
            Workspaces You Belong To
        </h2>

        @if ($joinedWorkspaces->count() === 0)
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 space-y-3">
                <x-heroicon-o-building-office-2 class="w-12 h-12 text-gray-300 mx-auto" />
                <h3 class="text-base font-semibold text-gray-700">No workspaces yet</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto">
                    Enter an invite code provided by your Business Owner or Manager above to join your team's workspace!
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($joinedWorkspaces as $ws)
                    @php
                        $role = $ws->userRole(auth()->id());
                    @endphp
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-gray-900 text-base truncate">{{ $ws->name }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider
                                    {{ $role === 'owner' ? 'bg-purple-100 text-purple-700' : ($role === 'manager' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $role ?? 'Member' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-user-group class="w-4 h-4 text-gray-400" />
                                    {{ $ws->members_count }} {{ Str::plural('member', $ws->members_count) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                                    Owner: {{ $ws->owner->name }}
                                </span>
                            </div>

                            @if (in_array($role, ['owner', 'manager']))
                                <div class="bg-primary-50 p-2.5 rounded-xl flex items-center justify-between text-xs">
                                    <span class="text-primary-700 font-medium">Invite Code:</span>
                                    <code class="font-mono font-bold text-primary-900 bg-white px-2 py-0.5 rounded border border-primary-200 select-all">{{ $ws->invite_code }}</code>
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-gray-100 grid grid-cols-2 gap-2">
                            <a href="{{ route('workspaces.show', $ws) }}"
                                class="w-full text-center px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium rounded-xl text-xs transition flex items-center justify-center gap-1">
                                <x-heroicon-o-eye class="w-3.5 h-3.5" />
                                Members
                            </a>
                            <a href="{{ route('tasks.board', ['workspace_id' => $ws->id]) }}"
                                class="w-full text-center px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl text-xs transition flex items-center justify-center gap-1 shadow-sm">
                                <x-heroicon-o-clipboard-document-list class="w-3.5 h-3.5" />
                                Tasks
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Personal User: My Pending Requests Tracker --}}
    @if ($myJoinRequests->count() > 0 || $myLeaveRequests->count() > 0)
        <div class="space-y-4 pt-4 border-t border-gray-100">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                My Submitted Applications Status
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($myJoinRequests as $req)
                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Join Request: {{ $req->workspace->name }}</p>
                            <p class="text-xs text-gray-500">Submitted {{ $req->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded-lg border border-amber-200">
                            Pending Approval
                        </span>
                    </div>
                @endforeach

                @foreach ($myLeaveRequests as $req)
                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Leave Application: {{ $req->workspace->name }}</p>
                            <p class="text-xs text-gray-500">Requested Leave Date: {{ $req->leave_date->format('M d, Y') }}</p>
                        </div>
                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded-lg border border-amber-200">
                            Pending Review
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
