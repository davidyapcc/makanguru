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
    <div
        x-data="{ open: false }"
        class="bg-white rounded-xl shadow-sm border border-gray-200 mb-4 overflow-hidden"
    >
        <div
            @click="open = !open"
            class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50 transition-colors"
        >
            <div class="flex items-center space-x-2">
                <div class="text-xs font-semibold text-gray-700">Settings:</div>
                <div class="flex items-center gap-2">
                    @php
                        $modelNames = [
                            'groq-openai' => 'GPT',
                            'groq-meta' => 'Llama'
                        ];
                    @endphp
                    <span class="px-1.5 py-0.5 bg-blue-100 text-[10px] text-blue-700 rounded-full font-medium">{{ $modelNames[$currentModel] ?? 'GPT' }}</span>
                    @if($filterHalal)
                        <span class="px-1.5 py-0.5 bg-green-100 text-[10px] text-green-700 rounded-full font-medium">Halal</span>
                    @endif
                    @if($filterPrice)
                        <span class="px-1.5 py-0.5 bg-red-100 text-[10px] text-red-700 rounded-full font-medium">{{ ucfirst($filterPrice) }}</span>
                    @endif
                    @if($filterArea)
                        <span class="px-1.5 py-0.5 bg-blue-100 text-[10px] text-blue-700 rounded-full font-medium">{{ $filterArea }}</span>
                    @endif
                    @if($filterHalal || $filterPrice || $filterArea)
                        <span class="px-1.5 py-0.5 bg-purple-100 text-[10px] text-purple-700 rounded-full font-medium">🤖 Smart</span>
                    @endif
                </div>
            </div>
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

        <div x-show="open" x-transition class="p-3 pt-0">
            <div class="flex flex-wrap gap-3 items-center">
                <!-- AI Model Selector -->
                <select
                    wire:model.live="currentModel"
                    class="text-xs border-gray-300 rounded-lg focus:ring-[--color-sky-blue] focus:border-[--color-sky-blue] font-medium"
                >
                    @if(!empty(config('services.groq.api_key')))
                        <option value="groq-openai">🧠 GPT (OpenAI via Groq)</option>
                        <option value="groq-meta">🦙 Llama (Meta via Groq)</option>
                    @endif
                </select>

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
    </div>

    <!-- Example Query Buttons -->
    @if(empty($chatHistory))
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <div class="text-xs font-semibold text-gray-700 mb-2">💡 Try asking:</div>
            <div class="flex flex-wrap gap-2">
                @if($currentPersona === 'makcik')
                    <button wire:click="$set('userQuery', 'Where to get halal nasi lemak in Damansara?')"
                            class="text-xs px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-800 rounded-full transition-colors">
                        🍛 Halal nasi lemak
                    </button>
                    <button wire:click="$set('userQuery', 'Value for money breakfast near me')"
                            class="text-xs px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-800 rounded-full transition-colors">
                        💰 Budget breakfast
                    </button>
                    <button wire:click="$set('userQuery', 'Family-friendly restaurant with generous portions')"
                            class="text-xs px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-800 rounded-full transition-colors">
                        👨‍👩‍👧‍👦 Family spots
                    </button>
                @elseif($currentPersona === 'gymbro')
                    <button wire:click="$set('userQuery', 'High protein meal prep spots')"
                            class="text-xs px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-full transition-colors">
                        💪 Protein meals
                    </button>
                    <button wire:click="$set('userQuery', 'Grilled chicken with no rice option')"
                            class="text-xs px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-full transition-colors">
                        🍗 Clean eating
                    </button>
                    <button wire:click="$set('userQuery', 'Quick post-workout meal')"
                            class="text-xs px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-full transition-colors">
                        ⚡ Post-workout fuel
                    </button>
                @elseif($currentPersona === 'atas')
                    <button wire:click="$set('userQuery', 'Instagram-worthy brunch spot with good vibes')"
                            class="text-xs px-3 py-1.5 bg-pink-100 hover:bg-pink-200 text-pink-800 rounded-full transition-colors">
                        📸 Aesthetic brunch
                    </button>
                    <button wire:click="$set('userQuery', 'Trendy cafe in Bangsar')"
                            class="text-xs px-3 py-1.5 bg-pink-100 hover:bg-pink-200 text-pink-800 rounded-full transition-colors">
                        ☕ Hipster cafe
                    </button>
                    <button wire:click="$set('userQuery', 'Upscale dining for special occasion')"
                            class="text-xs px-3 py-1.5 bg-pink-100 hover:bg-pink-200 text-pink-800 rounded-full transition-colors">
                        ✨ Fine dining
                    </button>
                @elseif($currentPersona === 'tauke')
                    <button wire:click="$set('userQuery', 'Quick business lunch with parking')"
                            class="text-xs px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 rounded-full transition-colors">
                        💼 Business lunch
                    </button>
                    <button wire:click="$set('userQuery', 'Value for money with fast service')"
                            class="text-xs px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 rounded-full transition-colors">
                        ⚡ Fast & worth it
                    </button>
                    <button wire:click="$set('userQuery', 'Air-conditioned place with round tables')"
                            class="text-xs px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 rounded-full transition-colors">
                        🧧 Business meeting
                    </button>
                @elseif($currentPersona === 'matmotor')
                    <button wire:click="$set('userQuery', 'Late night mamak with easy parking')"
                            class="text-xs px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-full transition-colors">
                        🏍️ Mamak supper
                    </button>
                    <button wire:click="$set('userQuery', '24/7 burger spot')"
                            class="text-xs px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-full transition-colors">
                        🍔 Midnight makan
                    </button>
                    <button wire:click="$set('userQuery', 'Cheap lepak spot with the gang')"
                            class="text-xs px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-full transition-colors">
                        🌙 Lepak vibes
                    </button>
                @elseif($currentPersona === 'corporate')
                    <button wire:click="$set('userQuery', 'Coffee place with WiFi near office')"
                            class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-full transition-colors">
                        ☕ Coffee & WiFi
                    </button>
                    <button wire:click="$set('userQuery', 'Quick lunch under 1 hour')"
                            class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-full transition-colors">
                        ⏰ Lunch break
                    </button>
                    <button wire:click="$set('userQuery', 'Comfort food for healing after meeting')"
                            class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-full transition-colors">
                        🍜 Healing food
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Chat Messages Container -->
    <div
        x-ref="chatContainer"
        @scroll-to-bottom.window="scrollToBottom()"
        wire:poll.visible.5s
        class="flex-1 overflow-y-auto bg-gradient-to-b from-white to-[--color-nasi-lemak-cream] rounded-xl shadow-inner px-4 pt-2 pb-4 mb-4 space-y-4"
    >
        @if(empty($chatHistory))
            <!-- Welcome Message -->
            <div class="text-center pt-4 pb-2">
                <div class="mb-4 flex items-center justify-center">
                    <img src="{{ asset('images/makanguru-transparent-bg-logo.png') }}" alt="MakanGuru Logo" class="h-32 w-auto object-contain">
                </div>
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
                    >{{ $message['content'] }}</x-chat-bubble>

                    <!-- Share Button (only for assistant responses) -->
                    @if($message['role'] === 'assistant')
                        <div class="flex justify-end mt-2 mb-2">
                            <button
                                wire:click="shareMessage({{ $index }})"
                                class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:border-[--color-sambal-red] hover:text-[--color-sambal-red] transition-all duration-200 shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                </svg>
                                Share This Vibe
                            </button>
                        </div>
                    @endif
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
        <!-- Rate Limit Warning -->
        @if($rateLimitMessage)
            <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-yellow-800 font-medium">{{ $rateLimitMessage }}</p>
                    @if($rateLimitResetIn)
                        <p class="text-xs text-yellow-700 mt-1">
                            Try again in <span class="font-bold">{{ $rateLimitResetIn }}</span> {{ $rateLimitResetIn === 1 ? 'second' : 'seconds' }}.
                        </p>
                    @endif
                </div>
            </div>
        @endif

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
                @if($rateLimitMessage) disabled @endif
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

    <!-- Social Card Preview Modal -->
    <x-social-card-modal :cardPreview="$cardPreview" />
</div>
