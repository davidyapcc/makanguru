@props(['currentPersona'])

<div
    x-data="{ open: false }"
    class="bg-white rounded-xl shadow-md border border-gray-200 mb-6 overflow-hidden"
>
    <div
        @click="open = !open"
        class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 transition-colors"
    >
        <div class="flex items-center space-x-2">
            <h3 class="text-sm font-semibold text-gray-700">Choose Your Guide</h3>
            <span class="text-xs text-gray-500" x-show="!open">— {{
                $currentPersona === 'makcik' ? 'Mak Cik' :
                ($currentPersona === 'gymbro' ? 'Gym Bro' :
                ($currentPersona === 'atas' ? 'Atas Friend' :
                ($currentPersona === 'tauke' ? 'Tauke' :
                ($currentPersona === 'matmotor' ? 'Mat Motor' : 'Corporate Slave'))))
            }}</span>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs text-gray-400" x-show="!open">Who's helping you today?</span>
            <svg
                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                :class="open ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>

    <div x-show="open" x-transition class="p-4 pt-0">
        <!-- Time-based Suggestion Banner -->
        @php
            $suggestedPersona = app(\App\Livewire\ChatInterface::class)->getSuggestedPersona();
            $suggestionMessage = app(\App\Livewire\ChatInterface::class)->getSuggestionMessage();
        @endphp

        @if($suggestedPersona !== $currentPersona)
            <div class="mb-3 p-3 bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="text-xs font-medium text-gray-700 mb-1">💡 Perfect timing!</div>
                        <div class="text-xs text-gray-600">{{ $suggestionMessage }}</div>
                    </div>
                    <button
                        wire:click="switchPersona('{{ $suggestedPersona }}')"
                        class="ml-3 text-xs px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-full transition-colors whitespace-nowrap"
                    >
                        Switch
                    </button>
                </div>
            </div>
        @endif


        <!-- Row 1: Original 3 Personas -->
        <div class="grid grid-cols-3 gap-3 mb-3">
            <!-- Mak Cik -->
            <button
                wire:click="switchPersona('makcik')"
                class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'makcik' ? 'border-[--color-teh-tarik-brown] bg-gradient-to-br from-green-50 to-white shadow-md' : 'border-gray-200 bg-white hover:border-[--color-teh-tarik-brown-light] hover:shadow' }}"
            >
                <div class="text-3xl mb-2">👵</div>
                <div class="text-xs font-medium text-center text-gray-800">Mak Cik</div>
                <div class="text-[10px] text-gray-600 text-center mt-1">Value & Halal</div>
            </button>

            <!-- Gym Bro -->
            <button
                wire:click="switchPersona('gymbro')"
                class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'gymbro' ? 'border-[--color-pandan-green] bg-gradient-to-br from-blue-50 to-white shadow-md' : 'border-gray-200 bg-white hover:border-[--color-pandan-green-light] hover:shadow' }}"
            >
                <div class="text-3xl mb-2">💪</div>
                <div class="text-xs font-medium text-center text-gray-800">Gym Bro</div>
                <div class="text-[10px] text-gray-600 text-center mt-1">Protein Focus</div>
            </button>

            <!-- Atas Friend -->
            <button
                wire:click="switchPersona('atas')"
                class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'atas' ? 'border-[--color-sambal-red] bg-gradient-to-br from-red-50 to-white shadow-md' : 'border-gray-200 bg-white hover:border-[--color-sambal-red] hover:shadow' }}"
            >
                <div class="text-3xl mb-2">💅</div>
                <div class="text-xs font-medium text-center text-gray-800">Atas Friend</div>
                <div class="text-[10px] text-gray-600 text-center mt-1">Aesthetic Vibes</div>
            </button>
        </div>

        <!-- Row 2: New 3 Personas -->
        <div class="grid grid-cols-3 gap-3">
            <!-- Tauke -->
            <button
                wire:click="switchPersona('tauke')"
                class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'tauke' ? 'border-yellow-600 bg-gradient-to-br from-yellow-50 to-white shadow-md' : 'border-gray-200 bg-white hover:border-yellow-500 hover:shadow' }}"
            >
                <div class="text-3xl mb-2">🧧</div>
                <div class="text-xs font-medium text-center text-gray-800">Tauke</div>
                <div class="text-[10px] text-gray-600 text-center mt-1">Value & Speed</div>
            </button>

            <!-- Mat Motor -->
            <button
                wire:click="switchPersona('matmotor')"
                class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'matmotor' ? 'border-purple-600 bg-gradient-to-br from-purple-50 to-white shadow-md' : 'border-gray-200 bg-white hover:border-purple-500 hover:shadow' }}"
            >
                <div class="text-3xl mb-2">🏍️</div>
                <div class="text-xs font-medium text-center text-gray-800">Mat Motor</div>
                <div class="text-[10px] text-gray-600 text-center mt-1">Late Night</div>
            </button>

            <!-- Corporate Slave -->
            <button
                wire:click="switchPersona('corporate')"
                class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'corporate' ? 'border-gray-700 bg-gradient-to-br from-gray-50 to-white shadow-md' : 'border-gray-200 bg-white hover:border-gray-600 hover:shadow' }}"
            >
                <div class="text-3xl mb-2">💼</div>
                <div class="text-xs font-medium text-center text-gray-800">Corporate Slave</div>
                <div class="text-[10px] text-gray-600 text-center mt-1">Quick Lunch</div>
            </button>
        </div>
    </div>
</div>
