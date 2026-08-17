@extends('layouts.app')

@section('title', 'Edit Profile - Ordo')
@section('page-title', 'Profile Settings')

@section('content')

<div class="max-w-2xl space-y-6" x-data="{ deleteModal: false }">

    @if ($errors->any())
    <div class="flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <x-heroicon-o-exclamation-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    {{-- Profile Info --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="flex items-center gap-5">
                <div class="w-20 h-20 rounded-full overflow-hidden bg-primary-100 text-primary-700 flex items-center justify-center text-2xl font-semibold flex-shrink-0">
                    @if (auth()->user()->avatar)
                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="Avatar" class="w-full h-full object-cover">
                    @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <label for="avatar" class="cursor-pointer inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                        <x-heroicon-o-camera class="w-4 h-4" /> Change Photo
                    </label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden"
                        onchange="document.getElementById('avatar-filename').textContent = this.files[0]?.name || ''">
                    <p id="avatar-filename" class="text-xs text-gray-400 mt-1"></p>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <label for="name" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                    <x-heroicon-o-user class="w-4 h-4 text-gray-400" /> Full Name
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
            </div>

            <div>
                <label for="status" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                    <x-heroicon-o-signal class="w-4 h-4 text-gray-400" /> Status
                </label>
                <input type="text" id="status" name="status" value="{{ old('status', auth()->user()->status) }}"
                    placeholder="e.g. Available, In a meeting, On leave till Monday"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
            </div>

            <div>
                <label for="bio" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                    <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400" /> Bio
                </label>
                <textarea id="bio" name="bio" rows="4" placeholder="Tell your team a bit about yourself..."
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">{{ old('bio', auth()->user()->bio) }}</textarea>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-400 mb-4">
                    Email: <span class="text-gray-600">{{ auth()->user()->email }}</span> (cannot be changed here)
                </p>
                <button type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg px-6 py-2.5 transition flex items-center gap-2">
                    <x-heroicon-o-check class="w-4 h-4" /> Save Changes
                </button>
            </div>
        </form>
    </div>

   @unless (auth()->user()->isAdmin())
    {{-- Account Type --}}
    @php
        $pendingSwitch = auth()->user()->switchRequests()->where('status', 'pending')->first();
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8" x-data="{ switchModal: false }">
        <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-1">
            <x-heroicon-o-arrow-path class="w-5 h-5 text-gray-400" /> Account Type
        </h3>
        <p class="text-sm text-gray-400 mb-5">Switch between Personal and Business — requires admin approval.</p>

        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3.5 mb-4">
            <div>
                <p class="text-xs text-gray-400">Current Type</p>
                <p class="text-sm font-semibold text-gray-800 capitalize">{{ auth()->user()->account_type }}</p>
            </div>
            <span class="text-xs font-medium bg-primary-100 text-primary-700 px-3 py-1 rounded-full capitalize">
                {{ auth()->user()->account_type }}
            </span>
        </div>

        @if ($pendingSwitch)
        <div class="flex items-center gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
            <x-heroicon-o-clock class="w-4 h-4 flex-shrink-0" />
            <span>
                Your request to switch to <strong class="capitalize">{{ $pendingSwitch->requested_type }}</strong> is pending admin review.
            </span>
        </div>
        @else
        <button type="button" @click="switchModal = true"
            class="flex items-center gap-2 text-sm font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg px-5 py-2.5 transition">
            <x-heroicon-o-arrow-path class="w-4 h-4" />
            Request Switch to {{ auth()->user()->account_type === 'personal' ? 'Business' : 'Personal' }}
        </button>
        @endif

        {{-- Switch Request Modal --}}
        <div x-show="switchModal" x-cloak
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
            @click.self="switchModal = false">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <div class="flex items-center gap-2 text-primary-600 mb-1">
                    <x-heroicon-o-arrow-path class="w-5 h-5" />
                    <h3 class="text-lg font-semibold text-gray-800">Request Account Switch</h3>
                </div>
                <p class="text-sm text-gray-500 mb-5">
                    Switch from <strong class="capitalize">{{ auth()->user()->account_type }}</strong> to
                    <strong class="capitalize">{{ auth()->user()->account_type === 'personal' ? 'Business' : 'Personal' }}</strong>.
                    An admin will review your request.
                </p>

                <form method="POST" action="{{ route('request-account-switch') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Reason</label>
                        <textarea name="reason" rows="3" required placeholder="Why do you need this switch?"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="switchModal = false"
                            class="flex-1 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg py-2.5">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg py-2.5">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endunless

    {{-- Change Password --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
        <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-1">
            <x-heroicon-o-lock-closed class="w-5 h-5 text-gray-400" /> Change Password
        </h3>
        <p class="text-sm text-gray-400 mb-5">Use a strong password you don't use elsewhere.</p>

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input type="password" id="current_password" name="current_password" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <input type="password" id="password" name="password" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                </div>
            </div>

            <button type="submit"
                class="bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg px-6 py-2.5 transition flex items-center gap-2">
                <x-heroicon-o-key class="w-4 h-4" /> Update Password
            </button>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="bg-red-50 rounded-2xl border border-red-200 p-8">
        <h3 class="text-base font-semibold text-red-700 flex items-center gap-2 mb-1">
            <x-heroicon-o-exclamation-triangle class="w-5 h-5" /> Danger Zone
        </h3>
        <p class="text-sm text-red-500 mb-5">Deleting your account is permanent. Your tasks will remain but will no longer be tied to your name.</p>

        <button type="button" @click="deleteModal = true"
            class="flex items-center gap-2 bg-white border-2 border-red-500 text-red-600 hover:bg-red-600 hover:text-white hover:shadow-lg hover:shadow-red-200 text-sm font-semibold rounded-lg px-5 py-2.5 transition-all duration-200">
            <x-heroicon-o-trash class="w-4 h-4" /> Delete My Account
        </button>
    </div>


    {{-- Logout --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Log Out</h3>
            <p class="text-xs text-gray-400 mt-0.5">Sign out of your account on this device.</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex items-center gap-2 text-sm font-medium text-red-600 bg-red-50 border border-red-100 rounded-lg px-5 py-2.5 hover:bg-red-600 hover:text-white hover:border-red-600 hover:shadow-md transition-all duration-200">
                <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4" /> Logout
            </button>
        </form>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal" x-cloak
        class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        @click.self="deleteModal = false">
        <div class="bg-white rounded-2xl w-full max-w-md p-6">
            <div class="flex items-center gap-2 text-red-600 mb-1">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                <h3 class="text-lg font-semibold">Delete Account</h3>
            </div>
            <p class="text-sm text-gray-500 mb-5">This action cannot be undone. This will permanently deactivate your account.</p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Enter your password</label>
                    <input type="password" name="password" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Type <span class="font-mono font-bold text-red-600">DELETE</span> to confirm
                    </label>
                    <input type="text" name="confirmation" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="deleteModal = false"
                        class="flex-1 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg py-2.5">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg py-2.5">
                        Permanently Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection