@extends('layouts.app')

@section('title', 'Dashboard - Ordo')
@section('page-title', 'Dashboard')

@section('content')

@if ($overdueCount > 0)
<div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-5 py-3.5">
    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-500 flex-shrink-0" />
    <p class="text-sm text-red-700">
        You have <strong>{{ $overdueCount }}</strong> {{ Str::plural('task', $overdueCount) }} overdue.
        <a href="{{ route('tasks.board') }}" class="underline font-medium hover:text-red-800">Review now</a>
    </p>
</div>
@endif

@if (isset($incomingJoinRequests) && isset($incomingLeaveRequests) && ($incomingJoinRequests->count() > 0 || $incomingLeaveRequests->count() > 0))
<div class="mb-6 bg-gradient-to-r from-amber-500 to-rose-600 rounded-2xl p-5 text-white shadow-md space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-bold flex items-center gap-2">
            <x-heroicon-o-bell-alert class="w-5 h-5 text-amber-200 animate-pulse" />
            Action Required: Pending Team Applications
        </h3>
        <a href="{{ route('workspaces.index') }}" class="text-xs font-semibold bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg transition">
            View All in Workspaces →
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
        @foreach ($incomingJoinRequests as $req)
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3.5 flex items-center justify-between border border-white/20">
                <div>
                    <p class="text-xs font-bold text-white flex items-center gap-1">
                        <x-heroicon-o-user-plus class="w-3.5 h-3.5 text-amber-300" />
                        {{ $req->user->name }}
                    </p>
                    <p class="text-[11px] text-amber-100">Wants to join <strong>{{ $req->workspace->name }}</strong></p>
                </div>
                <div class="flex items-center gap-1.5">
                    <form action="{{ route('workspaces.join-requests.approve', $req) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md text-[11px] font-bold transition">Approve</button>
                    </form>
                    <form action="{{ route('workspaces.join-requests.reject', $req) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white rounded-md text-[11px] font-medium transition">Reject</button>
                    </form>
                </div>
            </div>
        @endforeach

        @foreach ($incomingLeaveRequests as $req)
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3.5 flex items-center justify-between border border-white/20">
                <div>
                    <p class="text-xs font-bold text-white flex items-center gap-1">
                        <x-heroicon-o-arrow-right-start-on-rectangle class="w-3.5 h-3.5 text-rose-300" />
                        {{ $req->user->name }}
                    </p>
                    <p class="text-[11px] text-rose-100">Leave <strong>{{ $req->workspace->name }}</strong> on {{ $req->leave_date->format('M d') }}</p>
                    <p class="text-[10px] text-white/80 italic mt-0.5">"{{ $req->reason }}"</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <form action="{{ route('workspaces.leave-requests.approve', $req) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md text-[11px] font-bold transition">Approve</button>
                    </form>
                    <form action="{{ route('workspaces.leave-requests.reject', $req) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white rounded-md text-[11px] font-medium transition">Reject</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Welcome Banner --}}
<div class="mb-8 rounded-2xl overflow-hidden relative bg-primary-600 h-52 md:h-64">
    <img src="{{ asset('images/dashboard/welcome-banner.png') }}" alt="Welcome"
        class="absolute inset-0 w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-r from-primary-800/85 via-primary-700/50 to-transparent"></div>
    <div class="absolute inset-0 flex items-center px-6 md:px-10">
        <div class="max-w-sm">
            <h2 class="text-white text-2xl md:text-3xl font-bold drop-shadow-md">Welcome back, {{ auth()->user()->name }} 👋</h2>
            <p class="text-white/90 mt-2 text-sm md:text-base drop-shadow">
                @if(auth()->user()->isBusiness())
                    Here's what your team is working on today.
                @else
                    Here's what's on your plate today.
                @endif
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: Task List --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-primary-600" /> Upcoming Tasks
            </h3>
            <a href="{{ route('tasks.board') }}" class="text-sm text-primary-600 font-medium hover:underline">+ Add task</a>
        </div>

        @forelse ($tasks as $task)
            <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-start justify-between shadow-sm hover:shadow-md transition">
                <div class="flex items-start gap-3">
                    <span class="mt-1 w-2.5 h-2.5 rounded-full flex-shrink-0
                        {{ $task->priority === 'high' ? 'bg-red-500' : ($task->priority === 'medium' ? 'bg-amber-400' : 'bg-gray-300') }}">
                    </span>
                    <div>
                        <p class="font-medium text-gray-800">{{ $task->title }}</p>
                        @if ($task->description)
                            <p class="text-sm text-gray-500 mt-0.5 line-clamp-2">{{ $task->description }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                            <span class="capitalize">Priority: <span class="font-medium text-gray-600">{{ $task->priority }}</span></span>
                            <span class="capitalize">Status:
                                <span class="font-medium {{ $task->status === 'completed' ? 'text-green-600' : 'text-blue-600' }}">
                                    {{ str_replace('_', ' ', $task->status) }}
                                </span>
                            </span>
                            @if ($task->due_date)
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                    Due: {{ \Carbon\Carbon::parse($task->due_date)->format('d M') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-dashed border-gray-200 p-10 text-center">
                <img src="{{ asset('images/dashboard/empty-tasks.jpg') }}" alt="No tasks" class="w-40 mx-auto mb-4">
                <p class="text-gray-500 text-sm">No tasks yet. Create your first one!</p>
            </div>
        @endforelse
    </div>

    {{-- Right: Stats --}}
    <div class="space-y-6">

        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-600" /> Task Status
            </h3>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">Completed</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">In Progress</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-400">{{ $stats['todo'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">To Do</p>
                </div>
            </div>
        </div>

        {{-- AI Quick Add --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm"
            x-data="{
                prompt: '',
                files: [],
                maxFiles: 3,
                maxSize: 5 * 1024 * 1024,
                fileError: '',
                loading: false,
                loadingMode: null,
                result: null,
                error: null,

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
                    this.$refs.aiFileInput.files = dt.files;
                },
                formatSize(bytes) {
                    const kb = bytes / 1024;
                    return kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB';
                },

                async submit(mode) {
                    if (!this.prompt.trim()) return;

                    this.loading = true;
                    this.loadingMode = mode;
                    this.result = null;
                    this.error = null;

                    const url = mode === 'ai' ? '{{ route('ai.parse-intent') }}' : '{{ route('ai.quick-add-plain') }}';

                    const formData = new FormData();
                    formData.append('prompt', this.prompt);
                    this.files.forEach(f => formData.append('attachments[]', f));

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.result = data.task;
                            this.prompt = '';
                            this.files = [];
                            setTimeout(() => window.location.reload(), 1400);
                        } else {
                            this.error = data.message || 'Something went wrong.';
                        }
                    } catch (e) {
                        this.error = 'Could not reach the server. Try again.';
                    } finally {
                        this.loading = false;
                        this.loadingMode = null;
                    }
                }
            }">
            <h3 class="text-base font-semibold text-gray-800 mb-1 flex items-center gap-2">
                <x-heroicon-o-sparkles class="w-5 h-5 text-primary-600" /> AI Quick Add
            </h3>
            <p class="text-xs text-gray-400 mb-3">Describe your task. First line becomes the title.</p>

            <div class="flex items-start gap-2 mb-3">
                <img src="{{ asset('images/dashboard/ai-assistant.png') }}" alt="AI" class="w-22 h-16 flex-shrink-0">
                <textarea x-model="prompt" rows="2" :disabled="loading"
                    placeholder="e.g. Client meeting tomorrow 4pm high priority"
                    class="flex-1 text-sm rounded-lg border border-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none disabled:bg-gray-50 disabled:text-gray-400"></textarea>
            </div>

            {{-- Attachments --}}
            <label for="ai-quick-add-files"
                class="flex items-center justify-center gap-1.5 border-2 border-dashed border-gray-200 rounded-lg py-2.5 cursor-pointer hover:border-primary-400 hover:bg-primary-50/40 transition mb-2">
                <x-heroicon-o-paper-clip class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-xs text-gray-500">Attach files (up to 3 · 5MB each)</span>
            </label>
            <input type="file" id="ai-quick-add-files" x-ref="aiFileInput" multiple class="hidden"
                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                @change="addFiles($event.target.files)">

            <template x-if="fileError">
                <p class="text-xs text-red-500 mb-2" x-text="fileError"></p>
            </template>

            <div class="space-y-1.5 mb-3" x-show="files.length > 0">
                <template x-for="(file, index) in files" :key="file.name + index">
                    <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5">
                        <x-heroicon-o-document class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                        <span class="text-xs text-gray-600 truncate flex-1" x-text="file.name"></span>
                        <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="formatSize(file.size)"></span>
                        <button type="button" @click="removeFile(index)" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                            <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </template>
            </div>

            <template x-if="error">
                <div class="flex items-center gap-1.5 text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2 mb-3">
                    <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" />
                    <span x-text="error"></span>
                </div>
            </template>

            <template x-if="result">
                <div class="flex items-center gap-1.5 text-xs text-green-700 bg-green-50 rounded-lg px-3 py-2 mb-3">
                    <x-heroicon-o-check-circle class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>Task <strong x-text="result.title"></strong> created. Refreshing…</span>
                </div>
            </template>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="submit('ai')" :disabled="loading || !prompt.trim()"
                    class="flex items-center justify-center gap-1.5 text-xs font-medium bg-primary-500 hover:bg-primary-600 disabled:bg-gray-200 disabled:text-gray-400 text-white rounded-lg py-2.5 transition">
                    <template x-if="loading && loadingMode === 'ai'">
                        <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!(loading && loadingMode === 'ai')">
                        <x-heroicon-o-sparkles class="w-3.5 h-3.5" />
                    </template>
                    Get with AI
                </button>

                <button type="button" @click="submit('plain')" :disabled="loading || !prompt.trim()"
                    class="flex items-center justify-center gap-1.5 text-xs font-medium border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:text-gray-300 disabled:border-gray-200 rounded-lg py-2.5 transition">
                    <template x-if="loading && loadingMode === 'plain'">
                        <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!(loading && loadingMode === 'plain')">
                        <x-heroicon-o-clock class="w-3.5 h-3.5" />
                    </template>
                    Do It Later
                </button>
            </div>
        </div>

        {{-- Request Admin Access --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-800 mb-1 flex items-center gap-2">
                <x-heroicon-o-shield-exclamation class="w-5 h-5 text-primary-600" /> Admin Access
            </h3>
            <p class="text-xs text-gray-400 mb-3">Need elevated permissions? Request it here.</p>
            <button @click="$refs.requestAdminModal.showModal()"
                class="w-full text-sm border border-gray-300 text-gray-700 font-medium rounded-lg py-2 hover:bg-gray-50 transition">
                Request Admin Access
            </button>
        </div>

    </div>
</div>

{{-- Request Admin Access Modal --}}
<dialog x-ref="requestAdminModal" class="rounded-2xl p-0 backdrop:bg-black/40 w-full max-w-md m-auto overflow-hidden">
    <div class="bg-gray-900 px-6 py-5">
        <div class="inline-flex items-center gap-2 bg-white/10 text-white text-xs font-medium px-3 py-1.5 rounded-full mb-3">
            <x-heroicon-o-shield-exclamation class="w-4 h-4" /> Admin Access
        </div>
        <h3 class="text-white text-base font-semibold">Request Admin Access</h3>
        <p class="text-gray-400 text-sm mt-1">Tell the admin why you need this access.</p>
    </div>

    <div class="p-6">
        <form method="POST" action="{{ route('request-admin-access') }}" class="space-y-4">
            @csrf

            <div>
                <label for="reason" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                    <x-heroicon-o-chat-bubble-left-ellipsis class="w-4 h-4 text-gray-400" /> Reason
                </label>
                <textarea id="reason" name="reason" rows="4" required placeholder="Explain why you need admin access..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"></textarea>
                <p class="text-xs text-gray-400 mt-1.5">Your request will be reviewed by an existing admin.</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.querySelector('dialog[x-ref=requestAdminModal]').close()"
                    class="flex-1 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg py-2.5 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg py-2.5 transition flex items-center justify-center gap-2">
                    <x-heroicon-o-paper-airplane class="w-4 h-4" /> Submit Request
                </button>
            </div>
        </form>
    </div>
</dialog>
@endsection