@extends('layouts.app')

@section('title', 'Create Admin - Ordo')
@section('page-title', 'Create Admin Account')

@section('content')

    <div class="max-w-xl mx-auto">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            {{-- Header strip --}}
            <div class="bg-gray-900 px-8 py-6">
                <div class="inline-flex items-center gap-2 bg-white/10 text-white text-xs font-medium px-3 py-1.5 rounded-full mb-3">
                    <x-heroicon-o-shield-check class="w-4 h-4" /> Admin Only
                </div>
                <h2 class="text-white text-lg font-semibold">Create a New Admin</h2>
                <p class="text-gray-400 text-sm mt-1">This account will get full system access immediately.</p>
            </div>

            <div class="p-8">
                @if ($errors->any())
                    <div class="mb-6 flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        <x-heroicon-o-exclamation-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.create-admin.submit') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-400" /> Full Name
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                            placeholder="e.g. Aisha Khan"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-gray-800 transition">
                    </div>

                    <div>
                        <label for="email" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                            <x-heroicon-o-envelope class="w-4 h-4 text-gray-400" /> Email
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            placeholder="admin@ordo.com"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-gray-800 transition">
                    </div>

                    <div class="grid grid-cols-2 gap-4" x-data="{ show: false }">
                        <div>
                            <label for="password" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                                <x-heroicon-o-lock-closed class="w-4 h-4 text-gray-400" /> Password
                            </label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" id="password" name="password" required
                                    placeholder="••••••••"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-gray-800 transition">
                                <button type="button" @click="show = !show"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <x-heroicon-o-eye class="w-5 h-5" x-show="!show" />
                                    <x-heroicon-o-eye-slash class="w-5 h-5" x-show="show" x-cloak />
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="password_confirmation" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                                <x-heroicon-o-lock-closed class="w-4 h-4 text-gray-400" /> Confirm
                            </label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                                    placeholder="••••••••"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-gray-800 transition">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <label for="security_code" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5 mt-4">
                            <x-heroicon-o-key class="w-4 h-4 text-gray-400" /> Security Code
                        </label>
                        <input type="password" id="security_code" name="security_code" required
                            placeholder="Enter the system security code"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-gray-800 transition">
                        <p class="text-xs text-gray-400 mt-1.5">This code is stored securely and known only to the system owner.</p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <a href="{{ route('admin.dashboard') }}"
                            class="flex-1 text-center border border-gray-300 text-gray-600 text-sm font-medium rounded-lg py-2.5 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit"
                            class="flex-1 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg py-2.5 transition flex items-center justify-center gap-2">
                            <x-heroicon-o-user-plus class="w-4 h-4" /> Create Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection