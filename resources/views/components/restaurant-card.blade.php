@props(['name', 'address', 'area', 'price', 'isHalal', 'cuisineType' => null, 'tags' => []])

@php
    $priceLabel = match($price) {
        'budget' => 'RM 10-20',
        'moderate' => 'RM 20-50',
        'expensive' => 'RM 50+',
        default => 'N/A',
    };
    $priceBadgeColor = match($price) {
        'budget' => 'bg-green-100 text-green-800',
        'moderate' => 'bg-yellow-100 text-yellow-800',
        'expensive' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800',
    };
@endphp

<div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 hover:shadow-lg transition-shadow duration-200">
    <!-- Header -->
    <div class="flex items-start justify-between mb-2">
        <div class="flex-1">
            <h3 class="font-bold text-lg text-gray-900">{{ $name }}</h3>
            @if($cuisineType)
                <p class="text-sm text-gray-600">{{ $cuisineType }}</p>
            @endif
        </div>
        <div class="flex flex-col items-end space-y-1">
            <span class="{{ $priceBadgeColor }} px-2 py-1 rounded-full text-xs font-medium">
                {{ $priceLabel }}
            </span>
            @if($isHalal)
                <span class="bg-[--color-pandan-green] text-white px-2 py-1 rounded-full text-xs font-medium">
                    Halal
                </span>
            @endif
        </div>
    </div>

    <!-- Address -->
    <div class="flex items-start space-x-2 mb-3">
        <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <div class="flex-1">
            <p class="text-sm text-gray-700">{{ $address }}</p>
            <p class="text-xs text-gray-500">{{ $area }}</p>
        </div>
    </div>

    <!-- Tags -->
    @if(count($tags) > 0)
        <div class="flex flex-wrap gap-2">
            @foreach($tags as $tag)
                <span class="bg-[--color-nasi-lemak-cream] text-[--color-teh-tarik-brown] px-2 py-1 rounded text-xs">
                    {{ $tag }}
                </span>
            @endforeach
        </div>
    @endif
</div>
