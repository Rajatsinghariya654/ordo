<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon.png') }}">
    <title>@yield('title', 'Ordo')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>

<body class="bg-surface font-sans antialiased" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex">

        {{-- ─── Mobile Overlay ─── --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/40 z-30 md:hidden"></div>

        {{-- ─── Sidebar ─── --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed md:sticky md:top-0 inset-y-0 left-0 z-40 w-64 h-screen bg-white border-r border-gray-100 flex flex-col transition-transform duration-200 md:translate-x-0">

            <div class="h-16 flex items-center px-6 border-b border-gray-100">
                <img src="{{ asset('images/logo/ordo_logo.png') }}" alt="Ordo"
                    class="h-12 w-auto object-contain rounded-xl shadow-md p-1.5 bg-white">
            </div>
            {{-- User Card --}}
            <div class="px-6 py-5 flex items-center gap-3 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full overflow-hidden bg-primary-100 text-primary-700 flex items-center justify-center font-semibold text-sm flex-shrink-0">
                    @if (auth()->user()->avatar)
                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="Avatar" class="w-full h-full object-cover">
                    @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                    @if (auth()->user()->status)
                    <p class="text-xs text-primary-600 truncate">{{ auth()->user()->status }}</p>
                    @else
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    @endif
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">

                @if (auth()->user()->isAdmin())
                {{-- ═══ ADMIN NAVIGATION ═══ --}}
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Admin Panel</p>

                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-chart-bar class="w-5 h-5" /> Overview
                </a>
                <a href="{{ route('admin.users') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.users') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-users class="w-5 h-5" /> Users
                </a>
                <a href="{{ route('admin.workspaces') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.workspaces') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-building-office-2 class="w-5 h-5" /> Workspaces
                </a>
                <a href="{{ route('admin.switch-requests') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.switch-requests') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-arrow-path class="w-5 h-5" /> Switch Requests
                </a>
                <a href="{{ route('admin.create-admin') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.create-admin') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-user-plus class="w-5 h-5" /> Create Admin
                </a>
                <a href="{{ route('admin.admin-requests') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.admin-requests') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-shield-exclamation class="w-5 h-5" /> Admin Requests
                </a>
                <a href="{{ route('admin.moderation-logs') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.moderation-logs') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-shield-check class="w-5 h-5" /> Moderation Logs
                </a>
                <a href="{{ route('admin.activity-logs') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.activity-logs') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-clock class="w-5 h-5" /> Activity Logs
                </a>
                <a href="{{ route('admin.settings') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.settings') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-wrench-screwdriver class="w-5 h-5" /> System Settings
                </a>
                <a href="{{ route('admin.analytics') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.analytics') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-chart-bar-square class="w-5 h-5" /> AI Analytics
                </a>

                @elseif (auth()->user()->isBusiness())
                {{-- ═══ BUSINESS NAVIGATION ═══ --}}
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Workspace</p>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-home class="w-5 h-5" /> Dashboard
                </a>
                <a href="{{ route('workspaces.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('workspaces.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-building-office-2 class="w-5 h-5" /> My Workspaces
                </a>
                <a href="{{ route('tasks.board') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('tasks.board') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-clipboard-document-list class="w-5 h-5" /> Team Tasks
                </a>
                <a href="{{ route('tasks.nearby') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('tasks.nearby') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-map class="w-5 h-5" /> Nearby Tasks
                </a>
                <a href="{{ route('my-activity') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('my-activity') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-clock class="w-5 h-5" /> My Activity
                </a>
                <a href="{{ route('my-analytics-page') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('my-analytics-page') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-chart-bar class="w-5 h-5" /> My Analytics
                </a>

                @else
                {{-- ═══ PERSONAL (MYSELF) NAVIGATION ═══ --}}
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">My Space</p>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-home class="w-5 h-5" /> My Day
                </a>
                <a href="{{ route('workspaces.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('workspaces.*') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-user-group class="w-5 h-5" /> My Workspaces
                </a>
                <a href="{{ route('tasks.board') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('tasks.board') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-clipboard-document-list class="w-5 h-5" /> My Tasks
                </a>
                <a href="{{ route('tasks.nearby') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('tasks.nearby') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-map class="w-5 h-5" /> Nearby Tasks
                </a>
                <a href="{{ route('my-activity') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('my-activity') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-clock class="w-5 h-5" /> My Activity
                </a>
                <a href="{{ route('my-analytics-page') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('my-analytics-page') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-heroicon-o-chart-bar class="w-5 h-5" /> My Analytics
                </a>
                @endif

                <div class="pt-4 mt-4 border-t border-gray-100">
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('profile.edit') ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <x-heroicon-o-cog-6-tooth class="w-5 h-5" /> Settings
                    </a>
                </div>
            </nav>

            {{-- Logout --}}
            <div class="px-4 py-4 border-t border-gray-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-500 hover:bg-red-50 hover:text-red-600 transition">
                        <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" /> Logout
                    </button>
                </form>
            </div>
        </aside>

        {{-- ─── Main Content ─── --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 md:px-8">
                <button @click="sidebarOpen = true" class="md:hidden text-gray-500">
                    <x-heroicon-o-bars-3 class="w-6 h-6" />
                </button>

                <h1 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>

                <div class="flex items-center gap-4">
                    @if (auth()->user()->isAdmin())
                    <span class="text-xs font-medium bg-gray-900 text-white px-3 py-1 rounded-full">Admin</span>
                    @endif
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-4 md:p-8">
                @if (session('success'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>

</html>