@props(['currentModel' => 'gemini'])

@php
    $models = [
        'gemini' => [
            'name' => 'Gemini',
            'provider' => 'Google',
            'icon' => '🤖',
            'color' => 'from-blue-500 to-blue-600',
            'status' => 'active',
        ],
        'groq-openai' => [
            'name' => 'GPT',
            'provider' => 'OpenAI via Groq',
            'icon' => '🧠',
            'color' => 'from-green-500 to-green-600',
            'status' => 'coming-soon',
        ],
        'groq-meta' => [
            'name' => 'Llama',
            'provider' => 'Meta via Groq',
            'icon' => '🦙',
            'color' => 'from-purple-500 to-purple-600',
            'status' => 'coming-soon',
        ],
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 mb-4">
    <div class="flex items-center justify-between mb-2">
        <div class="text-xs font-semibold text-gray-700">AI Model:</div>
        <div class="text-xs text-gray-500">
            Currently: <span class="font-medium text-[--color-sky-blue]">{{ $models[$currentModel]['provider'] }}</span>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-2">
        @foreach($models as $modelKey => $model)
            <button
                wire:click="switchModel('{{ $modelKey }}')"
                {{ $model['status'] === 'coming-soon' ? 'disabled' : '' }}
                class="relative flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{
                    $currentModel === $modelKey
                        ? 'border-[--color-sky-blue] bg-blue-50 shadow-md'
                        : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm'
                }} {{ $model['status'] === 'coming-soon' ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
            >
                <!-- Status Badge -->
                @if($model['status'] === 'coming-soon')
                    <div class="absolute -top-2 -right-2 bg-amber-500 text-white text-[8px] font-bold px-1.5 py-0.5 rounded-full">
                        SOON
                    </div>
                @endif

                <!-- Icon -->
                <div class="text-2xl mb-1">{{ $model['icon'] }}</div>

                <!-- Model Name -->
                <div class="text-xs font-bold text-gray-800 mb-0.5">{{ $model['name'] }}</div>

                <!-- Provider -->
                <div class="text-[10px] text-gray-500">{{ $model['provider'] }}</div>

                <!-- Active Indicator -->
                @if($currentModel === $modelKey)
                    <div class="absolute bottom-1 w-1.5 h-1.5 bg-[--color-sky-blue] rounded-full"></div>
                @endif
            </button>
        @endforeach
    </div>
</div>
