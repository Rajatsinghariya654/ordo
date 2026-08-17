<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon.png') }}">
    <title>@yield('title', 'Ordo')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-surface font-sans antialiased">

    <div class="min-h-screen flex flex-col md:flex-row">

        {{-- Left: Form Side --}}
        <div class="w-full md:w-1/2 flex flex-col justify-center px-6 sm:px-12 lg:px-24 py-12">
            <div class="max-w-md w-full mx-auto">
                <img src="{{ asset('images/logo/ordo_logo.png') }}" alt="Ordo"
                    class="h-20 w-auto object-contain rounded-xl shadow-md p-1.5 bg-white mb-10 mx-auto md:mx-0">

                @yield('content')
            </div>
        </div>

        {{-- Right: Illustration Side --}}
        <div class="hidden md:flex w-1/2 bg-primary-500 items-center justify-center p-12 relative overflow-hidden">
            <div class="relative z-10 text-center text-white max-w-sm">
                @yield('illustration')
            </div>
        </div>

    </div>

</body>

</html>