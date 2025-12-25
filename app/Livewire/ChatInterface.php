<?php

namespace App\Livewire;

use App\Contracts\AIRecommendationInterface;
use App\Models\Place;
use App\Services\GeminiService;
use App\Services\GroqService;
use App\Services\PlaceCacheService;
use App\Services\SocialCardService;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * ChatInterface Livewire Component
 *
 * Provides a conversational interface for users to get AI-powered
 * restaurant recommendations using different Malaysian personas.
 */
class ChatInterface extends Component
{
    /**
     * User's current query input.
     */
    #[Validate('required|string|min:3|max:500')]
    public string $userQuery = '';

    /**
     * Current active persona (makcik|gymbro|atas|tauke|matmotor|corporate).
     */
    public string $currentPersona = 'makcik';

    /**
     * Current AI model/provider (gemini|groq-openai|groq-meta).
     */
    public string $currentModel;

    /**
     * Chat history array.
     * Each item: ['role' => 'user|assistant', 'content' => string, 'persona' => string]
     */
    public array $chatHistory = [];

    /**
     * Filter: Only show halal places.
     */
    public bool $filterHalal = false;

    /**
     * Filter: Price range (null|budget|moderate|expensive).
     */
    public ?string $filterPrice = null;

    /**
     * Filter: Area/location.
     */
    public ?string $filterArea = null;

    /**
     * Indicates if a request is being processed.
     */
    public bool $isLoading = false;

    /**
     * Rate limiting: Maximum messages allowed per time window.
     * Loaded from config/chat.php
     */
    private int $maxMessagesPerWindow;

    /**
     * Rate limiting: Time window in seconds.
     * Loaded from config/chat.php
     */
    private int $rateLimitWindow;

    /**
     * Rate limiting error message.
     */
    public ?string $rateLimitMessage = null;

    /**
     * Seconds until rate limit resets.
     */
    public ?int $rateLimitResetIn = null;

    /**
     * Card being previewed (if any).
     */
    public ?array $cardPreview = null;

    /**
     * Initialize component and load configuration.
     */
    public function mount(): void
    {
        // Always start with OpenAI (Groq) model
        $this->currentModel = 'groq-openai';
        $this->initializeRateLimiting();
        $this->trackPersonaUsage($this->currentPersona);
        $this->applyPersonaFilters($this->currentPersona);
    }

    /**
     * Initialize rate limiting configuration.
     * Called in mount() and also in isRateLimited() to ensure values are set.
     */
    private function initializeRateLimiting(): void
    {
        // Only initialize if not already set
        if (!isset($this->maxMessagesPerWindow)) {
            $this->maxMessagesPerWindow = config('chat.rate_limit.max_messages', 5);
        }

        if (!isset($this->rateLimitWindow)) {
            $this->rateLimitWindow = config('chat.rate_limit.window_seconds', 60);
        }
    }

    /**
     * Get the AI service based on current model.
     */
    private function getAiService(): AIRecommendationInterface
    {
        $service = match ($this->currentModel) {
            'groq-openai', 'groq-meta' => app(GroqService::class),
            default => app(GeminiService::class),
        };

        // If it's Groq, we can set the specific model based on selection
        if ($service instanceof GroqService) {
            $model = match ($this->currentModel) {
                'groq-openai' => config('services.groq.models.openai'),
                'groq-meta' => config('services.groq.models.meta'),
                default => config('services.groq.models.default'),
            };
            $service->setModel($model);
        }

        return $service;
    }

