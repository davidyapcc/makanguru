<div class="min-h-screen bg-gradient-to-br from-[--color-nasi-lemak-cream] to-white">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[--color-rendang-brown]">
                        🍽️ Restaurant Database
                    </h1>
                    <p class="text-sm text-gray-600 mt-2">
                        Browse all {{ $totalCount }} restaurants in our collection
                    </p>
                </div>
                <div class="flex gap-4 items-center">
                    <a href="/scraper" class="text-sm text-[--color-sky-blue] hover:text-[--color-sky-blue-light] font-medium transition-colors">
                        🌐 Scraper
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
        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        🔍 Search
                    </label>
                    <input
                        type="text"
                        id="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, cuisine, area..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[--color-pandan-green] focus:border-transparent"
                    >
                </div>

                <!-- Halal Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        🥘 Dietary
                    </label>
                    <label class="flex items-center cursor-pointer h-10">
                        <input
                            type="checkbox"
                            wire:model.live="filterHalal"
                            class="w-5 h-5 text-[--color-pandan-green] border-gray-300 rounded focus:ring-[--color-pandan-green]"
                        >
                        <span class="ml-2 text-sm text-gray-700">Halal Only</span>
                    </label>
                </div>

                <!-- Price Filter -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        💰 Price Range
                    </label>
                    <select
                        id="price"
                        wire:model.live="filterPrice"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[--color-pandan-green] focus:border-transparent"
                    >
                        <option value="">All Prices</option>
                        <option value="budget">Budget (RM 10-20)</option>
                        <option value="moderate">Moderate (RM 20-50)</option>
                        <option value="expensive">Expensive (RM 50+)</option>
                    </select>
                </div>

                <!-- Area Filter -->
                <div>
                    <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
                        📍 Area
                    </label>
                    <select
                        id="area"
                        wire:model.live="filterArea"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[--color-pandan-green] focus:border-transparent"
                    >
                        <option value="">All Areas</option>
                        @foreach($availableAreas as $area)
                            <option value="{{ $area }}">{{ $area }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Cuisine Filter (Full Width) -->
            <div class="mt-4">
                <label for="cuisine" class="block text-sm font-medium text-gray-700 mb-2">
                    🍜 Cuisine Type
                </label>
                <select
                    id="cuisine"
                    wire:model.live="filterCuisine"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[--color-pandan-green] focus:border-transparent"
                >
                    <option value="">All Cuisines</option>
                    @foreach($availableCuisines as $cuisine)
                        <option value="{{ $cuisine }}">{{ $cuisine }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Clear Filters -->
            @if($search || $filterHalal || $filterPrice || $filterArea || $filterCuisine)
                <div class="mt-4">
                    <button
                        wire:click="clearFilters"
                        class="text-sm text-[--color-sambal-red] hover:text-[--color-sambal-red-dark] font-medium transition-colors"
                    >
                        🗑️ Clear All Filters
                    </button>
                </div>
            @endif
        </div>

        <!-- Results Table -->
        @if($restaurants->count() > 0)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('name')">
                                    <div class="flex items-center gap-1">
                                        Restaurant
                                        @if($sortBy === 'name')
                                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('area')">
                                    <div class="flex items-center gap-1">
                                        Area
                                        @if($sortBy === 'area')
                                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('cuisine_type')">
                                    <div class="flex items-center gap-1">
                                        Cuisine
                                        @if($sortBy === 'cuisine_type')
                                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('price')">
                                    <div class="flex items-center gap-1">
                                        Price
                                        @if($sortBy === 'price')
                                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Halal
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tags
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($restaurants as $restaurant)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $restaurant->name }}
                                        </div>
                                        @if($restaurant->description)
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ Str::limit($restaurant->description, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $restaurant->area }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $restaurant->cuisine_type ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                            {{ $restaurant->price === 'budget' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $restaurant->price === 'moderate' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $restaurant->price === 'expensive' ? 'bg-red-100 text-red-800' : '' }}
                                        ">
                                            {{ ucfirst($restaurant->price) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($restaurant->is_halal)
                                            <span class="text-green-600 text-lg">✓</span>
                                        @else
                                            <span class="text-gray-400 text-lg">✗</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @if($restaurant->tags && count($restaurant->tags) > 0)
                                                @foreach(array_slice($restaurant->tags, 0, 3) as $tag)
                                                    <span class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                                        {{ $tag }}
                                                    </span>
                                                @endforeach
                                                @if(count($restaurant->tags) > 3)
                                                    <span class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                                        +{{ count($restaurant->tags) - 3 }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400">No tags</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    {{ $restaurants->links() }}
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No Restaurants Found</h3>
                <p class="text-gray-600 mb-6">
                    @if($search || $filterHalal || $filterPrice || $filterArea || $filterCuisine)
                        Try adjusting your filters to see more results
                    @else
                        The database is empty. Use the scraper to import restaurants!
                    @endif
                </p>
                @if($search || $filterHalal || $filterPrice || $filterArea || $filterCuisine)
                    <button
                        wire:click="clearFilters"
                        class="bg-[--color-pandan-green] hover:bg-[--color-pandan-green-light] text-white px-6 py-2 rounded-lg font-medium transition-colors"
                    >
                        Clear Filters
                    </button>
                @else
                    <a
                        href="/scraper"
                        class="inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
                    >
                        Go to Scraper
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
