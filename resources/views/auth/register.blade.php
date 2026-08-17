@extends('layouts.guest')

@section('title', 'Register - Ordo')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Create your account</h1>
<p class="text-gray-500 mb-8">Start organizing your tasks with Ordo.</p>

@if ($errors->any())
<div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('register.submit') }}" class="space-y-5">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="Ali Khan">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="you@example.com">
    </div>

    <div class="grid grid-cols-2 gap-4" x-data="{ show: false }">
        <div>
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
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm</label>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="••••••••">
            </div>
        </div>
    </div>

    {{-- Account Type Selector --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
        <div class="grid grid-cols-2 gap-3">
            <label class="relative flex flex-col items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 cursor-pointer has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 transition">
                <input type="radio" name="account_type" value="personal" class="sr-only" {{ old('account_type', 'personal') == 'personal' ? 'checked' : '' }}>
                <span class="text-sm font-medium text-gray-800">Myself</span>
                <span class="text-xs text-gray-500">Personal use</span>
            </label>
            <label class="relative flex flex-col items-center justify-center rounded-lg border-2 border-gray-200 px-4 py-3 cursor-pointer has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 transition">
                <input type="radio" name="account_type" value="business" class="sr-only" {{ old('account_type') == 'business' ? 'checked' : '' }}>
                <span class="text-sm font-medium text-gray-800">Business</span>
                <span class="text-xs text-gray-500">Team workspace</span>
            </label>
        </div>
    </div>

    <button type="submit"
        class="w-full bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg py-2.5 text-sm transition">
        Create Account
    </button>
</form>

<p class="text-center text-sm text-gray-500 mt-8">
    Already have an account?
    <a href="{{ route('login') }}" class="text-primary-600 font-medium hover:underline">Sign in</a>
</p>
@endsection

@section('illustration')
<div class="bg-white rounded-3xl p-6 shadow-xl mb-8">
    <img src="{{ asset('images/auth/register-illustration.jpg') }}" alt="Get Started" class="rounded-2xl w-full">
</div>
<h2 class="text-3xl font-bold mb-3">Join Ordo today</h2>
<p class="text-primary-100">One prompt is all it takes to create your next task.</p>
@endsection