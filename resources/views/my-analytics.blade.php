@extends('layouts.app')

@section('title', 'My Analytics - Ordo')
@section('page-title', 'My Analytics')

@section('content')

<div x-data="{ helpOpen: false }">
    <div class="flex items-center justify-between mb-6">
        <div class="hidden md:block">
            <p class="text-sm text-gray-500">A look at how many tasks you've completed over time.</p>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <form method="GET" action="{{ route('my-analytics-page') }}" class="w-full md:w-auto">
                <select name="months" onchange="this.form.submit()"
                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white w-full md:w-auto">
                    <option value="3" {{ $months == 3 ? 'selected' : '' }}>Last 3 Months</option>
                    <option value="6" {{ $months == 6 ? 'selected' : '' }}>Last 6 Months</option>
                    <option value="12" {{ $months == 12 ? 'selected' : '' }}>Last 12 Months</option>
                </select>
            </form>
            <button @click="helpOpen = !helpOpen" class="md:hidden flex-shrink-0 w-10 h-10 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-600 flex items-center justify-center transition" title="Info">
                <x-heroicon-o-information-circle class="w-5 h-5" />
            </button>
        </div>
    </div>

    <div x-show="helpOpen" class="md:hidden mb-4 p-4 bg-primary-50 border border-primary-200 rounded-xl text-sm text-primary-700">
        A look at how many tasks you've completed over time.
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-gray-800">{{ $totalTasks }}</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-clipboard-document-list class="w-3.5 h-3.5" /> Total Tasks
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-green-600">{{ $completedTasks }}</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-check-circle class="w-3.5 h-3.5" /> Completed
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-amber-500">{{ $pendingTasks }}</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-clock class="w-3.5 h-3.5" /> Pending
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-primary-600">{{ $completionRate }}%</p>
            <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                <x-heroicon-o-chart-bar class="w-3.5 h-3.5" /> Completion Rate
            </p>
        </div>
    </div>

    {{-- Chart --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Tasks Completed per Month</h3>
            <p class="text-xs text-gray-500">Last {{ $months }} months</p>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

    @push('scripts')
    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Completed Tasks',
                    data: @json($chartData),
                    backgroundColor: '#7c6ff0',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { ticks: { font: { size: 11 } } }
                }
            }
        });
    </script>
    @endpush

@endsection