@extends('layouts.app')

@section('title', 'My Activity - Ordo')
@section('page-title', 'My Activity')

@section('content')

    <p class="text-sm text-gray-500 mb-6">A timeline of everything you've done in Ordo.</p>

    @if ($logs->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-gray-200 p-12 text-center">
            <x-heroicon-o-clock class="w-10 h-10 text-gray-300 mx-auto mb-3" />
            <p class="text-gray-500 text-sm">No activity yet. Start creating tasks!</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-50">
            @foreach ($logs as $log)
                <div class="flex items-start gap-3 px-5 py-4">
                    <div class="w-8 h-8 rounded-full bg-primary-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <x-heroicon-o-bolt class="w-4 h-4 text-primary-600" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-700">You {{ $log->description }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }} · {{ $log->created_at->format('d M, h:i A') }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @endif

@endsection