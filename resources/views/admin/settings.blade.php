@extends('layouts.app')

@section('title', 'System Settings - Ordo')
@section('page-title', 'System Settings')

@section('content')

    <div class="max-w-2xl">

        @if ($errors->any())
            <div class="mb-6 flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <x-heroicon-o-exclamation-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf

            {{-- AI Configuration --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-br from-primary-600 to-primary-700 px-7 py-5">
                    <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-medium px-3 py-1.5 rounded-full mb-3">
                        <x-heroicon-o-sparkles class="w-4 h-4" /> AI Configuration
                    </div>
                    <h3 class="text-white text-lg font-semibold">Gemini API Settings</h3>
                    <p class="text-primary-100 text-sm mt-1">Update the AI key and system prompt without touching code.</p>
                </div>

                <div class="p-7 space-y-5">
                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                            <x-heroicon-o-key class="w-4 h-4 text-gray-400" /> Gemini API Key
                        </label>
                        <input type="password" name="gemini_api_key" value="{{ old('gemini_api_key', $settings['gemini_api_key']) }}"
                            placeholder="Leave blank to keep using the .env key"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        <p class="text-xs text-gray-400 mt-1.5">If set here, this overrides the key in the .env file. Leave blank to keep using .env.</p>
                    </div>

                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
                            <x-heroicon-o-chat-bubble-left-ellipsis class="w-4 h-4 text-gray-400" /> AI System Prompt (advanced)
                        </label>
                        <textarea name="ai_system_prompt" rows="4"
                            placeholder="Leave blank to use the default built-in prompt"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">{{ old('ai_system_prompt', $settings['ai_system_prompt']) }}</textarea>
                        <p class="text-xs text-gray-400 mt-1.5">Advanced: overrides the instructions sent to Gemini for task parsing.</p>
                    </div>
                </div>
            </div>

            {{-- Geo Settings --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-7">
                <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-1">
                    <x-heroicon-o-map class="w-5 h-5 text-gray-400" /> Geo-Spatial Settings
                </h3>
                <p class="text-sm text-gray-400 mb-5">Default radius used on the Nearby Tasks page.</p>

                <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Search Radius (km)</label>
                <select name="default_gps_radius" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    <option value="2" {{ $settings['default_gps_radius'] == '2' ? 'selected' : '' }}>2 km</option>
                    <option value="5" {{ $settings['default_gps_radius'] == '5' ? 'selected' : '' }}>5 km</option>
                    <option value="10" {{ $settings['default_gps_radius'] == '10' ? 'selected' : '' }}>10 km</option>
                </select>
            </div>

            {{-- Danger Zone --}}
            <div class="bg-amber-50 rounded-2xl border border-amber-200 p-7">
                <h3 class="text-base font-semibold text-amber-800 flex items-center gap-2 mb-1">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5" /> Maintenance Mode
                </h3>
                <p class="text-sm text-amber-600 mb-4">When enabled, only admins can log in. All other users will see a maintenance page.</p>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" {{ $settings['maintenance_mode'] == '1' ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm font-medium text-amber-800">Enable maintenance mode</span>
                </label>
            </div>

            <button type="submit"
                class="bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg px-6 py-2.5 transition flex items-center gap-2">
                <x-heroicon-o-check class="w-4 h-4" /> Save Settings
            </button>
        </form>
    </div>

@endsection