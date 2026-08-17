@extends('layouts.app')

@section('title', 'Moderation Logs - Ordo')
@section('page-title', 'Moderation Logs')

@section('content')

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-medium">User</th>
                    <th class="text-left px-5 py-3 font-medium">Action</th>
                    <th class="text-left px-5 py-3 font-medium">By Admin</th>
                    <th class="text-left px-5 py-3 font-medium">Reason</th>
                    <th class="text-left px-5 py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-5 py-3.5 font-medium text-gray-800">{{ $log->targetUser->name ?? 'Deleted User' }}</td>
                        <td class="px-5 py-3.5">
                            @php
                                $badgeColor = match($log->action_type) {
                                    'suspended' => 'bg-amber-100 text-amber-600',
                                    'blocked' => 'bg-red-100 text-red-600',
                                    'reactivated' => 'bg-green-100 text-green-600',
                                    'removed_from_team' => 'bg-gray-100 text-gray-600',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="text-xs px-2.5 py-1 rounded-full {{ $badgeColor }}">
                                {{ str_replace('_', ' ', $log->action_type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $log->admin->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $log->reason ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400 text-xs">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-gray-400">No moderation actions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $logs->links() }}
    </div>

@endsection