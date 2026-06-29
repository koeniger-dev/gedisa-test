<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'MovieRatings') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen">
            {{-- Public navigation: works for guests and authenticated users alike. --}}
            <nav class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-8">
                        <a href="{{ route('movies.search') }}" wire:navigate class="text-lg font-semibold">🎬 MovieRatings</a>
                    </div>

                    <div class="flex items-center gap-4 text-sm">
                        @auth
                            <a href="{{ route('dashboard') }}" wire:navigate class="text-gray-600 hover:text-gray-900">Watchlist</a>
                            <a href="{{ route('profile') }}" wire:navigate class="text-gray-600 hover:text-gray-900">Profil</a>
                            <span class="text-gray-300">·</span>
                            <span class="text-gray-600">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-gray-900">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="text-gray-600 hover:text-gray-900">Login</a>
                            <a href="{{ route('register') }}" wire:navigate class="px-3 py-1.5 bg-gray-900 text-white rounded-md hover:bg-gray-700">Registrieren</a>
                        @endauth
                    </div>
                </div>
            </nav>

            <main class="py-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
