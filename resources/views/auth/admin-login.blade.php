@extends('layouts.guest')

@section('title', 'Admin Login - Ordo')

@section('content')
    <div class="inline-flex items-center gap-2 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-full mb-6">
        <x-heroicon-o-shield-check class="w-4 h-4" /> Admin Access
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-1">Admin Sign In</h1>
    <p class="text-gray-500 mb-8">Restricted access — Super Admin only.</p>

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Admin Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-gray-800"
                placeholder="admin@ordo.com">
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" id="password" name="password" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-gray-800"
                    placeholder="••••••••">
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600">
                    <x-heroicon-o-eye class="w-5 h-5" x-show="!show" />
                    <x-heroicon-o-eye-slash class="w-5 h-5" x-show="show" x-cloak />
                </button>
            </div>
        </div>

        <button type="submit"
            class="w-full bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg py-2.5 text-sm transition">
            Sign In as Admin
        </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-8">
        Not an admin?
        <a href="{{ route('login') }}" class="text-primary-600 font-medium hover:underline">Back to user login</a>
    </p>
@endsection

@section('illustration')
    <div class="bg-white rounded-3xl p-6 shadow-xl mb-8">
        <img src="{{ asset('images/auth/admin-login-illustration.jpg') }}" alt="Admin Access" class="rounded-2xl w-full">
    </div>
    <h2 class="text-3xl font-bold mb-3">Full system control</h2>
    <p class="text-primary-100">Manage users, moderation, and platform settings.</p>
@endsection