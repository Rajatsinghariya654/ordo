@extends('layouts.guest')

@section('title', 'Login - Ordo')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h1>
<p class="text-gray-500 mb-8">Login to manage your tasks with Ordo.</p>

@if ($errors->any())
<div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="you@example.com">
    </div>

    <div x-data="{ show: false }">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
        <div class="relative">
            <input :type="show ? 'text' : 'password'" id="password" name="password" required
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="••••••••">
            <button type="button" @click="show = !show"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <x-heroicon-o-eye class="w-5 h-5" x-show="!show" />
                <x-heroicon-o-eye-slash class="w-5 h-5" x-show="show" x-cloak />
            </button>
        </div>
    </div>

    <button type="submit"
        class="w-full bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg py-2.5 text-sm transition">
        Sign In
    </button>
</form>

<p class="text-center text-sm text-gray-500 mt-8">
    Don't have an account?
    <a href="{{ route('register') }}" class="text-primary-600 font-medium hover:underline">Sign up</a>
</p>

<p class="text-center text-sm mt-3">
    <a href="{{ route('admin.login') }}" class="text-gray-400 hover:text-primary-600 hover:underline">Sign in as Admin</a>
</p>
@endsection

@section('illustration')
<div class="bg-white rounded-3xl p-6 shadow-xl mb-8">
    <img src="{{ asset('images/auth/login-illustration.jpg') }}" alt="Secure Login" class="rounded-2xl w-full">
</div>
<h2 class="text-3xl font-bold mb-3">Manage tasks the smart way</h2>
<p class="text-primary-100">Let AI turn your everyday thoughts into organized, geo-aware tasks.</p>
@endsection