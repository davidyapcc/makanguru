@props(['role', 'persona' => 'makcik', 'isFallback' => false])

@php
    $isUser = $role === 'user';
    $personaEmoji = match($persona) {
        'makcik' => '👵',
        'gymbro' => '💪',
        'atas' => '💅',
        default => '🤖',
    };
    $personaName = match($persona) {
        'makcik' => 'Mak Cik',
        'gymbro' => 'Gym Bro',
        'atas' => 'Atas Friend',
        default => 'AI',
    };
@endphp

<div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }} mb-4 animate-fadeIn">
    <div class="flex items-start max-w-[85%] {{ $isUser ? 'flex-row-reverse' : 'flex-row' }} {{ $isUser ? 'space-x-reverse space-x-2' : 'space-x-2' }}">
        <!-- Avatar -->
        @if(!$isUser)
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br {{ $persona === 'makcik' ? 'from-[var(--color-teh-tarik-brown-light)] to-[var(--color-teh-tarik-brown)]' : ($persona === 'gymbro' ? 'from-[var(--color-pandan-green-light)] to-[var(--color-pandan-green)]' : 'from-[var(--color-sambal-red)] to-[var(--color-sambal-red-dark)]') }} flex items-center justify-center text-white text-xl shadow-md">
                {{ $personaEmoji }}
            </div>
        @endif

        <!-- Message Bubble -->
        <div class="flex flex-col">
            @if(!$isUser)
                <div class="text-xs text-gray-600 mb-1">
                    {{ $personaName }}
                </div>
            @endif

            <div class="rounded-2xl px-4 py-3 shadow-sm {{ $isUser ? 'bg-[var(--color-sky-blue)] text-white rounded-tr-sm' : 'bg-white text-gray-800 rounded-tl-sm border border-gray-200' }}">
                @if($isFallback)
                    <div class="flex items-center space-x-2 text-amber-600 text-xs mb-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Connection issue</span>
                    </div>
                @endif

                <div class="text-sm leading-relaxed whitespace-pre-wrap">{{ $slot }}</div>
            </div>
        </div>

        <!-- User Avatar (You) -->
        @if($isUser)
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center text-white text-xl shadow-md">
                👤
            </div>
        @endif
    </div>
</div>
