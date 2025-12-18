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
            <span class="text-xs text-gray-500" x-show="!open">— {{ $currentPersona === 'makcik' ? 'Mak Cik' : ($currentPersona === 'gymbro' ? 'Gym Bro' : 'Atas Friend') }}</span>
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
        <div class="grid grid-cols-3 gap-3">
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
</div>
</div>