    /**
     * Send a message and get AI recommendation.
     */
    public function sendMessage(): void
    {
        $this->validate();

        if (empty(trim($this->userQuery))) {
            return;
        }

        // Check rate limit
        if ($this->isRateLimited()) {
            return;
        }

        // Clear any previous rate limit messages
        $this->rateLimitMessage = null;
        $this->rateLimitResetIn = null;

        // Add user message to history
        $this->chatHistory[] = [
            'role' => 'user',
            'content' => $this->userQuery,
            'persona' => $this->currentPersona,
            'model' => $this->currentModel,
        ];

        // Set loading state
        $this->isLoading = true;

        // Resolve service dynamically
        $aiService = $this->getAiService();

        logger()->info('ChatInterface: Using AI service', [
            'model' => $this->currentModel,
            'service' => get_class($aiService)
        ]);

        try {
            // Get filtered places based on current filters
            $places = $this->getFilteredPlaces();

            // Get AI recommendation
            $recommendation = $aiService->recommend(
                $this->userQuery,
                $this->currentPersona,
                $places
            );

            // Add AI response to history
            $this->chatHistory[] = [
                'role' => 'assistant',
                'content' => $recommendation->recommendation,
                'persona' => $this->currentPersona,
                'model' => $this->currentModel,
                'is_fallback' => $recommendation->isFallback(),
            ];
        } catch (\Exception $e) {
            // Check if we can fallback to Meta model
            $canFallback = $this->currentModel === 'groq-openai' &&
                          !empty(config('services.groq.api_key'));

            if ($canFallback) {
                logger()->warning('ChatInterface: OpenAI failed, falling back to Meta', [
                    'error' => $e->getMessage(),
                    'persona' => $this->currentPersona,
                ]);

                try {
                    // Fallback to Meta model
                    $this->currentModel = 'groq-meta';
                    $places = $this->getFilteredPlaces();
                    $metaService = $this->getAiService();

                    $recommendation = $metaService->recommend(
                        $this->userQuery,
                        $this->currentPersona,
                        $places
                    );

                    // Add AI response to history
                    $this->chatHistory[] = [
                        'role' => 'assistant',
                        'content' => $recommendation->recommendation,
                        'persona' => $this->currentPersona,
                        'model' => $this->currentModel,
                        'is_fallback' => $recommendation->isFallback(),
                    ];

                    logger()->info('ChatInterface: Successfully used Meta fallback');

                    // Reset back to OpenAI for next request
                    $this->currentModel = 'groq-openai';

                    // Exit the catch block successfully
                    return;
                } catch (\Exception $metaError) {
                    logger()->error('ChatInterface: Meta fallback also failed', [
                        'error' => $metaError->getMessage(),
                    ]);

                    // Reset back to OpenAI
                    $this->currentModel = 'groq-openai';
                }
            }

            // If fallback failed or not available, show error message
            $this->chatHistory[] = [
                'role' => 'assistant',
                'content' => $this->getFallbackMessage($this->currentPersona),
                'persona' => $this->currentPersona,
                'model' => $this->currentModel,
                'is_fallback' => true,
            ];

            // Log the error
            logger()->error('ChatInterface: AI recommendation failed', [
                'error' => $e->getMessage(),
                'persona' => $this->currentPersona,
                'query' => $this->userQuery,
            ]);
        } finally {
            // Reset loading state and clear input
            $this->isLoading = false;
            $this->userQuery = '';
        }
    }

    /**
     * Switch to a different persona.
     */
    public function switchPersona(string $persona): void
    {
        if (in_array($persona, ['makcik', 'gymbro', 'atas', 'tauke', 'matmotor', 'corporate'])) {
            $this->currentPersona = $persona;
            $this->trackPersonaUsage($persona);
            $this->applyPersonaFilters($persona);
        }
    }

    /**
     * Get a time-based persona suggestion.
     * Suggests appropriate personas based on current time of day.
     *
     * @return string Suggested persona name
     */
    public function getSuggestedPersona(): string
    {
        $hour = (int) now()->format('H');

        return match (true) {
            // Late night (10PM - 4AM): Mat Motor (rempit hours)
            $hour >= 22 || $hour < 4 => 'matmotor',

            // Early morning (4AM - 9AM): Mak Cik (breakfast & value)
            $hour >= 4 && $hour < 9 => 'makcik',

            // Work hours (9AM - 6PM): Corporate Slave (lunch break)
            $hour >= 9 && $hour < 18 => 'corporate',

            // Evening (6PM - 8PM): Gym Bro (post-workout meal)
            $hour >= 18 && $hour < 20 => 'gymbro',

            // Dinner time (8PM - 10PM): Tauke (business dinner) or Atas (date night)
            // Alternate based on day of week: weekday = Tauke, weekend = Atas
            $hour >= 20 && $hour < 22 => now()->isWeekend() ? 'atas' : 'tauke',

            default => 'makcik', // Fallback
        };
    }

    /**
     * Get a friendly message explaining the time-based suggestion.
     *
     * @return string Explanation message
     */
    public function getSuggestionMessage(): string
    {
        $hour = (int) now()->format('H');

        return match (true) {
            $hour >= 22 || $hour < 4 => '🏍️ Late night vibes! Mat Motor knows the best supper spots.',
            $hour >= 4 && $hour < 9 => '👵 Good morning! Mak Cik has breakfast recommendations.',
            $hour >= 9 && $hour < 18 => '💼 Lunch break! Corporate Slave finds quick office-friendly spots.',
            $hour >= 18 && $hour < 20 => '💪 Post-gym time! Gym Bro has high-protein options.',
            $hour >= 20 && $hour < 22 && now()->isWeekend() => '💅 Weekend dinner! Atas Friend knows the aesthetic spots.',
            $hour >= 20 && $hour < 22 => '🧧 Dinner time! Tauke recommends efficient business-friendly places.',
            default => '👵 Anytime is makan time! Mak Cik is here to help.',
        };
    }

