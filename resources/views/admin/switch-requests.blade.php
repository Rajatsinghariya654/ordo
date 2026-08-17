@extends('layouts.app')

@section('title', 'Switch Requests - Ordo')
@section('page-title', 'Account Switch Requests')

@section('content')

    @if ($requests->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-gray-200 p-12 text-center">
            <x-heroicon-o-check-circle class="w-10 h-10 text-green-400 mx-auto mb-3" />
            <p class="text-gray-500 text-sm">No pending switch requests.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($requests as $req)
                <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-sm font-semibold">
                            {{ strtoupper(substr($req->user->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">{{ $req->user->name }}</p>
                            <p class="text-sm text-gray-500">
                                <span class="capitalize font-medium">{{ $req->user->account_type }}</span>
                                →
                                <span class="capitalize font-medium text-primary-600">{{ $req->requested_type }}</span>
                            </p>
                            @if ($req->reason)
                                <p class="text-xs text-gray-400 mt-1 italic">"{{ $req->reason }}"</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.switch-requests.reject', $req) }}">
                            @csrf
                            <button class="text-sm text-red-600 border border-red-200 px-3.5 py-1.5 rounded-lg hover:bg-red-50">Reject</button>
                        </form>
                        <form method="POST" action="{{ route('admin.switch-requests.approve', $req) }}">
                            @csrf
                            <button class="text-sm bg-primary-500 hover:bg-primary-600 text-white px-3.5 py-1.5 rounded-lg">Approve</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection