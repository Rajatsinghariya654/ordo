@extends('layouts.app')

@section('title', 'AI Analytics - Ordo')
@section('page-title', 'AI Analytics')

@section('content')

<div x-data=\"{ helpOpen: false }\">
    <div class=\"flex items-center justify-between mb-6 md:hidden\">
        <h3 class=\"text-sm font-semibold text-gray-700\">AI System Performance</h3>
        <button @click=\"helpOpen = !helpOpen\" class=\"flex-shrink-0 w-9 h-9 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-600 flex items-center justify-center transition\" title=\"Info\">
            <x-heroicon-o-information-circle class=\"w-5 h-5\" />
        </button>
    </div>
    <div x-show=\"helpOpen\" class=\"md:hidden mb-4 p-4 bg-primary-50 border border-primary-200 rounded-xl text-xs text-primary-700\">
        View real-time AI request analytics, performance metrics, and recent request logs.
    </div>

    {{-- Summary Cards --}}
    <div class=\"grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6\">
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-gray-800">{{ $totalRequests }}</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-sparkles class="w-3.5 h-3.5" /> Total AI Requests
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-green-600">{{ $successRate }}%</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-check-circle class="w-3.5 h-3.5" /> Success Rate
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-blue-600">{{ $avgResponseTime }}ms</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-clock class="w-3.5 h-3.5" /> Avg Response Time
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-primary-600">{{ number_format($totalTokens) }}</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-cpu-chip class="w-3.5 h-3.5" /> Total Tokens Used
            </p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Requests — Last 7 Days</h3>
            <div class="w-full h-64 md:h-96">
                <canvas id="dailyUsageChart" height="90"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Status Breakdown</h3>
            <div class="w-full h-64">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

    {{-- Recent Requests --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">Recent AI Requests</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-medium">User</th>
                    <th class="text-left px-5 py-3 font-medium">Prompt</th>
                    <th class="text-left px-5 py-3 font-medium">Status</th>
                    <th class="text-left px-5 py-3 font-medium">Tokens</th>
                    <th class="text-left px-5 py-3 font-medium">Time</th>
                    <th class="text-left px-5 py-3 font-medium">When</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($recentLogs as $log)
                    <tr>
                        <td class="px-5 py-3.5 text-gray-700">{{ $log->user->name ?? 'Deleted User' }}</td>
                        <td class="px-5 py-3.5 text-gray-500 max-w-xs truncate">{{ $log->prompt_text }}</td>
                        <td class="px-5 py-3.5">
                            @php
                                $badgeColor = match($log->status) {
                                    'success' => 'bg-green-100 text-green-600',
                                    'failed' => 'bg-red-100 text-red-600',
                                    'error' => 'bg-red-100 text-red-600',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $badgeColor }} capitalize">{{ $log->status }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $log->tokens_used }}</td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $log->execution_time_ms }}ms</td>
                        <td class="px-5 py-3.5 text-gray-400 text-xs">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-gray-400">No AI requests logged yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    <script>
        new Chart(document.getElementById('dailyUsageChart'), {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'AI Requests',
                    data: @json($chartData),
                    backgroundColor: '#7c6ff0',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($statusBreakdown->keys()) !!},
                datasets: [{
                    data: {!! json_encode($statusBreakdown->values()) !!},
                    backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#94a3b8'],
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
            }
        });
    </script>
    {{-- @formatter:off --}}
    @endpush

@endsection