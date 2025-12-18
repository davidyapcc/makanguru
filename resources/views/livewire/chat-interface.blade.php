<div
    x-data="{
        scrollToBottom() {
            $nextTick(() => {
                const chatContainer = this.$refs.chatContainer;
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            });
        }
    }"
    x-init="scrollToBottom()"
    class="flex flex-col h-[calc(100vh-12rem)]"
>
    <!-- Persona Switcher -->
    <x-persona-switcher :currentPersona="$currentPersona" />

    <!-- Filters Bar (Optional) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 mb-4">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="text-xs font-semibold text-gray-700">Filters:</div>

            <!-- Halal Filter -->
            <label class="flex items-center space-x-2 cursor-pointer">
                <input
                    type="checkbox"
                    wire:model.live="filterHalal"
                    class="w-4 h-4 text-[--color-pandan-green] border-gray-300 rounded focus:ring-[--color-pandan-green]"
                >
                <span class="text-xs text-gray-700">Halal Only</span>
            </label>

            <!-- Price Filter -->
            <select
                wire:model.live="filterPrice"
                class="text-xs border-gray-300 rounded-lg focus:ring-[--color-sambal-red] focus:border-[--color-sambal-red]"
            >
                <option value="">Any Price</option>
                <option value="budget">Budget (RM 10-20)</option>
                <option value="moderate">Moderate (RM 20-50)</option>
                <option value="expensive">Expensive (RM 50+)</option>
            </select>

            <!-- Area Filter -->
            <input
                type="text"
                wire:model.live="filterArea"
                placeholder="Area (e.g., Bangsar)"
                class="text-xs border-gray-300 rounded-lg focus:ring-[--color-sambal-red] focus:border-[--color-sambal-red] px-3 py-1"
            >

            <!-- Clear Filters -->
            @if($filterHalal || $filterPrice || $filterArea)
                <button
                    wire:click="$set('filterHalal', false); $set('filterPrice', null); $set('filterArea', null)"
                    class="text-xs text-[--color-sambal-red] hover:underline"
                >
                    Clear All
                </button>
            @endif
        </div>
    </div>

    <!-- Chat Messages Container -->
    <div
        x-ref="chatContainer"
        @scroll-to-bottom.window="scrollToBottom()"
        wire:poll.visible.5s
        class="flex-1 overflow-y-auto bg-gradient-to-b from-white to-[--color-nasi-lemak-cream] rounded-xl shadow-inner px-4 pt-2 pb-4 mb-4 space-y-4"
    >
        @if(empty($chatHistory))
            <!-- Welcome Message -->
            <div class="text-center py-12">
                <div class="text-6xl mb-4">🍜</div>
                <h2 class="text-2xl font-bold text-[--color-teh-tarik-brown] mb-2">
                    Makan Mana?
                </h2>
                <p class="text-gray-600 mb-4">
                    Ask me anything about where to eat in Malaysia!
                </p>
                <div class="mx-auto space-y-2 text-left">
                    <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                        <p class="text-sm text-gray-700"><strong>Try asking:</strong></p>
                        <ul class="text-xs text-gray-600 mt-2 space-y-1">
                            <li>• "Where can I get spicy food in PJ?"</li>
                            <li>• "I want halal breakfast near KLCC"</li>
                            <li>• "Instagram-worthy cafe with good coffee"</li>
                        </ul>
                    </div>
                </div>
            </div>
        @else
            <!-- Chat History -->
            @foreach($chatHistory as $index => $message)
                <div
                    x-init="if ({{ $index }} === {{ count($chatHistory) - 1 }}) { scrollToBottom(); }"
                >
                    <x-chat-bubble
                        :role="$message['role']"
                        :persona="$message['persona'] ?? $currentPersona"
                        :isFallback="$message['is_fallback'] ?? false"
                    >
                        {{ $message['content'] }}
                    </x-chat-bubble>
                </div>
            @endforeach
        @endif

        <!-- Loading Indicator -->
        <div wire:loading wire:target="sendMessage">
            <x-loading-spinner :persona="$currentPersona" />
        </div>
    </div>

    <!-- Input Area (Fixed at Bottom) -->
    <div class="bg-white rounded-xl shadow-lg border-2 border-gray-200 p-4">
        <form wire:submit="sendMessage" class="flex items-start space-x-3">
            <!-- Text Input -->
            <div class="flex-1">
                <textarea
                    wire:model="userQuery"
                    placeholder="Type your question... (e.g., 'Where to get nasi lemak?')"
                    rows="2"
                    class="w-full resize-none border-gray-300 rounded-lg focus:ring-2 focus:ring-[--color-sambal-red] focus:border-[--color-sambal-red] text-sm py-3 px-4 leading-relaxed"
                    @keydown.enter.prevent="if (!$event.shiftKey) { $el.closest('form').requestSubmit(); }"
                ></textarea>
                @error('userQuery')
                    <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Send Button -->
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                class="flex-shrink-0 bg-gradient-to-r from-[var(--color-sky-blue)] to-[var(--color-sky-blue-light)] text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2 h-[52px]"
            >
                <span>Send</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </form>

        <!-- Quick Actions -->
        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200">
            <div class="text-xs text-gray-500">
                Press <kbd class="px-2 py-0.5 bg-gray-100 border border-gray-300 rounded">Enter</kbd> to send
            </div>
            @if(!empty($chatHistory))
                <button
                    wire:click="clearChat"
                    wire:confirm="Are you sure you want to clear the chat history?"
                    class="text-xs text-[--color-sambal-red] hover:underline"
                >
                    Clear Chat
                </button>
            @endif
        </div>
    </div>
</div>