    /**
     * Clear chat history.
     */
    public function clearChat(): void
    {
        $this->chatHistory = [];
        $this->userQuery = '';
    }

    /**
     * Get filtered places based on current filter settings.
     * Uses Redis caching to reduce database load.
     */
    private function getFilteredPlaces(): \Illuminate\Support\Collection
    {
        $cacheService = app(PlaceCacheService::class);

        return $cacheService->getPlaces(
            halalOnly: $this->filterHalal,
            price: $this->filterPrice,
            area: $this->filterArea,
            tags: []
        );
    }

    /**
     * Get persona-specific fallback message.
     */
    private function getFallbackMessage(string $persona): string
    {
        return match ($persona) {
            'makcik' => "Aiyooo, Mak Cik's phone no signal lah! But nevermind, you just go find some nice nasi lemak nearby. Don't go hungry ah!",
            'gymbro' => "Bro, my brain not loading sia. Connection down. But you know what's always good? High protein chicken rice. Can never go wrong, padu!",
            'atas' => "Darling, my connection is being absolutely dreadful right now. Perhaps just pop over to your usual spot? I trust your taste is impeccable anyway.",
            default => "Sorry, I'm having trouble connecting right now. Please try again!",
        };
    }

    /**
     * Check if the user has exceeded the rate limit.
     */
    private function isRateLimited(): bool
    {
        // Ensure rate limiting is initialized
        $this->initializeRateLimiting();

        $sessionKey = 'chat_messages_' . session()->getId();
        $messages = session()->get($sessionKey, []);

        // Remove messages older than the rate limit window
        $now = now();
        $messages = array_filter($messages, function ($timestamp) use ($now) {
            return $now->diffInSeconds($timestamp) < $this->rateLimitWindow;
        });

        // Check if user has exceeded the limit
        if (count($messages) >= $this->maxMessagesPerWindow) {
            // Calculate when the oldest message will expire
            $oldestMessage = min($messages);
            $resetIn = $this->rateLimitWindow - $now->diffInSeconds($oldestMessage);

            $this->rateLimitResetIn = max(1, (int) ceil($resetIn));
            $this->rateLimitMessage = $this->getRateLimitMessage($this->currentPersona, $this->rateLimitResetIn);

            // Update session with filtered messages
            session()->put($sessionKey, $messages);

            logger()->info('ChatInterface: Rate limit exceeded', [
                'session_id' => session()->getId(),
                'messages_count' => count($messages),
                'reset_in' => $this->rateLimitResetIn,
            ]);

            return true;
        }

        // Add current timestamp to messages
        $messages[] = $now;
        session()->put($sessionKey, $messages);

        return false;
    }

    /**
     * Get persona-specific rate limit message.
     */
    private function getRateLimitMessage(string $persona, int $seconds): string
    {
        return match ($persona) {
            'makcik' => "Adoi! Slow down lah! Mak Cik cannot keep up with you asking so fast. Give me {$seconds} seconds to rest, okay? Don't be so impatient!",
            'gymbro' => "Woah bro! Too much too fast sia! Even protein shakes need rest time between sets. Chill for {$seconds} seconds, then we go again. No rush!",
            'atas' => "Darling, please! One must not be so... eager. Quality takes time. Give me {$seconds} seconds to compose myself. Patience is a virtue, after all.",
            'tauke' => "Wa tell you ah, time is money! But even business deal need time to process. Wait {$seconds} seconds first, then we talk. Cannot rush rush like that!",
            'matmotor' => "Member, slow down lah! Even my motor need to cool down after spinning. Wait {$seconds} seconds, then we lepak again. Don't koyak yourself!",
            'corporate' => "Okay look, I know you're stressed, but I'm also rate-limited by the system. Give me {$seconds} seconds to recover from that last meeting... I mean message. We all need healing time.",
            default => "Please wait {$seconds} seconds before sending another message. You've reached the rate limit.",
        };
    }

    /**
     * Track persona usage for analytics.
     * Stores usage data in session for basic analytics.
     *
     * @param string $persona The persona being used
     */
    private function trackPersonaUsage(string $persona): void
    {
        $analytics = session('persona_analytics', []);

        // Initialize persona data if not exists
        if (!isset($analytics[$persona])) {
            $analytics[$persona] = [
                'count' => 0,
                'last_used' => null,
                'first_used' => now()->toIso8601String(),
            ];
        }

        // Increment usage count
        $analytics[$persona]['count']++;
        $analytics[$persona]['last_used'] = now()->toIso8601String();

        // Track time of day usage
        $hour = (int) now()->format('H');
        $timeSlot = match (true) {
            $hour >= 4 && $hour < 9 => 'morning',
            $hour >= 9 && $hour < 12 => 'late_morning',
            $hour >= 12 && $hour < 14 => 'lunch',
            $hour >= 14 && $hour < 18 => 'afternoon',
            $hour >= 18 && $hour < 21 => 'evening',
            $hour >= 21 || $hour < 4 => 'night',
            default => 'other',
        };

        if (!isset($analytics[$persona]['time_slots'])) {
            $analytics[$persona]['time_slots'] = [];
        }

        $analytics[$persona]['time_slots'][$timeSlot] =
            ($analytics[$persona]['time_slots'][$timeSlot] ?? 0) + 1;

        session(['persona_analytics' => $analytics]);
    }

