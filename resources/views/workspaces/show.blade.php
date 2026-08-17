@extends('layouts.app')

@section('title', $workspace->name . ' - Workspaces - Ordo')
@section('page-title', $workspace->name)

@section('content')
<div class="space-y-8">
    
    {{-- Header & Back Navigation --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="space-y-1">
            <a href="{{ route('workspaces.index') }}" class="inline-flex items-center gap-1 text-xs text-primary-600 hover:underline font-medium mb-1">
                <x-heroicon-o-arrow-left class="w-3.5 h-3.5" /> Back to My Workspaces
            </a>
            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <x-heroicon-o-building-office-2 class="w-7 h-7 text-primary-600" />
                {{ $workspace->name }}
            </h1>
            <p class="text-xs text-gray-500">
                Workspace Owner: <span class="font-medium text-gray-800">{{ $workspace->owner->name }}</span> ({{ $workspace->owner->email }})
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('tasks.board', ['workspace_id' => $workspace->id]) }}"
                class="px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl text-xs transition flex items-center gap-1.5 shadow-sm">
                <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                Go to Workspace Tasks
            </a>

            <div class="px-3.5 py-1.5 bg-primary-50 border border-primary-200 rounded-xl text-xs flex items-center gap-2">
                <x-heroicon-o-key class="w-4 h-4 text-primary-600" />
                <span class="text-gray-600 font-medium">Invite Code:</span>
                <code class="font-mono font-bold text-primary-900 text-xs select-all">{{ $workspace->invite_code }}</code>
            </div>

            {{-- Leave Button for members (if not owner) --}}
            @if (! $workspace->isOwner(auth()->id()))
                <button onclick="document.getElementById('leaveModal').classList.remove('hidden')"
                    class="px-3.5 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 font-medium rounded-xl text-xs transition flex items-center gap-1.5">
                    <x-heroicon-o-arrow-right-start-on-rectangle class="w-4 h-4" />
                    Leave Workspace
                </button>
            @endif
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

    {{-- Pending Join Requests (If Owner or Manager) --}}
    @if ($workspace->canManageMembers(auth()->id()) && $workspace->pendingJoinRequests->count() > 0)
        <div class="bg-amber-50/50 border border-amber-200 p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-amber-900 flex items-center gap-2">
                <x-heroicon-o-user-plus class="w-5 h-5 text-amber-600" />
                Pending Member Join Requests ({{ $workspace->pendingJoinRequests->count() }})
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($workspace->pendingJoinRequests as $req)
                    <div class="bg-white p-4 rounded-xl border border-amber-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $req->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $req->user->email }} • Requested {{ $req->created_at->diffForHumans() }}</p>
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

    {{-- Pending Leave Applications (If Owner or Manager) --}}
    @if ($workspace->canManageMembers(auth()->id()) && $workspace->pendingLeaveRequests->count() > 0)
        <div class="bg-rose-50/50 border border-rose-200 p-6 rounded-2xl space-y-4">
            <h2 class="text-sm font-bold text-rose-900 flex items-center gap-2">
                <x-heroicon-o-document-text class="w-5 h-5 text-rose-600" />
                Pending Leave Applications ({{ $workspace->pendingLeaveRequests->count() }})
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($workspace->pendingLeaveRequests as $req)
                    <div class="bg-white p-4 rounded-xl border border-rose-100 shadow-sm space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $req->user->name }}</p>
                                <p class="text-xs text-gray-500">Leaving Date: <span class="font-medium text-rose-600">{{ $req->leave_date->format('M d, Y') }}</span></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('workspaces.leave-requests.approve', $req) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition">
                                        Approve Leave
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
                            Reason: "{{ $req->reason }}"
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Workspace Members List --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <x-heroicon-o-users class="w-5 h-5 text-primary-600" />
                    Workspace Members ({{ $workspace->members->count() }})
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">List of all active members and their workspace permissions.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-100 text-xs">
                        <th class="py-3.5 px-6">Member</th>
                        <th class="py-3.5 px-6">Account Type</th>
                        <th class="py-3.5 px-6">Workspace Role</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($workspace->members as $member)
                        @php
                            $role = $member->pivot->role;
                        @endphp
                        <tr class="hover:bg-gray-50/50">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-primary-100 text-primary-700 font-bold flex items-center justify-center text-xs">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">{{ $member->name }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $member->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-medium capitalize {{ $member->isBusiness() ? 'bg-purple-50 text-purple-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $member->account_type }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                @if ($workspace->isOwner(auth()->id()) && $role !== 'owner')
                                    {{-- Owner can change roles --}}
                                    <form action="{{ route('workspaces.members.update-role', [$workspace, $member]) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" onchange="this.form.submit()"
                                            class="text-xs font-semibold rounded-lg border-gray-200 py-1 px-2.5 focus:ring-primary-500 focus:border-primary-500 bg-gray-50">
                                            <option value="member" {{ $role === 'member' ? 'selected' : '' }}>Member</option>
                                            <option value="manager" {{ $role === 'manager' ? 'selected' : '' }}>Manager</option>
                                        </select>
                                    </form>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                        {{ $role === 'owner' ? 'bg-purple-100 text-purple-700' : ($role === 'manager' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700') }}">
                                        {{ $role }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if ($workspace->canManageMembers(auth()->id()) && $role !== 'owner')
                                    @if (auth()->user()->isBusiness() || $role === 'member')
                                        <form action="{{ route('workspaces.members.remove', [$workspace, $member]) }}" method="POST" class="inline-block"
                                            onsubmit="return confirm('Are you sure you want to remove {{ $member->name }} from this workspace?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-800 hover:underline">
                                                Remove Member
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Dedicated Workspace Tasks Section --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
            <div>
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-primary-600" />
                    Workspace Tasks Board
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Tasks created within <span class="font-semibold text-gray-700">{{ $workspace->name }}</span>. All members can view; roles define edit/delete access.</p>
            </div>
            <a href="{{ route('tasks.board', ['workspace_id' => $workspace->id]) }}"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap shadow-sm">
                <x-heroicon-o-plus class="w-4 h-4" />
                Manage Tasks Board
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $statusLabels = [
                    'todo' => ['label' => 'To Do', 'color' => 'gray'],
                    'in_progress' => ['label' => 'In Progress', 'color' => 'blue'],
                    'review' => ['label' => 'Review', 'color' => 'amber'],
                    'completed' => ['label' => 'Completed', 'color' => 'emerald']
                ];
            @endphp

            @foreach ($statusLabels as $sKey => $sMeta)
                <div class="bg-gray-50/70 p-3.5 rounded-xl border border-gray-100 flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-200/60 pb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-{{ $sMeta['color'] }}-500"></span>
                            {{ $sMeta['label'] }}
                        </span>
                        <span class="text-[11px] font-bold text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200">
                            {{ isset($workspaceColumns[$sKey]) ? $workspaceColumns[$sKey]->count() : 0 }}
                        </span>
                    </div>

                    <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                        @forelse ($workspaceColumns[$sKey] ?? [] as $t)
                            <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm space-y-2">
                                <div class="flex items-start justify-between gap-1.5">
                                    <h4 class="font-semibold text-xs text-gray-900 line-clamp-2">{{ $t->title }}</h4>
                                    <span class="text-[10px] font-semibold capitalize px-1.5 py-0.5 rounded
                                        {{ $t->priority === 'high' ? 'bg-red-50 text-red-700' : ($t->priority === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $t->priority }}
                                    </span>
                                </div>

                                @if ($t->description)
                                    <p class="text-[11px] text-gray-500 line-clamp-2">{{ $t->description }}</p>
                                @endif

                                <div class="pt-2 border-t border-gray-50 flex items-center justify-between text-[11px] text-gray-400">
                                    <span>By {{ $t->creator->name ?? 'User' }}</span>
                                    @if ($t->attachments->isNotEmpty())
                                        <span class="flex items-center gap-0.5 text-primary-600 font-medium">
                                            <x-heroicon-o-paper-clip class="w-3 h-3" /> {{ $t->attachments->count() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-6 text-[11px] text-gray-400 italic">No tasks</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Role Permission Matrix Banner --}}
    <div class="bg-gray-50 border border-gray-200 p-6 rounded-2xl space-y-3">
        <h3 class="text-xs font-bold text-gray-800 flex items-center gap-2">
            <x-heroicon-o-shield-check class="w-4 h-4 text-primary-600" />
            Workspace Roles & Permissions Summary
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-600">
            <div class="bg-white p-3.5 rounded-xl border border-gray-100 space-y-1">
                <p class="font-bold text-purple-700 uppercase">Owner</p>
                <p>Full workspace control, approve join/leave requests, manage roles, remove members, create/edit/delete any workspace task.</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-gray-100 space-y-1">
                <p class="font-bold text-indigo-700 uppercase">Manager</p>
                <p>Approve join/leave requests, remove standard members, create & edit/delete any workspace task.</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-gray-100 space-y-1">
                <p class="font-bold text-gray-700 uppercase">Member</p>
                <p>View all workspace tasks, create new tasks in workspace, edit/delete only own created tasks, apply to leave workspace.</p>
            </div>
        </div>
    </div>

</div>

{{-- Leave Application Modal --}}
@if (! $workspace->isOwner(auth()->id()))
    <div id="leaveModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl space-y-0 border border-gray-100">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-rose-600 to-rose-700 px-6 py-5 flex items-center justify-between text-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                        <x-heroicon-o-document-text class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold">Apply to Leave Workspace</h3>
                        <p class="text-xs text-rose-100">Submit formal leave request to Workspace Owner/Manager</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('leaveModal').classList.add('hidden')"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>
            </div>

            {{-- Modal Form --}}
            <form action="{{ route('workspaces.leave-request', $workspace) }}" method="POST" class="p-6 space-y-4 bg-white">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                        <x-heroicon-o-chat-bubble-bottom-center-text class="w-4 h-4 text-primary-500" />
                        Reason for Leaving
                    </label>
                    <textarea name="reason" rows="3" required placeholder="Describe why you want to leave this workspace..."
                        class="w-full rounded-xl border border-primary-300 bg-gray-50/50 px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                        <x-heroicon-o-calendar class="w-4 h-4 text-primary-500" />
                        Target Leave Date
                    </label>
                    <input type="date" name="leave_date" min="{{ date('Y-m-d') }}" required
                        class="w-full rounded-xl border border-primary-300 bg-gray-50/50 px-3.5 py-2.5 text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('leaveModal').classList.add('hidden')"
                        class="px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-md flex items-center gap-1.5">
                        <x-heroicon-o-paper-airplane class="w-4 h-4" />
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
