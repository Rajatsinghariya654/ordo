@extends('layouts.app')

@section('title', 'Nearby Tasks - Ordo')
@section('page-title', 'Nearby Tasks')

@section('content')

<style>
    .leaflet-control-zoom {
        border: none !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.15) !important;
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

<div x-data="{
    locating: true,
    locationError: '',
    radius: {{ (float) $radius }},
    mapObj: null,
    userMarker: null,
    taskMarkers: [],
    tasks: {{ $tasks->values()->toJson() }},
    userLat: {{ $lat ?: 'null' }},
    userLng: {{ $lng ?: 'null' }},

    init() {
        if (this.userLat && this.userLng) {
            this.locating = false;
            this.$nextTick(() => this.initMap());
        } else {
            this.getLocation();
        }
    },
    getLocation() {
        this.locating = true;
        this.locationError = '';
        if (!navigator.geolocation) {
            this.locating = false;
            this.locationError = 'Geolocation is not supported by your browser.';
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.userLat = pos.coords.latitude;
                this.userLng = pos.coords.longitude;
                this.reload();
            },
            () => {
                this.locating = false;
                this.locationError = 'Could not access your location. Please allow location access and try again.';
            }
        );
    },
    reload() {
        window.location.href = '{{ route('tasks.nearby') }}?lat=' + this.userLat + '&lng=' + this.userLng + '&radius=' + this.radius;
    },
    initMap() {
        this.mapObj = L.map(this.$refs.mapContainer).setView([this.userLat, this.userLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(this.mapObj);

        const userIcon = L.divIcon({
            className: '',
            html: '<div style=\'width:16px;height:16px;background:#2563eb;border:3px solid white;border-radius:50%;box-shadow:0 0 0 6px rgba(37,99,235,0.25);\'></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
        });
        this.userMarker = L.marker([this.userLat, this.userLng], { icon: userIcon }).addTo(this.mapObj).bindPopup('You are here');

        this.tasks.forEach((task) => {
            const marker = L.marker([task.latitude, task.longitude]).addTo(this.mapObj)
                .bindPopup('<strong>' + task.title + '</strong><br>' + (task.distance ? task.distance.toFixed(1) + ' km away' : ''));
            this.taskMarkers.push(marker);
        });

        setTimeout(() => this.mapObj.invalidateSize(), 150);
    }
}">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Nearby Tasks</h2>
            <p class="text-sm text-gray-500 mt-0.5">Tasks with a location, sorted by distance from where you are.</p>
        </div>
        <div class="flex items-center gap-2">
            <select x-model.number="radius" @change="reload()"
                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                <option value="2">Within 2 km</option>
                <option value="5">Within 5 km</option>
                <option value="10">Within 10 km</option>
            </select>
            <button @click="getLocation()"
                class="flex items-center gap-1.5 text-sm font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg px-3.5 py-2 transition">
                <x-heroicon-o-arrow-path class="w-4 h-4" /> Refresh
            </button>
        </div>
    </div>

    <template x-if="locating">
        <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center">
            <x-heroicon-o-map-pin class="w-10 h-10 text-primary-300 mx-auto mb-3 animate-bounce" />
            <p class="text-gray-600 font-medium">Finding your location…</p>
            <p class="text-sm text-gray-400 mt-1">Please allow location access when prompted.</p>
        </div>
    </template>

    <template x-if="!locating && locationError">
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-16 text-center">
            <x-heroicon-o-exclamation-triangle class="w-10 h-10 text-amber-400 mx-auto mb-3" />
            <p class="text-gray-600 font-medium" x-text="locationError"></p>
            <button @click="getLocation()" class="mt-4 text-sm font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg px-4 py-2 transition">
                Try Again
            </button>
        </div>
    </template>

    <template x-if="!locating && !locationError">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

            {{-- Map --}}
            <div class="lg:col-span-3">
                <div x-ref="mapContainer" class="isolate w-full h-[500px] rounded-2xl border border-gray-100"></div>
            </div>

            {{-- List --}}
            <div class="lg:col-span-2 space-y-3">
                <template x-if="tasks.length === 0">
                    <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-10 text-center">
                        <x-heroicon-o-map class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                        <p class="text-sm text-gray-500">No tasks with a location nearby.</p>
                        <p class="text-xs text-gray-400 mt-1">Try a bigger radius, or add a location to a task.</p>
                    </div>
                </template>

                <template x-for="task in tasks" :key="task.id">
                    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-gray-800" x-text="task.title"></p>
                            <span class="text-xs font-semibold text-primary-600 bg-primary-50 rounded-full px-2 py-0.5 flex-shrink-0"
                                x-text="task.distance ? task.distance.toFixed(1) + ' km' : ''"></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1.5 flex items-center gap-1.5" x-show="task.location_name">
                            <x-heroicon-o-map-pin class="w-3.5 h-3.5 flex-shrink-0" />
                            <span class="truncate" x-text="task.location_name"></span>
                        </p>
                        <div class="flex items-center gap-2 mt-2.5">
                            <span class="text-xs px-2 py-0.5 rounded-full capitalize"
                                :class="{
                                    'bg-gray-100 text-gray-600': task.status === 'todo',
                                    'bg-blue-100 text-blue-600': task.status === 'in_progress',
                                    'bg-amber-100 text-amber-600': task.status === 'review',
                                    'bg-green-100 text-green-600': task.status === 'completed'
                                }"
                                x-text="task.status.replace('_', ' ')"></span>
                            <span class="text-xs px-2 py-0.5 rounded-full capitalize"
                                :class="{
                                    'bg-red-50 text-red-500': task.priority === 'high',
                                    'bg-amber-50 text-amber-500': task.priority === 'medium',
                                    'bg-gray-50 text-gray-500': task.priority === 'low'
                                }"
                                x-text="task.priority"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>

@endsection