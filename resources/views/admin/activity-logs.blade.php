@extends('layouts.app')

@section('title', 'Activity Logs - Ordo')
@section('page-title', 'Activity Logs')

@section('content')

{{-- Search Bar --}}
<form method="GET" action="{{ route('admin.activity-logs') }}" class="mb-6">
    <div class="relative max-w-md">
        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by task or user name..."
            class="w-full rounded-lg border border-gray-300 pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
    </div>
</form>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="text-left px-5 py-3 font-medium">User</th>
                <th class="text-left px-5 py-3 font-medium">Activity</th>
                <th class="text-left px-5 py-3 font-medium">When</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($logs as $log)
            <tr>
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-semibold flex-shrink-0">
                            {{ $log->user ? strtoupper(substr($log->user->name, 0, 1)) : '?' }}
                        </div>
                        <span class="font-medium text-gray-800">{{ $log->user->name ?? 'Deleted User' }}</span>
                    </div>
                </td>
                <td class="px-5 py-3.5 text-gray-600">
                    @if ($log->action === 'account_deleted')
                    <span class="inline-flex items-center gap-1.5 text-red-600 font-medium">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        {{ $log->description }}
                    </span>
                    @else
                    {{ $log->description }}
                    @endif
                </td>
                <td class="px-5 py-3.5 text-gray-400 text-xs">{{ $log->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="px-5 py-10 text-center text-gray-400">
                    @if ($search)
                    No activity found for "{{ $search }}".
                    @else
                    No activity logged yet.
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $logs->links() }}
</div>

@endsection