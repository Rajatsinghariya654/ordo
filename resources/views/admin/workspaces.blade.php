@extends('layouts.app')

@section('title', 'Workspaces Admin - Ordo')
@section('page-title', 'System Workspaces Overview')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <x-heroicon-o-building-office-2 class="w-7 h-7 text-primary-600" />
                System Workspaces Overview
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Metadata view of all registered business and team workspaces in Ordo.
            </p>
        </div>
        <div class="px-3 py-1.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg text-xs font-semibold">
            Admin Metadata View
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-100 text-xs">
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Workspace Name</th>
                        <th class="py-3.5 px-6">Owner</th>
                        <th class="py-3.5 px-6">Invite Code</th>
                        <th class="py-3.5 px-6">Members Count</th>
                        <th class="py-3.5 px-6">Created At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($workspaces as $ws)
                        <tr class="hover:bg-gray-50/50">
                            <td class="py-4 px-6 font-mono text-xs text-gray-400">#{{ $ws->id }}</td>
                            <td class="py-4 px-6 font-semibold text-gray-900 text-xs">{{ $ws->name }}</td>
                            <td class="py-4 px-6">
                                <p class="font-medium text-gray-800 text-xs">{{ $ws->owner->name ?? 'N/A' }}</p>
                                <p class="text-[11px] text-gray-500">{{ $ws->owner->email ?? 'N/A' }}</p>
                            </td>
                            <td class="py-4 px-6">
                                <code class="font-mono text-xs bg-gray-100 px-2 py-1 rounded font-bold text-gray-700">{{ $ws->invite_code }}</code>
                            </td>
                            <td class="py-4 px-6 font-medium text-gray-700 text-xs">
                                {{ $ws->members_count }} members
                            </td>
                            <td class="py-4 px-6 text-xs text-gray-500">
                                {{ $ws->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500 text-xs">No workspaces found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($workspaces->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $workspaces->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
