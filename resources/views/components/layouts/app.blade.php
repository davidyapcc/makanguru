<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'MakanGuru - Where to Makan?' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gradient-to-br from-[--color-nasi-lemak-cream] to-white min-h-screen antialiased">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50 border-b-2 border-[--color-sambal-red]">
        <div class="max-w-4xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="text-2xl">🍜</div>
                    <h1 class="text-xl font-bold text-[--color-teh-tarik-brown]">
                        MakanGuru
                    </h1>
                </div>
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
</body>
</html>
