<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon.png') }}">
    <title>Under Maintenance - Ordo</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white h-screen overflow-hidden flex items-center justify-center px-4">
    <div class="max-w-3xl w-full">
        <div class="bg-white rounded-3xl px-8 py-6 text-center">
            <img src="{{ asset('images/maintenance/maintenance.jpg') }}" alt="Under maintenance" class="w-full max-w-xs mx-auto mb-6">

            <h1 class="text-3xl font-bold text-gray-900 mb-3">We'll be right back</h1>
            <p class="text-gray-500 text-base leading-relaxed mb-6 whitespace-nowrap">
                Ordo is currently undergoing scheduled maintenance to make things even better. Please check back shortly.
            </p>

            <a href="{{ route('admin.login') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700 border border-primary-200 hover:bg-primary-50 rounded-lg px-5 py-2.5 transition">
                <x-heroicon-o-shield-check class="w-4 h-4" />
                Admin Sign In
            </a>
        </div>
    </div>
</body>
</html>