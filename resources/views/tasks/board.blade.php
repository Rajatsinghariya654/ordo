@extends('layouts.app')

@section('title', 'Tasks - Ordo')
@section('page-title', auth()->user()->isBusiness() ? 'Team Tasks' : 'My Tasks')

@section('content')

<style>
    .leaflet-control-zoom {
        border: none !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15) !important;
        border-radius: 8px !important;
        overflow: hidden;
    }

    .leaflet-control-zoom a {
        width: 26px !important;
        height: 26px !important;
        line-height: 26px !important;
        font-size: 15px !important;
        color: #7c6ff0 !important;
        background: white !important;
        border: none !important;
    }

    .leaflet-control-zoom a:first-child {
        border-bottom: 1px solid #f0eefe !important;
    }

    .leaflet-control-zoom a:hover {
        background: #f5f3ff !important;
        color: #6d5fe0 !important;
    }
</style>


<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-6">

    {{-- Search + Filter --}}
    <div class="flex flex-col sm:flex-row gap-3 flex-1"
        x-data="{
            search: '',
            groupFilter: '',
            filterTasks() {
                document.querySelectorAll('[data-task-card]').forEach(card => {
                    const title = (card.dataset.taskTitle || '').toLowerCase();
                    const group = (card.dataset.taskGroup || '').toLowerCase();
                    const matchesSearch = !this.search || title.includes(this.search.toLowerCase()) || group.includes(this.search.toLowerCase());
                    const matchesGroup = !this.groupFilter || card.dataset.taskGroupId === this.groupFilter;
                    card.style.display = (matchesSearch && matchesGroup) ? '' : 'none';
                });
            }
        }">
        <div class="relative flex-1 max-w-md">
            <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="text" x-model="search" @input="filterTasks()" placeholder="Search tasks or groups..."
                class="w-full text-sm rounded-lg border border-gray-200 pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        @if (isset($workspaces) && $workspaces->isNotEmpty())
        <select onchange="window.location.href = this.value"
            class="text-sm border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white w-auto font-medium text-gray-700">
            <option value="{{ route('tasks.board') }}" {{ empty($selectedWorkspaceId) ? 'selected' : '' }}>All Tasks</option>
            <option value="{{ route('tasks.board', ['workspace_id' => 'personal']) }}" {{ isset($selectedWorkspaceId) && $selectedWorkspaceId === 'personal' ? 'selected' : '' }}>Personal Tasks Only</option>
            @foreach ($workspaces as $ws)
                <option value="{{ route('tasks.board', ['workspace_id' => $ws->id]) }}" {{ isset($selectedWorkspaceId) && $selectedWorkspaceId == $ws->id ? 'selected' : '' }}>
                    🏢 {{ $ws->name }}
                </option>
            @endforeach
        </select>
        @endif

        @if ($groups->isNotEmpty())
        <select x-model="groupFilter" @change="filterTasks()"
            class="text-sm border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white w-auto">
            <option value="">All Groups</option>
            @foreach ($groups as $group)
            <option value="{{ $group->id }}">{{ $group->title }} ({{ $group->tasks_count }})</option>
            @endforeach
        </select>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-2 flex-shrink-0">
        <button @click="$refs.newGroupModal.showModal()"
            class="flex items-center gap-1.5 text-white bg-blue-500 hover:bg-blue-600 text-sm font-medium px-4 py-2.5 rounded-lg transition">
            <x-heroicon-o-sparkles class="w-4 h-4" /> New Group
        </button>
        <button @click="$refs.addTaskModal.showModal()"
            class="bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition flex items-center gap-2 shadow-sm">
            <x-heroicon-o-plus class="w-4 h-4" /> New Task
        </button>
    </div>

</div>
@php
    $recurringGroups = $groups->where('is_recurring', true);
@endphp

