<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MakanGuru</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-[--color-nasi-lemak-cream] to-white min-h-screen antialiased">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">🍜</div>
                <h1 class="text-3xl font-bold text-amber-900">MakanGuru</h1>
                <p class="text-gray-600 mt-2">Admin Access</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Enter Access Key</h2>

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-600 font-medium">
                            {{ $errors->first('access_key') ?? $errors->first('message') }}
                        </p>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('admin.authenticate') }}">
                    @csrf

                    <div class="mb-6">
                        <label for="access_key" class="block text-sm font-medium text-gray-700 mb-2">
                            Access Key
                        </label>
                        <input
                            type="password"
                            id="access_key"
                            name="access_key"
                            required
                            autofocus
                            class="w-full px-4 py-3 border @error('access_key') border-red-300 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Enter your access key"
                        >
                        @error('access_key')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors shadow-md"
                    >
                        🔐 Authenticate
                    </button>
                </form>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <a href="/" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        ← Back to Chat
                    </a>
                </div>
            </div>

            <!-- Info -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>This area is restricted to administrators only.</p>
                <p class="mt-1">Access key is configured in <code class="bg-gray-100 px-2 py-1 rounded">.env</code></p>
            </div>
        </div>
    </div>
</body>
</html>
