@extends('layouts.app')

@section('title', 'Manage Users - Ordo')
@section('page-title', 'Manage Users')

@section('content')

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-medium">Name</th>
                    <th class="text-left px-5 py-3 font-medium">Email</th>
                    <th class="text-left px-5 py-3 font-medium">Type</th>
                    <th class="text-left px-5 py-3 font-medium">Status</th>
                    <th class="text-right px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($users as $user)
                <tr>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-semibold flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-800">{{ $user->name }}</span>
                            @if ($user->isAdmin())
                            <span class="text-xs bg-gray-900 text-white px-2 py-0.5 rounded-full">Admin</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500">{{ $user->email }}</td>
                    <td class="px-5 py-3.5 text-gray-500 capitalize">
                        @if ($user->isAdmin())
                        <span class="text-gray-300">—</span>
                        @else
                        {{ $user->account_type }}
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        @if ($user->is_blocked)
                        <span class="text-xs bg-red-100 text-red-600 px-2.5 py-1 rounded-full">Blocked</span>
                        @elseif ($user->is_suspended)
                        <span class="text-xs bg-amber-100 text-amber-600 px-2.5 py-1 rounded-full">Suspended</span>
                        @else
                        <span class="text-xs bg-green-100 text-green-600 px-2.5 py-1 rounded-full">Active</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        @php
                            $canModerate = $user->id !== auth()->id() && $user->canBeModeratedBy(auth()->user());
                        @endphp

                        @if ($canModerate)
                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            @if ($user->is_suspended || $user->is_blocked)
                            <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                                @csrf
                                <button class="text-xs text-green-600 hover:underline">Reactivate</button>
                            </form>
                            @else
                            <button @click="$refs.suspend{{ $user->id }}.showModal()" class="text-xs text-amber-600 hover:underline">Suspend</button>
                            <button @click="$refs.block{{ $user->id }}.showModal()" class="text-xs text-red-600 hover:underline">Block</button>
                            @endif

                            @if ($user->isAdmin())
                            <button @click="$refs.removeAdmin{{ $user->id }}.showModal()" class="text-xs text-gray-500 hover:underline">Remove Admin</button>
                            @endif
                        </div>

                        {{-- Suspend Modal --}}
                        <dialog x-ref="suspend{{ $user->id }}" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 m-0 rounded-xl p-0 backdrop:bg-black/40 w-full max-w-sm">
                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="p-6">
                                @csrf
                                <h3 class="font-semibold text-gray-800 mb-3">Suspend {{ $user->name }}?</h3>
                                <textarea name="reason" required placeholder="Reason for suspension..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-4" rows="3"></textarea>
                                <div class="flex gap-3">
                                    <button type="button" onclick="this.closest('dialog').close()" class="flex-1 border border-gray-300 text-gray-600 text-sm rounded-lg py-2">Cancel</button>
                                    <button type="submit" class="flex-1 bg-amber-500 text-white text-sm rounded-lg py-2">Suspend</button>
                                </div>
                            </form>
                        </dialog>

                        {{-- Block Modal --}}
                        <dialog x-ref="block{{ $user->id }}" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 m-0 rounded-xl p-0 backdrop:bg-black/40 w-full max-w-sm">
                            <form method="POST" action="{{ route('admin.users.block', $user) }}" class="p-6">
                                @csrf
                                <h3 class="font-semibold text-gray-800 mb-3">Block {{ $user->name }}?</h3>
                                <p class="text-xs text-red-500 mb-3">This is a serious action for misuse or data-leak cases.</p>
                                <textarea name="reason" required placeholder="Reason for blocking..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-4" rows="3"></textarea>
                                <div class="flex gap-3">
                                    <button type="button" onclick="this.closest('dialog').close()" class="flex-1 border border-gray-300 text-gray-600 text-sm rounded-lg py-2">Cancel</button>
                                    <button type="submit" class="flex-1 bg-red-600 text-white text-sm rounded-lg py-2">Block</button>
                                </div>
                            </form>
                        </dialog>

                        @if ($user->isAdmin())
                        {{-- Remove Admin Access Modal --}}
                        <dialog x-ref="removeAdmin{{ $user->id }}" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 m-0 rounded-xl p-0 backdrop:bg-black/40 w-full max-w-sm">
                            <form method="POST" action="{{ route('admin.users.remove-admin-access', $user) }}" class="p-6">
                                @csrf
                                <h3 class="font-semibold text-gray-800 mb-3">Remove admin access from {{ $user->name }}?</h3>
                                <p class="text-xs text-gray-500 mb-4">They'll become a regular user again. Their account and tasks stay intact.</p>
                                <div class="flex gap-3">
                                    <button type="button" onclick="this.closest('dialog').close()" class="flex-1 border border-gray-300 text-gray-600 text-sm rounded-lg py-2">Cancel</button>
                                    <button type="submit" class="flex-1 bg-gray-800 text-white text-sm rounded-lg py-2">Remove Admin Access</button>
                                </div>
                            </form>
                        </dialog>
                        @endif
                        @elseif ($user->isAdmin() && $user->id !== auth()->id())
                        <span class="text-xs text-gray-300 italic">Granted by another admin</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    {{ $users->links() }}
</div>

@endsection