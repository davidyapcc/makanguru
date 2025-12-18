@props(['currentPersona'])

<div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">Choose Your Guide</h3>
        <span class="text-xs text-gray-500">Who's helping you today?</span>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <!-- Mak Cik -->
        <button
            wire:click="switchPersona('makcik')"
            class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'makcik' ? 'border-[--color-teh-tarik-brown] bg-gradient-to-br from-[--color-nasi-lemak-cream] to-white shadow-md' : 'border-gray-200 bg-white hover:border-[--color-teh-tarik-brown-light] hover:shadow' }}"
        >
            <div class="text-3xl mb-2">👵</div>
            <div class="text-xs font-medium text-center text-gray-800">Mak Cik</div>
            <div class="text-[10px] text-gray-600 text-center mt-1">Value & Halal</div>
        </button>

        <!-- Gym Bro -->
        <button
            wire:click="switchPersona('gymbro')"
            class="flex flex-col items-center p-3 rounded-lg border-2 transition-all duration-200 {{ $currentPersona === 'gymbro' ? 'border-[--color-pandan-green] bg-gradient-to-br from-green-50 to-white shadow-md' : 'border-gray-200 bg-white hover:border-[--color-pandan-green-light] hover:shadow' }}"
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