    /**
     * Apply smart filters based on persona characteristics.
     * Automatically adjusts filters to match persona preferences.
     *
     * @param string $persona The persona to apply filters for
     */
    private function applyPersonaFilters(string $persona): void
    {
        match ($persona) {
            'makcik' => $this->applyMakCikFilters(),
            'gymbro' => $this->applyGymBroFilters(),
            'atas' => $this->applyAtasFilters(),
            'tauke' => $this->applyTaukeFilters(),
            'matmotor' => $this->applyMatMotorFilters(),
            'corporate' => $this->applyCorporateFilters(),
            default => null,
        };
    }

    /**
     * Apply Mak Cik persona filters: Halal + Budget/Moderate prices.
     */
    private function applyMakCikFilters(): void
    {
        $this->filterHalal = true;
        $this->filterPrice = null; // Show all prices, but prefer budget
        $this->filterArea = null;
    }

    /**
     * Apply Gym Bro persona filters: No specific halal filter, moderate prices.
     */
    private function applyGymBroFilters(): void
    {
        $this->filterHalal = false;
        $this->filterPrice = 'moderate';
        $this->filterArea = null;
    }

    /**
     * Apply Atas Friend persona filters: Expensive only.
     */
    private function applyAtasFilters(): void
    {
        $this->filterHalal = false;
        $this->filterPrice = 'expensive';
        $this->filterArea = null;
    }

    /**
     * Apply Tauke persona filters: Value for money (budget/moderate).
     */
    private function applyTaukeFilters(): void
    {
        $this->filterHalal = false;
        $this->filterPrice = 'moderate';
        $this->filterArea = null;
    }

    /**
     * Apply Mat Motor persona filters: Budget only.
     */
    private function applyMatMotorFilters(): void
    {
        $this->filterHalal = false;
        $this->filterPrice = 'budget';
        $this->filterArea = null;
    }

    /**
     * Apply Corporate Slave persona filters: Moderate prices.
     */
    private function applyCorporateFilters(): void
    {
        $this->filterHalal = false;
        $this->filterPrice = 'moderate';
        $this->filterArea = null;
    }

    /**
     * Get persona analytics for current session.
     *
     * @return array Analytics data
     */
    public function getPersonaAnalytics(): array
    {
        return session('persona_analytics', []);
    }

    /**
     * Get the most popular persona in current session.
     *
     * @return string|null Most used persona or null
     */
    public function getMostPopularPersona(): ?string
    {
        $analytics = $this->getPersonaAnalytics();

        if (empty($analytics)) {
            return null;
        }

        $sorted = collect($analytics)->sortByDesc('count');

        return $sorted->keys()->first();
    }

    /**
     * Generate a shareable social card from a message.
     */
    public function shareMessage(int $index): void
    {
        if (!isset($this->chatHistory[$index])) {
            return;
        }

        $message = $this->chatHistory[$index];

        // Only allow sharing assistant responses
        if ($message['role'] !== 'assistant') {
            return;
        }

        // Find the corresponding user query
        $userQuery = '';
        if ($index > 0 && isset($this->chatHistory[$index - 1])) {
            $userQuery = $this->chatHistory[$index - 1]['content'];
        }

        try {
            $cardService = app(SocialCardService::class);
            $filename = $cardService->generateCard(
                $message['content'],
                $message['persona'],
                $userQuery
            );

            $this->cardPreview = [
                'url' => $cardService->getCardUrl($filename),
                'filename' => $filename,
                'message_index' => $index,
            ];

            logger()->info('ChatInterface: Social card generated', [
                'filename' => $filename,
                'persona' => $message['persona'],
            ]);
        } catch (\Exception $e) {
            logger()->error('ChatInterface: Failed to generate social card', [
                'error' => $e->getMessage(),
                'message_index' => $index,
            ]);

            // Could add a flash message here if needed
        }
    }

    /**
     * Close the card preview modal.
     */
    public function closeCardPreview(): void
    {
        $this->cardPreview = null;
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.chat-interface');
    }
}
