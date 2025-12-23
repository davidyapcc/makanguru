@props(['persona' => 'makcik'])

@php
    $loadingText = match($persona) {
        'makcik' => 'Mak Cik is putting on her spectacles...',
        'gymbro' => 'Bro is thinking... loading the gains...',
        'atas' => 'Darling, let me consult my notes...',
        'tauke' => 'Calculating ROI and checking reviews...',
        'matmotor' => 'Checking parking spots and late night options...',
        'corporate' => 'Finding WiFi and coffee... need caffeine...',
        default => 'Thinking...',
    };
    $personaEmoji = match($persona) {
        'makcik' => '👵',
        'gymbro' => '💪',
        'atas' => '💅',
        'tauke' => '🧧',
        'matmotor' => '🏍️',
        'corporate' => '💼',
        default => '🤖',
    };
@endphp

<div class="flex justify-start mb-4 animate-fadeIn">
    <div class="flex items-start max-w-[85%] space-x-2">
        <!-- Avatar -->
        @php
            $avatarGradient = match($persona) {
                'makcik' => 'from-[--color-teh-tarik-brown-light] to-[--color-teh-tarik-brown]',
                'gymbro' => 'from-[--color-pandan-green-light] to-[--color-pandan-green]',
                'atas' => 'from-[--color-sambal-red] to-[--color-sambal-red-dark]',
                'tauke' => 'from-yellow-400 to-yellow-600',
                'matmotor' => 'from-purple-400 to-purple-600',
                'corporate' => 'from-gray-400 to-gray-600',
                default => 'from-blue-400 to-blue-600',
            };
            $personaDisplayName = match($persona) {
                'makcik' => 'Mak Cik',
                'gymbro' => 'Gym Bro',
                'atas' => 'Atas Friend',
                'tauke' => 'Tauke',
                'matmotor' => 'Mat Motor',
                'corporate' => 'Corporate Slave',
                default => ucfirst($persona),
            };
        @endphp
        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br {{ $avatarGradient }} flex items-center justify-center text-white text-xl shadow-md animate-pulse">
            {{ $personaEmoji }}
        </div>

        <!-- Typing Indicator -->
        <div class="ml-2 flex flex-col">
            <div class="text-xs text-gray-600 mb-1">
                {{ $personaDisplayName }}
            </div>

            <div class="rounded-2xl px-4 py-3 bg-white border border-gray-200 shadow-sm rounded-tl-sm">
                <div class="flex items-center space-x-2">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-[--color-teh-tarik-brown-light] rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-[--color-teh-tarik-brown-light] rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-[--color-teh-tarik-brown-light] rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                    <span class="text-xs text-gray-500 italic">{{ $loadingText }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
