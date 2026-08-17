@extends('layouts.app')

@section('title', 'Admin Dashboard - Ordo')
@section('page-title', 'Admin Overview')

@section('content')

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">

        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total_users'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Total Users</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-primary-600">{{ $stats['personal_users'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Personal</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['business_users'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Business</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total_tasks'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Total Tasks</p>
        </div>

        <div class="bg-white rounded-xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['pending_switch_requests'] }}</p>
            <p class="text-xs text-amber-500 mt-1">Pending Requests</p>
        </div>

        <div class="bg-white rounded-xl border border-red-100 bg-red-50 p-4 shadow-sm">
            <p class="text-2xl font-bold text-red-600">{{ $stats['suspended_users'] }}</p>
            <p class="text-xs text-red-500 mt-1">Suspended/Blocked</p>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('admin.users') }}" class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
            <p class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <x-heroicon-o-users class="w-5 h-5 text-primary-600" /> Manage Users
            </p>
            <p class="text-sm text-gray-500 mt-1">View, suspend, or block user accounts.</p>
        </a>

        <a href="{{ route('admin.switch-requests') }}" class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
            <p class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <x-heroicon-o-arrow-path class="w-5 h-5 text-primary-600" /> Switch Requests
            </p>
            <p class="text-sm text-gray-500 mt-1">Review pending account type changes.</p>
        </a>

        <a href="{{ route('admin.create-admin') }}" class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
            <p class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <x-heroicon-o-user-plus class="w-5 h-5 text-primary-600" /> Create Admin
            </p>
            <p class="text-sm text-gray-500 mt-1">Directly create a new admin account.</p>
        </a>

        <a href="{{ route('admin.admin-requests') }}" class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
            <p class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-primary-600" /> Admin Requests
            </p>
            <p class="text-sm text-gray-500 mt-1">Review pending admin access requests.</p>
        </a>
    </div>

@endsection