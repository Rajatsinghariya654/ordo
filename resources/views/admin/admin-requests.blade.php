@extends('layouts.app')

@section('title', 'Admin Requests - Ordo')
@section('page-title', 'Admin Access Requests')

@section('content')

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Review and manage requests from users asking for admin privileges.</p>
        @if (! $requests->isEmpty())
            <span class="text-xs font-medium bg-amber-100 text-amber-700 px-3 py-1.5 rounded-full flex items-center gap-1.5">
                <x-heroicon-o-clock class="w-3.5 h-3.5" /> {{ $requests->count() }} Pending
            </span>
        @endif
    </div>

    @if ($requests->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-14 text-center">
            <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                <x-heroicon-o-check-circle class="w-8 h-8 text-green-500" />
            </div>
            <p class="text-gray-700 font-medium">All caught up</p>
            <p class="text-gray-400 text-sm mt-1">No pending admin access requests right now.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($requests as $req)
                <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4 min-w-0">
                            <div class="w-11 h-11 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-sm font-semibold flex-shrink-0">
                                {{ strtoupper(substr($req->user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-medium text-gray-800">{{ $req->user->name }}</p>
                                    <span class="text-xs capitalize bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                                        {{ $req->user->account_type }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                    <x-heroicon-o-envelope class="w-3.5 h-3.5" /> {{ $req->user->email }}
                                </p>

                                <div class="mt-3 bg-gray-50 border border-gray-100 rounded-lg px-3.5 py-2.5">
                                    <p class="text-sm text-gray-600 flex items-start gap-2">
                                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" />
                                        <span>{{ $req->reason }}</span>
                                    </p>
                                </div>

                                <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                    Requested {{ $req->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <form method="POST" action="{{ route('admin.admin-requests.reject', $req) }}">
                                @csrf
                                <button class="text-sm text-red-600 border border-red-200 px-3.5 py-1.5 rounded-lg hover:bg-red-50 transition flex items-center gap-1.5">
                                    <x-heroicon-o-x-mark class="w-4 h-4" /> Reject
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.admin-requests.approve', $req) }}"
                                onsubmit="return confirm('Grant admin access to {{ $req->user->name }}?');">
                                @csrf
                                <button class="text-sm bg-gray-900 hover:bg-gray-800 text-white px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5">
                                    <x-heroicon-o-check class="w-4 h-4" /> Approve
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection