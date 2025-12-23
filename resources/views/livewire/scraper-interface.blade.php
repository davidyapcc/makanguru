<div class="min-h-screen bg-gradient-to-br from-[--color-nasi-lemak-cream] to-white">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[--color-rendang-brown]">
                        🌐 Restaurant Scraper
                    </h1>
                    <p class="text-sm text-gray-600 mt-2">
                        Import real restaurant data from OpenStreetMap
                    </p>
                </div>
                <div class="flex gap-4 items-center">
                    <a href="/restaurants" class="text-sm text-[--color-sky-blue] hover:text-[--color-sky-blue-light] font-medium transition-colors">
                        🍽️ Restaurants
                    </a>
                    <a href="/" class="text-sm text-[--color-sky-blue] hover:text-[--color-sky-blue-light] font-medium transition-colors">
                        💬 Chat
                    </a>
                    @if(session('admin_authenticated'))
                        <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-medium">
                                🚪 Logout
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Scraper Controls -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-6">Scraping Settings</h2>

                    <!-- Area Selection -->
                    <div class="mb-5">
                        <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
                            📍 Area
                        </label>
                        <select
                            id="area"
                            wire:model="selectedArea"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[--color-pandan-green] focus:border-transparent"
                        >
                            @foreach($availableAreas as $area)
                                <option value="{{ $area }}">{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Radius Selection -->
                    <div class="mb-5">
                        <label for="radius" class="block text-sm font-medium text-gray-700 mb-2">
                            📏 Radius: {{ number_format($radius / 1000, 1) }}km
                        </label>
                        <input
                            type="range"
                            id="radius"
                            wire:model.live="radius"
                            min="1000"
                            max="15000"
                            step="1000"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[--color-pandan-green]"
                        >
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>1km</span>
                            <span>15km</span>
                        </div>
                    </div>

                    <!-- Limit Selection -->
                    <div class="mb-5">
                        <label for="limit" class="block text-sm font-medium text-gray-700 mb-2">
                            🔢 Max Results: {{ $limit }}
                        </label>
                        <input
                            type="range"
                            id="limit"
                            wire:model.live="limit"
                            min="10"
                            max="200"
                            step="10"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[--color-pandan-green]"
                        >
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>10</span>
                            <span>200</span>
                        </div>
                    </div>

                    <!-- Preview Mode Toggle -->
                    <div class="mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="previewMode"
                                class="w-5 h-5 text-[--color-pandan-green] border-gray-300 rounded focus:ring-[--color-pandan-green]"
                            >
                            <span class="ml-3 text-sm font-medium text-gray-700">
                                🔍 Preview Mode (Don't Save)
                            </span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-8">
                            Preview results before importing to database
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <button
                            type="button"
                            wire:click="startScraping"
                            wire:loading.attr="disabled"
                            class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-4 rounded-lg font-semibold text-lg transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg hover:shadow-xl"
                        >
                            <span wire:loading.remove wire:target="startScraping" class="flex items-center justify-center gap-2">
                                @if($previewMode)
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Preview Restaurants
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Import Restaurants
                                @endif
                            </span>
                            <span wire:loading wire:target="startScraping" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Scraping OpenStreetMap...
                            </span>
                        </button>

                        @if(count($scrapedRestaurants) > 0)
                            <button
                                wire:click="clearResults"
                                class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 transition-colors"
                            >
                                🗑️ Clear Results
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Database Stats -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Database Stats</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Restaurants</span>
                            <span class="text-lg font-bold text-[--color-pandan-green]">{{ $dbStats['total'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Halal Options</span>
                            <span class="text-lg font-bold text-[--color-sambal-red]">{{ $dbStats['halal'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Areas Covered</span>
                            <span class="text-lg font-bold text-[--color-sky-blue]">{{ $dbStats['areas'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Results -->
            <div class="lg:col-span-2">
                <!-- Success/Error Messages -->
                @if($successMessage)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <span class="text-2xl mr-3">✅</span>
                            <div class="flex-1">
                                <h3 class="text-green-800 font-semibold">Success!</h3>
                                <p class="text-green-700 text-sm mt-1">{{ $successMessage }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($errorMessage)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <span class="text-2xl mr-3">❌</span>
                            <div class="flex-1">
                                <h3 class="text-red-800 font-semibold">Error</h3>
                                <p class="text-red-700 text-sm mt-1">{{ $errorMessage }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Results Stats -->
                @if($stats['found'] > 0)
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-md p-4 text-center">
                            <div class="text-3xl font-bold text-[--color-pandan-green]">{{ $stats['found'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Found</div>
                        </div>
                        @if(!$previewMode)
                            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                                <div class="text-3xl font-bold text-[--color-sky-blue]">{{ $stats['saved'] }}</div>
                                <div class="text-sm text-gray-600 mt-1">Saved</div>
                            </div>
                            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                                <div class="text-3xl font-bold text-[--color-sambal-red]">{{ $stats['duplicates'] }}</div>
                                <div class="text-sm text-gray-600 mt-1">Duplicates</div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Results Table -->
                @if(count($scrapedRestaurants) > 0)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">
                                {{ $previewMode ? 'Preview Results' : 'Import Results' }}
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Restaurant
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Area
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cuisine
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Price
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Halal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($scrapedRestaurants as $restaurant)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $restaurant['name'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ Str::limit($restaurant['description'] ?? '', 50) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ $restaurant['area'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ $restaurant['cuisine_type'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                                    {{ $restaurant['price'] === 'budget' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $restaurant['price'] === 'moderate' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $restaurant['price'] === 'expensive' ? 'bg-red-100 text-red-800' : '' }}
                                                ">
                                                    {{ ucfirst($restaurant['price']) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @if($restaurant['is_halal'])
                                                    <span class="text-green-600 text-lg">✓</span>
                                                @else
                                                    <span class="text-gray-400 text-lg">✗</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <div class="text-6xl mb-4">🍜</div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Results Yet</h3>
                        <p class="text-gray-600 mb-6">
                            Configure your settings and click "{{ $previewMode ? 'Preview' : 'Import' }} Restaurants" to get started
                        </p>
                        <div class="bg-gray-50 rounded-lg p-6 text-left mx-auto">
                            <h4 class="font-semibold text-gray-800 mb-3">💡 Quick Tips:</h4>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li>• Start with <strong>Preview Mode</strong> to see results first</li>
                                <li>• Use <strong>smaller radius</strong> (2-5km) for urban areas</li>
                                <li>• Limit to <strong>50-100 results</strong> for faster scraping</li>
                                <li>• Check <strong>Database Stats</strong> to track your progress</li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