@if ($recurringGroups->isNotEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-1.5 mb-3">
        <x-heroicon-o-arrow-path class="w-4 h-4 text-primary-500" /> Recurring Schedules
    </h3>
    <div class="flex flex-wrap gap-2">
        @foreach ($recurringGroups as $group)
        <form method="POST" action="{{ route('task-groups.regenerate', $group) }}"
            onsubmit="return confirm('Generate next week\'s tasks for {{ $group->title }}?');">
            @csrf
            <button type="submit"
                class="flex items-center gap-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-lg px-3 py-2 transition">
                <x-heroicon-o-tag class="w-3.5 h-3.5" />
                {{ $group->title }}
                <span class="text-primary-400">·</span>
                <span class="text-primary-600">Generate Next Week</span>
            </button>
        </form>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">

    @php
    $columnMeta = [
    'todo' => ['label' => 'To Do', 'color' => 'bg-gray-100 text-gray-600', 'dot' => 'bg-gray-400'],
    'in_progress' => ['label' => 'In Progress', 'color' => 'bg-blue-100 text-blue-600', 'dot' => 'bg-blue-500'],
    'review' => ['label' => 'Review', 'color' => 'bg-amber-100 text-amber-600', 'dot' => 'bg-amber-500'],
    'completed' => ['label' => 'Completed', 'color' => 'bg-green-100 text-green-600', 'dot' => 'bg-green-500'],
    ];
    $fileIcons = [
    'jpg' => 'photo', 'jpeg' => 'photo', 'png' => 'photo', 'webp' => 'photo',
    'pdf' => 'document-text',
    'doc' => 'document', 'docx' => 'document',
    'xls' => 'table-cells', 'xlsx' => 'table-cells',
    ];
    $imageExts = ['jpg', 'jpeg', 'png', 'webp'];
    @endphp

    @foreach ($columnMeta as $key => $meta)
    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <span class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full {{ $meta['color'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                {{ $meta['label'] }}
            </span>
            <span class="text-xs text-gray-400 font-medium">{{ $columns[$key]->count() }}</span>
        </div>

        <div class="space-y-3">
            @forelse ($columns[$key] as $task)
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition"
                data-task-card
                data-task-title="{{ $task->title }}"
                data-task-group="{{ $task->group->title ?? '' }}"
                data-task-group-id="{{ $task->group_id }}">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-gray-800 leading-snug line-clamp-2">{{ $task->title }}</p>
                    <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1.5
                                    {{ $task->priority === 'high' ? 'bg-red-500' : ($task->priority === 'medium' ? 'bg-amber-400' : 'bg-gray-300') }}">
                    </span>
                </div>

                @if ($task->description)
                <p class="text-xs text-gray-500 mt-1.5 line-clamp-2">{{ $task->description }}</p>
                @endif

                @if ($task->due_date)
                <p class="text-xs mt-2.5 flex items-center gap-1.5 {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                    <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                    {{ \Carbon\Carbon::parse($task->due_date)->format('d M, h:i A') }}
                    @if ($task->isOverdue())
                    <span class="inline-flex items-center gap-1 bg-red-100 text-red-600 text-[10px] font-semibold px-1.5 py-0.5 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-3 h-3" /> Overdue
                    </span>
                    @endif
                </p>
                @endif

                @if ($task->location_name)
                <p class="text-xs text-primary-600 mt-2.5 flex items-center gap-1.5 truncate">
                    <x-heroicon-o-map-pin class="w-3.5 h-3.5 flex-shrink-0" />
                    <span class="truncate">{{ $task->location_name }}</span>
                </p>
                @endif

                @if ($task->attachments->isNotEmpty())
                <p class="text-xs text-primary-600 mt-2.5 flex items-center gap-1.5">
                    <x-heroicon-o-paper-clip class="w-3.5 h-3.5" />
                    {{ $task->attachments->count() }} {{ Str::plural('file', $task->attachments->count()) }} attached
                </p>
                @endif

                @if ($task->workspace)
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold mt-2.5 px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">
                    <x-heroicon-o-building-office-2 class="w-3 h-3" /> {{ $task->workspace->name }}
                </span>
                @endif

                @if ($task->group)
                <span class="inline-flex items-center gap-1 text-[10px] font-medium mt-2.5 px-2 py-0.5 rounded-full"
                    style="background-color: {{ $task->group->color }}20; color: {{ $task->group->color }}">
                    <x-heroicon-o-tag class="w-3 h-3" /> {{ $task->group->title }}
                </span>
                @endif

                @php
                    $cardCreatorRole = ($task->workspace && $task->creator_id) ? $task->workspace->userRole($task->creator_id) : null;
                @endphp
                <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500 pt-2 border-t border-gray-50">
                    <span class="flex items-center gap-1 text-[11px] truncate">
                        <x-heroicon-o-user class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                        By <strong class="font-semibold text-gray-700 truncate">{{ $task->creator->name ?? 'User' }}</strong>
                    </span>
                    @if ($cardCreatorRole)
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider flex-shrink-0
                            {{ $cardCreatorRole === 'owner' ? 'bg-purple-100 text-purple-700 border border-purple-200' : ($cardCreatorRole === 'manager' ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : 'bg-gray-100 text-gray-600') }}">
                            {{ $cardCreatorRole }}
                        </span>
                    @endif
                </div>

                <div class="mt-3.5 pt-3.5 border-t border-gray-50 space-y-2.5">
                    @php
                        $user = auth()->user();
                        $canManageTask = false;
                        if ($task->workspace_id && $task->workspace) {
                            $role = $task->workspace->userRole($user);
                            if (in_array($role, ['owner', 'manager']) || $task->creator_id === $user->id || $user->isAdmin()) {
                                $canManageTask = true;
                            }
                        } else {
                            if ($task->creator_id === $user->id || $task->assignee_id === $user->id || $user->isAdmin()) {
                                $canManageTask = true;
                            }
                        }
                    @endphp

                    <div class="flex items-center gap-2">
                        <button type="button" @click="$refs.viewTask{{ $task->id }}.showModal()"
                            class="flex items-center gap-1.5 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-md px-2.5 py-1.5 transition">
                            <x-heroicon-o-eye class="w-3.5 h-3.5" /> View
                        </button>

                        @if ($canManageTask)
                            <button type="button" @click="$refs.editTask{{ $task->id }}.showModal()"
                                class="flex items-center gap-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-md px-2.5 py-1.5 transition">
                                <x-heroicon-o-pencil-square class="w-3.5 h-3.5" /> Edit
                            </button>
                            <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                                onsubmit="return confirm('Delete this task?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex items-center gap-1.5 text-xs font-medium text-red-500 bg-red-50 hover:bg-red-100 rounded-md px-2.5 py-1.5 transition">
                                    <x-heroicon-o-trash class="w-3.5 h-3.5" /> Delete
                                </button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('tasks.updateStatus', $task) }}">
                        @csrf
                        @method('PATCH')
                        <select name="status" onchange="this.form.submit()"
                            class="w-full text-xs border border-gray-200 rounded-md px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                            @foreach ($columnMeta as $sKey => $sMeta)
                            <option value="{{ $sKey }}" {{ $task->status === $sKey ? 'selected' : '' }}>
                                {{ $sMeta['label'] }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            {{-- View Task Modal --}}
            <dialog x-ref="viewTask{{ $task->id }}" class="rounded-2xl p-0 backdrop:bg-black/40 w-full max-w-2xl m-auto overflow-hidden max-h-[90vh]">
                <div class="flex flex-col max-h-[90vh]">

                    <div class="bg-gradient-to-br from-primary-600 to-primary-700 px-7 py-5 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                {{ $meta['label'] }}
                            </span>
                            <button type="button" onclick="this.closest('dialog').close()"
                                class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                        <h3 class="text-white text-lg font-semibold mt-3">{{ $task->title }}</h3>
                    </div>

                    <div class="p-7 overflow-y-auto space-y-6">

                        <div class="flex flex-wrap gap-4 text-sm">
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <span class="w-2 h-2 rounded-full flex-shrink-0
                                    {{ $task->priority === 'high' ? 'bg-red-500' : ($task->priority === 'medium' ? 'bg-amber-400' : 'bg-gray-300') }}"></span>
                                Priority: <span class="font-medium text-gray-700 capitalize">{{ $task->priority }}</span>
                            </div>
                            @if ($task->due_date)
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <x-heroicon-o-calendar class="w-4 h-4" />
                                {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y, h:i A') }}
                            </div>
                            @endif
                        </div>

                        {{-- Task Creator & Workspace Role Info Box --}}
                        @php
                            $modalCreatorRole = ($task->workspace && $task->creator_id) ? $task->workspace->userRole($task->creator_id) : null;
                        @endphp
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 font-bold flex items-center justify-center text-xs">
                                    {{ strtoupper(substr($task->creator->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-medium">Created By</p>
                                    <p class="text-xs font-bold text-gray-900">{{ $task->creator->name ?? 'Unknown' }} <span class="text-gray-500 font-normal">({{ $task->creator->email ?? '' }})</span></p>
                                </div>
                            </div>
                            @if ($modalCreatorRole)
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                        {{ $modalCreatorRole === 'owner' ? 'bg-purple-100 text-purple-700 border border-purple-200' : ($modalCreatorRole === 'manager' ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : 'bg-gray-100 text-gray-700 border border-gray-200') }}">
                                        {{ $modalCreatorRole }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if ($task->description)
                        <div>
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Description</h4>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $task->description }}</p>
                        </div>
                        @endif

                        @if ($task->latitude && $task->longitude)
                        <div>
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Location</h4>
                            <a href="https://www.openstreetmap.org/?mlat={{ $task->latitude }}&mlon={{ $task->longitude }}#map=16/{{ $task->latitude }}/{{ $task->longitude }}"
                                target="_blank"
                                class="flex items-center gap-2.5 bg-gray-50 border border-gray-100 rounded-lg px-3.5 py-2.5 hover:border-primary-300 transition">
                                <x-heroicon-o-map-pin class="w-4 h-4 text-primary-500 flex-shrink-0" />
                                <span class="text-sm text-gray-700 truncate flex-1">{{ $task->location_name ?: 'View on map' }}</span>
                                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 text-gray-300 flex-shrink-0" />
                            </a>
                        </div>
                        @endif

                        <div>
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Timeline</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-plus class="w-3.5 h-3.5 text-gray-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">Created</p>
                                        <p class="text-xs text-gray-400">{{ $task->created_at->format('d M Y, h:i A') }}</p>
                                    </div>
                                </div>

                                @if ($task->started_at)
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-play class="w-3.5 h-3.5 text-blue-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">Started</p>
                                        <p class="text-xs text-gray-400">{{ $task->started_at->format('d M Y, h:i A') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if ($task->completed_at)
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-check class="w-3.5 h-3.5 text-green-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">Completed</p>
                                        <p class="text-xs text-gray-400">{{ $task->completed_at->format('d M Y, h:i A') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if ($task->isOverdue())
                                <div class="flex items-center gap-2 text-red-600 bg-red-50 rounded-lg px-3 py-2 text-xs font-medium mt-2">
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                                    This task is overdue
                                </div>
                                @endif
                            </div>
                        </div>

                        @if ($task->attachments->isNotEmpty())
                        <div>
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                                Attachments ({{ $task->attachments->count() }})
                            </h4>

                            @php
                            $images = $task->attachments->filter(fn($a) => in_array(strtolower($a->file_type), $imageExts));
                            $docs = $task->attachments->reject(fn($a) => in_array(strtolower($a->file_type), $imageExts));
                            @endphp

                            @if ($images->isNotEmpty())
                            <div class="grid grid-cols-3 gap-3 mb-3">
                                @foreach ($images as $img)
                                <a href="{{ asset('storage/' . $img->file_path) }}" target="_blank" class="group block">
                                    <div class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                                        <img src="{{ asset('storage/' . $img->file_path) }}" alt="{{ $img->file_name }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition">
                                    </div>
                                    <p class="text-[11px] text-gray-500 truncate mt-1">{{ $img->file_name }}</p>
                                </a>
                                @endforeach
                            </div>
                            @endif

                            @if ($docs->isNotEmpty())
                            <div class="space-y-1.5">
                                @foreach ($docs as $doc)
                                <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank"
                                    class="flex items-center gap-2.5 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 hover:border-primary-300 transition">
                                    <x-dynamic-component :component="'heroicon-o-' . ($fileIcons[strtolower($doc->file_type)] ?? 'paper-clip')"
                                        class="w-4 h-4 text-primary-500 flex-shrink-0" />
                                    <span class="text-sm text-gray-700 truncate flex-1">{{ $doc->file_name }}</span>
                                    <span class="text-[11px] text-gray-400 flex-shrink-0">{{ $doc->formattedSize() }}</span>
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-gray-300 flex-shrink-0" />
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="flex items-center gap-2 text-sm text-gray-400 bg-gray-50 rounded-lg px-4 py-3">
                            <x-heroicon-o-paper-clip class="w-4 h-4" />
                            No files attached to this task.
                        </div>
                        @endif

                    </div>

                    <div class="px-7 py-4 border-t border-gray-100 flex-shrink-0 flex gap-3">
                        <button type="button" onclick="this.closest('dialog').close()"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg py-2.5 transition">
                            Close
                        </button>
                        @if ($canManageTask)
                        <button type="button"
                            @click="$refs.viewTask{{ $task->id }}.close(); $refs.editTask{{ $task->id }}.showModal();"
                            class="flex-1 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg py-2.5 transition flex items-center justify-center gap-2">
                            <x-heroicon-o-pencil-square class="w-4 h-4" /> Edit Task
                        </button>
                        @endif
                    </div>
                </div>
            </dialog>

            {{-- Edit Task Modal --}}
            <dialog x-ref="editTask{{ $task->id }}" class="rounded-2xl p-0 backdrop:bg-black/40 w-full max-w-2xl m-auto overflow-hidden max-h-[90vh]">
                <div x-data="{
                    showMap: false,
                    latitude: {{ $task->latitude ?? 'null' }},
                    longitude: {{ $task->longitude ?? 'null' }},
                    locationName: @js($task->location_name ?? ''),
                    userLat: null,
                    userLng: null,
                    mapObj: null,
                    markerObj: null,
                    userMarker: null,
                    searchQuery: '',
                    searchResults: [],
                    searching: false,
                    locating: false,
                    distanceInfo: null,
                    files: [],
                    maxFiles: {{ max(3 - $task->attachments->count(), 0) }},
                    maxSize: 5 * 1024 * 1024,
                    error: '',
                    addFiles(fileList) {
                        this.error = '';
                        let incoming = Array.from(fileList);

                        if (this.maxFiles <= 0) {
                            this.error = 'This task already has the maximum of 3 attachments.';
                            return;
                        }

                        if (this.files.length + incoming.length > this.maxFiles) {
                            this.error = 'You can add up to ' + this.maxFiles + ' more file(s).';
                            incoming = incoming.slice(0, this.maxFiles - this.files.length);
                        }

                        for (const f of incoming) {
                            if (f.size > this.maxSize) {
                                this.error = f.name + ' is over 5MB.';
                                continue;
                            }
                            this.files.push(f);
                        }
                        this.syncInput();
                    },
                    removeFile(index) {
                        this.files.splice(index, 1);
                        this.syncInput();
                    },
                    syncInput() {
                        const dt = new DataTransfer();
                        this.files.forEach(f => dt.items.add(f));
                        this.$refs.editFileInput.files = dt.files;
                    },
                    formatSize(bytes) {
                        const kb = bytes / 1024;
                        return kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB';
                    },
                    toggleMap() {
                        this.showMap = !this.showMap;
                        if (this.showMap) {
                            this.$nextTick(() => {
                                this.initMap();
                                this.locateMe(true);
                            });
                        } else if (this.mapObj) {
                            this.mapObj.remove();
                            this.mapObj = null;
                            this.markerObj = null;
                            this.userMarker = null;
                        }
                    },
                    initMap() {
                        const startLat = this.latitude || 26.2389;
                        const startLng = this.longitude || 73.0243;
                        this.mapObj = L.map(this.$refs.mapContainer).setView([startLat, startLng], this.latitude ? 15 : 12);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 19,
                        }).addTo(this.mapObj);

                        if (this.latitude && this.longitude) {
                            this.markerObj = L.marker([this.latitude, this.longitude]).addTo(this.mapObj);
                        }

                        this.mapObj.on('click', (e) => this.setPin(e.latlng.lat, e.latlng.lng));
                        setTimeout(() => this.mapObj.invalidateSize(), 150);
                    },
                    setPin(lat, lng, skipGeocode) {
                        this.latitude = lat;
                        this.longitude = lng;

                        if (this.markerObj) {
                            this.markerObj.setLatLng([lat, lng]);
                        } else {
                            this.markerObj = L.marker([lat, lng]).addTo(this.mapObj);
                        }

                        this.mapObj.setView([lat, lng], this.mapObj.getZoom() < 13 ? 15 : this.mapObj.getZoom());
                        if (!skipGeocode) this.reverseGeocode(lat, lng);
                        this.calculateRoute();
                    },
                    async reverseGeocode(lat, lng) {
                        this.locationName = 'Looking up address…';
                        try {
                            const res = await fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng);
                            const data = await res.json();
                            this.locationName = data.display_name || (lat.toFixed(5) + ', ' + lng.toFixed(5));
                        } catch (e) {
                            this.locationName = lat.toFixed(5) + ', ' + lng.toFixed(5);
                        }
                    },
                    async searchPlace() {
                        if (!this.searchQuery.trim()) return;
                        this.searching = true;
                        this.searchResults = [];
                        try {
                            const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(this.searchQuery) + '&limit=5');
                            this.searchResults = await res.json();
                        } catch (e) {
                            this.searchResults = [];
                        } finally {
                            this.searching = false;
                        }
                    },
                    selectSearchResult(result) {
                        this.locationName = result.display_name;
                        this.setPin(parseFloat(result.lat), parseFloat(result.lon), true);
                        this.searchResults = [];
                        this.searchQuery = '';
                    },
                    locateMe(silent) {
                        if (!navigator.geolocation) {
                            if (!silent) this.locationName = 'Geolocation not supported by your browser.';
                            return;
                        }
                        this.locating = true;
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                this.locating = false;
                                this.userLat = pos.coords.latitude;
                                this.userLng = pos.coords.longitude;

                                const userIcon = L.divIcon({
                                    className: '',
                                    html: '<div style=\'width:14px;height:14px;background:#2563eb;border:3px solid white;border-radius:50%;box-shadow:0 0 0 5px rgba(37,99,235,0.25);\'></div>',
                                    iconSize: [14, 14],
                                    iconAnchor: [7, 7],
                                });

                                if (this.userMarker) {
                                    this.userMarker.setLatLng([this.userLat, this.userLng]);
                                } else if (this.mapObj) {
                                    this.userMarker = L.marker([this.userLat, this.userLng], { icon: userIcon }).addTo(this.mapObj).bindPopup('You are here');
                                }

                                if (!this.latitude && this.mapObj) {
                                    this.mapObj.setView([this.userLat, this.userLng], 13);
                                }

                                this.calculateRoute();
                            },
                            () => {
                                this.locating = false;
                                if (!silent) this.locationName = 'Could not get your location.';
                            }
                        );
                    },
                    async calculateRoute() {
                        if (!this.latitude || !this.longitude || !this.userLat || !this.userLng) {
                            this.distanceInfo = null;
                            return;
                        }
                        try {
                            const url = 'https://router.project-osrm.org/route/v1/foot/' + this.userLng + ',' + this.userLat + ';' + this.longitude + ',' + this.latitude + '?overview=false';
                            const res = await fetch(url);
                            const data = await res.json();
                            if (data.routes && data.routes[0]) {
                                const km = (data.routes[0].distance / 1000).toFixed(1);
                                const mins = Math.round(data.routes[0].duration / 60);
                                this.distanceInfo = km + ' km away · ~' + mins + ' min walk';
                            } else {
                                this.distanceInfo = null;
                            }
                        } catch (e) {
                            this.distanceInfo = null;
                        }
                    }
                }"
                    class="flex flex-col max-h-[90vh]">

                    <div class="bg-gradient-to-br from-amber-500 to-amber-600 px-7 py-5 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-full">
                                <x-heroicon-o-pencil-square class="w-4 h-4" /> Edit Task
                            </span>
                            <button type="button" onclick="this.closest('dialog').close()"
                                class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                        <h3 class="text-white text-lg font-semibold mt-3">{{ $task->title }}</h3>
                    </div>

                    <div class="p-7 overflow-y-auto">
                        <form method="POST" action="{{ route('tasks.update', $task) }}" enctype="multipart/form-data" class="space-y-5">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                                <input type="text" name="title" required value="{{ $task->title }}"
                                    class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                                <textarea name="description" rows="3"
                                    class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">{{ $task->description }}</textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                                    <select name="priority" class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                        <option value="low" {{ $task->priority === 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ $task->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ $task->priority === 'high' ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
                                    <input type="datetime-local" name="due_date"
                                        value="{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d\TH:i') : '' }}"
                                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                </div>
                            </div>

                            {{-- Location --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-sm font-medium text-gray-700 flex items-center gap-1.5">
                                        <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400" /> Location <span class="text-gray-400 font-normal">(optional)</span>
                                    </label>
                                    <button type="button" @click="toggleMap()" class="text-xs text-primary-600 font-medium hover:underline" x-text="showMap ? 'Hide map' : (locationName ? 'Edit location' : '+ Add location')"></button>
                                </div>

                                <template x-if="!showMap && locationName">
                                    <p class="text-xs text-gray-500 flex items-center gap-1.5 bg-gray-50 rounded-lg px-3 py-2">
                                        <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-primary-500 flex-shrink-0" />
                                        <span x-text="locationName" class="truncate"></span>
                                    </p>
                                </template>

                                <template x-if="showMap">
                                    <div class="space-y-2 mt-1">
                                        <div class="relative">
                                            <div class="flex gap-2">
                                                <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchPlace()"
                                                    placeholder="Search a place… e.g. Mehrangarh Fort"
                                                    class="flex-1 text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500">
                                                <button type="button" @click="searchPlace()" :disabled="searching"
                                                    class="bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 text-white rounded-lg px-3.5 flex items-center justify-center flex-shrink-0">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <template x-if="searchResults.length > 0">
                                                <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                                    <template x-for="result in searchResults" :key="result.place_id">
                                                        <button type="button" @click="selectSearchResult(result)"
                                                            class="w-full text-left px-3 py-2 text-xs text-gray-600 hover:bg-amber-50 border-b border-gray-50 last:border-0 truncate">
                                                            <span x-text="result.display_name"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        <button type="button" @click="locateMe(false)" :disabled="locating"
                                            class="flex items-center gap-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 disabled:opacity-50 rounded-md px-3 py-2 transition">
                                            <x-heroicon-o-cursor-arrow-rays class="w-3.5 h-3.5" />
                                            <span x-text="locating ? 'Locating…' : 'Show my location on map'"></span>
                                        </button>

                                        <div x-ref="mapContainer" class="w-full h-56 rounded-lg border border-gray-200"></div>

                                        <p class="text-xs text-gray-500 flex items-start gap-1" x-show="locationName">
                                            <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-amber-500 flex-shrink-0 mt-0.5" />
                                            <span x-text="locationName"></span>
                                        </p>
                                        <p class="text-xs text-gray-400" x-show="!locationName">Search a place, click the map, or drop a pin to set the task location.</p>

                                        <p class="text-xs text-blue-700 flex items-center gap-1.5 bg-blue-50 rounded-lg px-3 py-2" x-show="distanceInfo">
                                            <x-heroicon-o-map class="w-3.5 h-3.5 flex-shrink-0" />
                                            <span x-text="distanceInfo"></span>
                                        </p>
                                    </div>
                                </template>

                                <input type="hidden" name="latitude" :value="latitude">
                                <input type="hidden" name="longitude" :value="longitude">
                                <input type="hidden" name="location_name" :value="locationName">
                            </div>

                            @if ($task->attachments->isNotEmpty())
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Current Attachments ({{ $task->attachments->count() }}/3)
                                </label>
                                <div class="space-y-1.5">
                                    @foreach ($task->attachments as $attachment)
                                    <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                        <x-dynamic-component :component="'heroicon-o-' . ($fileIcons[strtolower($attachment->file_type)] ?? 'paper-clip')"
                                            class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                        <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                            class="text-sm text-gray-600 hover:text-primary-600 truncate flex-1">
                                            {{ $attachment->file_name }}
                                        </a>
                                        <span class="text-[11px] text-gray-400 flex-shrink-0">{{ $attachment->formattedSize() }}</span>
                                        <button type="submit" form="delete-attachment-{{ $attachment->id }}"
                                            onclick="return confirm('Remove this file?');" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Add More Files</label>

                                <template x-if="maxFiles > 0">
                                    <div>
                                        <label :for="'edit-files-{{ $task->id }}'"
                                            class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed border-gray-200 rounded-xl py-5 cursor-pointer hover:border-amber-400 hover:bg-amber-50/40 transition">
                                            <x-heroicon-o-paper-clip class="w-5 h-5 text-amber-500" />
                                            <span class="text-sm text-gray-600 font-medium">Click to attach files</span>
                                            <span class="text-xs text-gray-400" x-text="'Up to ' + maxFiles + ' more · 5MB each'"></span>
                                        </label>
                                        <input type="file" id="edit-files-{{ $task->id }}" name="attachments[]" x-ref="editFileInput" multiple class="hidden"
                                            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                                            @change="addFiles($event.target.files)">
                                    </div>
                                </template>

                                <template x-if="maxFiles <= 0">
                                    <p class="text-xs text-gray-400 bg-gray-50 rounded-lg px-4 py-3">
                                        Maximum of 3 attachments reached. Remove one above to add another.
                                    </p>
                                </template>

                                <template x-if="error">
                                    <p class="text-xs text-red-500 mt-2 flex items-center gap-1">
                                        <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5" />
                                        <span x-text="error"></span>
                                    </p>
                                </template>

                                <div class="space-y-1.5 mt-2.5" x-show="files.length > 0">
                                    <template x-for="(file, index) in files" :key="file.name + index">
                                        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                            <x-heroicon-o-document class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            <span class="text-xs text-gray-600 truncate flex-1" x-text="file.name"></span>
                                            <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="formatSize(file.size)"></span>
                                            <button type="button" @click="removeFile(index)" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" onclick="this.closest('dialog').close()"
                                    class="flex-1 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg py-2.5 hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="flex-1 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg py-2.5 transition flex items-center justify-center gap-2">
                                    <x-heroicon-o-check class="w-4 h-4" /> Save Changes
                                </button>
                            </div>
                        </form>

                        {{-- Standalone delete forms (kept outside the main form to avoid HTML nested-form issues) --}}
                        @foreach ($task->attachments as $attachment)
                        <form id="delete-attachment-{{ $attachment->id }}" method="POST" action="{{ route('task-attachments.destroy', $attachment) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endforeach
                    </div>
                </div>
            </dialog>

            @empty
            <div class="text-center py-10">
                <x-heroicon-o-inbox class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                <p class="text-xs text-gray-400">No tasks</p>
            </div>
            @endforelse
        </div>
    </div>
    @endforeach

</div>

{{-- Add Task Modal --}}
<dialog x-ref="addTaskModal" class="rounded-2xl p-0 backdrop:bg-black/40 w-full max-w-2xl m-auto overflow-hidden max-h-[90vh]">
    <div x-data="{
        files: [],
        maxFiles: 3,
        maxSize: 5 * 1024 * 1024,
        error: '',
        addFiles(fileList) {
            this.error = '';
            let incoming = Array.from(fileList);

            if (this.files.length + incoming.length > this.maxFiles) {
                this.error = 'You can attach up to ' + this.maxFiles + ' files.';
                incoming = incoming.slice(0, this.maxFiles - this.files.length);
            }

            for (const f of incoming) {
                if (f.size > this.maxSize) {
                    this.error = f.name + ' is over 5MB.';
                    continue;
                }
                this.files.push(f);
            }
            this.syncInput();
        },
        removeFile(index) {
            this.files.splice(index, 1);
            this.syncInput();
        },
        syncInput() {
            const dt = new DataTransfer();
            this.files.forEach(f => dt.items.add(f));
            this.$refs.fileInput.files = dt.files;
        },
        formatSize(bytes) {
            const kb = bytes / 1024;
            return kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB';
        },

        showMap: false,
        latitude: null,
        longitude: null,
        locationName: '',
        userLat: null,
        userLng: null,
        mapObj: null,
        markerObj: null,
        userMarker: null,
        searchQuery: '',
        searchResults: [],
        searching: false,
        locating: false,
        distanceInfo: null,

        toggleMap() {
            this.showMap = !this.showMap;
            if (this.showMap) {
                this.$nextTick(() => {
                    this.initMap();
                    this.locateMe(true);
                });
            } else if (this.mapObj) {
                this.mapObj.remove();
                this.mapObj = null;
                this.markerObj = null;
                this.userMarker = null;
            }
        },
        initMap() {
            const startLat = this.latitude || 26.2389;
            const startLng = this.longitude || 73.0243;
            this.mapObj = L.map(this.$refs.mapContainer).setView([startLat, startLng], this.latitude ? 15 : 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(this.mapObj);

            if (this.latitude && this.longitude) {
                this.markerObj = L.marker([this.latitude, this.longitude]).addTo(this.mapObj);
            }

            this.mapObj.on('click', (e) => this.setPin(e.latlng.lat, e.latlng.lng));
            setTimeout(() => this.mapObj.invalidateSize(), 150);
        },
        setPin(lat, lng, skipGeocode) {
            this.latitude = lat;
            this.longitude = lng;

            if (this.markerObj) {
                this.markerObj.setLatLng([lat, lng]);
            } else {
                this.markerObj = L.marker([lat, lng]).addTo(this.mapObj);
            }

            this.mapObj.setView([lat, lng], this.mapObj.getZoom() < 13 ? 15 : this.mapObj.getZoom());
            if (!skipGeocode) this.reverseGeocode(lat, lng);
            this.calculateRoute();
        },
        async reverseGeocode(lat, lng) {
            this.locationName = 'Looking up address…';
            try {
                const res = await fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng);
                const data = await res.json();
                this.locationName = data.display_name || (lat.toFixed(5) + ', ' + lng.toFixed(5));
            } catch (e) {
                this.locationName = lat.toFixed(5) + ', ' + lng.toFixed(5);
            }
        },
        async searchPlace() {
            if (!this.searchQuery.trim()) return;
            this.searching = true;
            this.searchResults = [];
            try {
                const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(this.searchQuery) + '&limit=5');
                this.searchResults = await res.json();
            } catch (e) {
                this.searchResults = [];
            } finally {
                this.searching = false;
            }
        },
        selectSearchResult(result) {
            this.locationName = result.display_name;
            this.setPin(parseFloat(result.lat), parseFloat(result.lon), true);
            this.searchResults = [];
            this.searchQuery = '';
        },
        locateMe(silent) {
            if (!navigator.geolocation) {
                if (!silent) this.locationName = 'Geolocation not supported by your browser.';
                return;
            }
            this.locating = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.locating = false;
                    this.userLat = pos.coords.latitude;
                    this.userLng = pos.coords.longitude;

                    const userIcon = L.divIcon({
                        className: '',
                        html: '<div style=\'width:14px;height:14px;background:#2563eb;border:3px solid white;border-radius:50%;box-shadow:0 0 0 5px rgba(37,99,235,0.25);\'></div>',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7],
                    });

                    if (this.userMarker) {
                        this.userMarker.setLatLng([this.userLat, this.userLng]);
                    } else if (this.mapObj) {
                        this.userMarker = L.marker([this.userLat, this.userLng], { icon: userIcon }).addTo(this.mapObj).bindPopup('You are here');
                    }

                    if (!this.latitude && this.mapObj) {
                        this.mapObj.setView([this.userLat, this.userLng], 13);
                    }

                    this.calculateRoute();
                },
                () => {
                    this.locating = false;
                    if (!silent) this.locationName = 'Could not get your location.';
                }
            );
        },
        async calculateRoute() {
            if (!this.latitude || !this.longitude || !this.userLat || !this.userLng) {
                this.distanceInfo = null;
                return;
            }
            try {
                const url = 'https://router.project-osrm.org/route/v1/foot/' + this.userLng + ',' + this.userLat + ';' + this.longitude + ',' + this.latitude + '?overview=false';
                const res = await fetch(url);
                const data = await res.json();
                if (data.routes && data.routes[0]) {
                    const km = (data.routes[0].distance / 1000).toFixed(1);
                    const mins = Math.round(data.routes[0].duration / 60);
                    this.distanceInfo = km + ' km away · ~' + mins + ' min walk';
                } else {
                    this.distanceInfo = null;
                }
            } catch (e) {
                this.distanceInfo = null;
            }
        }
    }"
        class="flex flex-col max-h-[90vh]">

        <div class="bg-gradient-to-br from-primary-600 to-primary-700 px-7 py-5 flex-shrink-0">
            <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-full mb-3">
                <x-heroicon-o-clipboard-document-list class="w-4 h-4" /> New Task
            </div>
            <h3 class="text-white text-lg font-semibold">Create a New Task</h3>
            <p class="text-primary-100 text-sm mt-1">Fill in the details and attach anything you'll need.</p>
        </div>

        <div class="p-7 overflow-y-auto">
            <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                    <input type="text" name="title" required placeholder="e.g. Finish client proposal"
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                    <textarea name="description" rows="3" placeholder="Any extra detail worth noting..."
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"></textarea>
                </div>

                @if (isset($workspaces) && $workspaces->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Workspace <span class="text-gray-400 font-normal">(optional)</span></label>
                    <select name="workspace_id" class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition bg-white">
                        <option value="">Personal Task (No Workspace)</option>
                        @foreach ($workspaces as $ws)
                            <option value="{{ $ws->id }}" {{ isset($selectedWorkspaceId) && $selectedWorkspaceId == $ws->id ? 'selected' : '' }}>{{ $ws->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                        <select name="priority" class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
                        <input type="datetime-local" name="due_date"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    </div>
                </div>

                {{-- Location --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-sm font-medium text-gray-700 flex items-center gap-1.5">
                            <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400" /> Location <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <button type="button" @click="toggleMap()" class="text-xs text-primary-600 font-medium hover:underline" x-text="showMap ? 'Hide map' : (locationName ? 'Edit location' : '+ Add location')"></button>
                    </div>

                    <template x-if="!showMap && locationName">
                        <p class="text-xs text-gray-500 flex items-center gap-1.5 bg-gray-50 rounded-lg px-3 py-2">
                            <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-primary-500 flex-shrink-0" />
                            <span x-text="locationName" class="truncate"></span>
                        </p>
                    </template>

                    <template x-if="showMap">
                        <div class="space-y-2">
                            <div class="relative">
                                <div class="flex gap-2">
                                    <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchPlace()"
                                        placeholder="Search a place… e.g. Mehrangarh Fort"
                                        class="flex-1 text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <button type="button" @click="searchPlace()" :disabled="searching"
                                        class="bg-primary-500 hover:bg-primary-600 disabled:bg-gray-300 text-white rounded-lg px-3.5 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                </div>
                                <template x-if="searchResults.length > 0">
                                    <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                        <template x-for="result in searchResults" :key="result.place_id">
                                            <button type="button" @click="selectSearchResult(result)"
                                                class="w-full text-left px-3 py-2 text-xs text-gray-600 hover:bg-primary-50 border-b border-gray-50 last:border-0 truncate">
                                                <span x-text="result.display_name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="locateMe(false)" :disabled="locating"
                                class="flex items-center gap-1.5 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 disabled:opacity-50 rounded-md px-3 py-2 transition">
                                <x-heroicon-o-cursor-arrow-rays class="w-3.5 h-3.5" />
                                <span x-text="locating ? 'Locating…' : 'Show my location on map'"></span>
                            </button>

                            <div x-ref="mapContainer" class="w-full h-56 rounded-lg border border-gray-200"></div>

                            <p class="text-xs text-gray-500 flex items-start gap-1" x-show="locationName">
                                <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-primary-500 flex-shrink-0 mt-0.5" />
                                <span x-text="locationName"></span>
                            </p>
                            <p class="text-xs text-gray-400" x-show="!locationName">Search a place, click the map, or drop a pin to set the task location.</p>

                            <p class="text-xs text-blue-700 flex items-center gap-1.5 bg-blue-50 rounded-lg px-3 py-2" x-show="distanceInfo">
                                <x-heroicon-o-map class="w-3.5 h-3.5 flex-shrink-0" />
                                <span x-text="distanceInfo"></span>
                            </p>
                        </div>
                    </template>

                    <input type="hidden" name="latitude" :value="latitude">
                    <input type="hidden" name="longitude" :value="longitude">
                    <input type="hidden" name="location_name" :value="locationName">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Attachments</label>

                    <label for="task-files"
                        class="flex flex-col items-center justify-center gap-1.5 border-2 border-dashed border-gray-200 rounded-xl py-6 cursor-pointer hover:border-primary-400 hover:bg-primary-50/40 transition">
                        <div class="w-9 h-9 rounded-full bg-primary-50 flex items-center justify-center">
                            <x-heroicon-o-paper-clip class="w-4.5 h-4.5 text-primary-500" />
                        </div>
                        <span class="text-sm text-gray-600 font-medium">Click to attach files</span>
                        <span class="text-xs text-gray-400">Up to 3 files · 5MB each · images, PDF, Word, Excel</span>
                    </label>
                    <input type="file" id="task-files" name="attachments[]" x-ref="fileInput" multiple class="hidden"
                        accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                        @change="addFiles($event.target.files)">

                    <template x-if="error">
                        <p class="text-xs text-red-500 mt-2 flex items-center gap-1">
                            <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5" />
                            <span x-text="error"></span>
                        </p>
                    </template>

                    <div class="space-y-1.5 mt-2.5" x-show="files.length > 0">
                        <template x-for="(file, index) in files" :key="file.name + index">
                            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                <x-heroicon-o-document class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                <span class="text-xs text-gray-600 truncate flex-1" x-text="file.name"></span>
                                <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="formatSize(file.size)"></span>
                                <button type="button" @click="removeFile(index)" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.querySelector('dialog[x-ref=addTaskModal]').close()"
                        class="flex-1 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg py-2.5 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg py-2.5 transition flex items-center justify-center gap-2">
                        <x-heroicon-o-check class="w-4 h-4" /> Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</dialog>

{{-- New Group Modal --}}
<dialog x-ref="newGroupModal" class="rounded-2xl p-0 backdrop:bg-black/40 w-full max-w-2xl m-auto overflow-hidden max-h-[90vh]">
    <div x-data="{
            groupTitle: '',
            workspaceId: '{{ (isset($selectedWorkspaceId) && is_numeric($selectedWorkspaceId)) ? $selectedWorkspaceId : '' }}',
            isRecurring: false,
            planText: '',
            files: [],
            maxFiles: 3,
            maxSize: 5 * 1024 * 1024,
            fileError: '',
            loading: false,
            error: null,
            result: null,
            addFiles(fileList) {
                this.fileError = '';
                let incoming = Array.from(fileList);

                if (this.files.length + incoming.length > this.maxFiles) {
                    this.fileError = 'You can attach up to ' + this.maxFiles + ' files.';
                    incoming = incoming.slice(0, this.maxFiles - this.files.length);
                }

                for (const f of incoming) {
                    if (f.size > this.maxSize) {
                        this.fileError = f.name + ' is over 5MB.';
                        continue;
                    }
                    this.files.push(f);
                }
                this.syncInput();
            },
            removeFile(index) {
                this.files.splice(index, 1);
                this.syncInput();
            },
            syncInput() {
                const dt = new DataTransfer();
                this.files.forEach(f => dt.items.add(f));
                this.$refs.groupFileInput.files = dt.files;
            },
            formatSize(bytes) {
                const kb = bytes / 1024;
                return kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB';
            },
            async generate() {
                if (!this.groupTitle.trim() || !this.planText.trim()) return;
                this.loading = true;
                this.error = null;
                this.result = null;

                const formData = new FormData();
                formData.append('plan_text', this.planText);
                formData.append('is_recurring', this.isRecurring ? '1' : '0');
                if (this.workspaceId) formData.append('workspace_id', this.workspaceId);
                this.files.forEach(f => formData.append('attachments[]', f));
                formData.append('group_title', this.groupTitle);

                try {
                    const response = await fetch('{{ route('task-groups.generate-ai') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: formData,
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.result = data;
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        this.error = data.message || 'Something went wrong.';
                    }
                } catch (e) {
                    this.error = 'Could not reach the server. Try again.';
                } finally {
                    this.loading = false;
                }
            }
        }" class="flex flex-col max-h-[90vh]">

        <div class="bg-gradient-to-br from-primary-600 to-primary-700 px-7 py-5 flex-shrink-0">
            <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-full mb-3">
                <x-heroicon-o-sparkles class="w-4 h-4" /> New Group
            </div>
            <h3 class="text-white text-lg font-semibold">Create a Task Group with AI</h3>
            <p class="text-primary-100 text-sm mt-1">Describe your plan — gym schedule, trip, event — and AI will break it into tasks.</p>
        </div>

        <div class="p-7 overflow-y-auto space-y-5">
            @if (isset($workspaces) && $workspaces->isNotEmpty())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Target Workspace <span class="text-gray-400 font-normal">(optional)</span></label>
                <select x-model="workspaceId" class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition bg-white">
                    <option value="">Personal Tasks (No Workspace)</option>
                    @foreach ($workspaces as $ws)
                        <option value="{{ $ws->id }}">🏢 {{ $ws->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Group Title</label>
                <input type="text" x-model="groupTitle" placeholder="e.g. Gym Schedule, Goa Trip, Wedding Planning"
                    class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Describe your plan</label>
                <textarea x-model="planText" rows="7" :disabled="loading"
                    placeholder="e.g. Gym schedule: Monday chest+triceps 6pm to 8pm, Tuesday back+biceps 6pm to 8pm, Wednesday leg and shoulder..."
                    class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition resize-none disabled:bg-gray-50"></textarea>
                <p class="text-xs text-gray-400 mt-1.5">Mention days, times, and details — AI will create one task per item.</p>
            </div>

            <label class="flex items-center gap-2.5 bg-primary-50 border border-primary-100 rounded-lg px-4 py-3 cursor-pointer">
                <input type="checkbox" x-model="isRecurring" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <div>
                    <p class="text-sm font-medium text-gray-800">Make this recurring weekly</p>
                    <p class="text-xs text-gray-500">You'll be able to generate next week's tasks with one click.</p>
                </div>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Attachments <span class="text-gray-400 font-normal">(optional)</span></label>

                <label for="group-files"
                    class="flex items-center justify-center gap-1.5 border-2 border-dashed border-gray-200 rounded-lg py-3 cursor-pointer hover:border-primary-400 hover:bg-primary-50/40 transition">
                    <x-heroicon-o-paper-clip class="w-4 h-4 text-gray-400" />
                    <span class="text-xs text-gray-500">Attach files (up to 3 · 5MB each) — will be added to every generated task</span>
                </label>
                <input type="file" id="group-files" x-ref="groupFileInput" multiple class="hidden"
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                    @change="addFiles($event.target.files)">

                <template x-if="fileError">
                    <p class="text-xs text-red-500 mt-2" x-text="fileError"></p>
                </template>

                <div class="space-y-1.5 mt-2.5" x-show="files.length > 0">
                    <template x-for="(file, index) in files" :key="file.name + index">
                        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                            <x-heroicon-o-document class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="text-xs text-gray-600 truncate flex-1" x-text="file.name"></span>
                            <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="formatSize(file.size)"></span>
                            <button type="button" @click="removeFile(index)" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <template x-if="error">
                <div class="flex items-center gap-1.5 text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2">
                    <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" />
                    <span x-text="error"></span>
                </div>
            </template>

            <template x-if="result">
                <div class="flex items-center gap-1.5 text-xs text-green-700 bg-green-50 rounded-lg px-3 py-2">
                    <x-heroicon-o-check-circle class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>Created <strong x-text="result.tasks_created"></strong> tasks in <strong x-text="result.group_title"></strong>. Refreshing…</span>
                </div>
            </template>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="this.closest('dialog').close()"
                    class="flex-1 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg py-2.5 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" @click="generate()" :disabled="loading || !groupTitle.trim() || !planText.trim()"
                    class="flex-1 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-300 text-white text-sm font-medium rounded-lg py-2.5 transition flex items-center justify-center gap-2">
                    <template x-if="loading">
                        <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!loading">
                        <x-heroicon-o-sparkles class="w-4 h-4" />
                    </template>
                    <span x-text="loading ? 'Generating…' : 'Generate Tasks with AI'"></span>
                </button>
            </div>
        </div>
    </div>
</dialog>
@endsection