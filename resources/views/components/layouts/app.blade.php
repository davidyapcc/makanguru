<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'MakanGuru - Where to Makan?' }}</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="MakanGuru - AI-powered Malaysian food recommendations. Get personalized suggestions from The Mak Cik, The Gym Bro, or The Atas Friend. Never wonder 'Makan Mana?' again!">
    <meta name="keywords" content="Malaysian food, restaurant recommendations, AI food guide, where to eat Malaysia, halal food, nasi lemak, makan mana">
    <meta name="author" content="MakanGuru">

    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'MakanGuru - AI-Powered Malaysian Food Recommendations' }}">
    <meta property="og:description" content="Get personalized Malaysian food recommendations from AI personalities. The Mak Cik, The Gym Bro, and The Atas Friend are here to help you decide where to eat!">
    <meta property="og:image" content="{{ asset('images/og-makanguru-logo.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $title ?? 'MakanGuru - AI-Powered Malaysian Food Recommendations' }}">
    <meta name="twitter:description" content="Get personalized Malaysian food recommendations from AI personalities. Never wonder 'Makan Mana?' again!">
    <meta name="twitter:image" content="{{ asset('images/og-makanguru-logo.png') }}">

    <!-- Additional Meta Tags -->
    <meta name="theme-color" content="#DC2626">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gradient-to-br from-[--color-nasi-lemak-cream] to-white min-h-screen antialiased">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50 border-b-2 border-[--color-sambal-red]">
        <div class="max-w-4xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <a href="/" class="flex items-center space-x-2">
                    <img src="{{ asset('images/makanguru-logo-removebg-small.png') }}" alt="MakanGuru Logo" class="h-10 w-10 object-contain">
                    <h1 class="text-xl font-bold text-[--color-teh-tarik-brown]">
                        MakanGuru
                    </h1>
                </a>
                <div class="flex items-center space-x-4">
                    @if(session('admin_authenticated'))
                        <a href="/restaurants" class="text-sm text-[--color-sky-blue] hover:text-[--color-sky-blue-light] font-medium">
                            🍽️ Restaurants
                        </a>
                        <a href="/scraper" class="text-sm text-[--color-sky-blue] hover:text-[--color-sky-blue-light] font-medium">
                            🌐 Scraper
                        </a>
                        <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-medium">
                                🚪 Logout
                            </button>
                        </form>
                    @endif
                    <div class="text-sm text-gray-600">
                        Where to makan?
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="max-w-4xl mx-auto px-4 py-6 text-center text-sm text-gray-500">
        <p>Powered by AI • Built with ❤️ in Malaysia</p>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>
