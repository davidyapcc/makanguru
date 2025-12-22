<?php

namespace App\Livewire;

use App\Contracts\AIRecommendationInterface;
use App\Models\Place;
use App\Services\GeminiService;
use App\Services\GroqService;
use App\Services\PlaceCacheService;
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
     * Current active persona (makcik|gymbro|atas).
     */
    public string $currentPersona = 'makcik';

    /**
     * Current AI model/provider (gemini|groq-openai|groq-meta).
     */
    public string $currentModel = 'gemini';

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
     * Initialize component and load configuration.
     */
    public function mount(): void
    {
        $this->initializeRateLimiting();
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
            // Add error message to chat
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
        if (in_array($persona, ['makcik', 'gymbro', 'atas'])) {
            $this->currentPersona = $persona;
        }
    }

    /**
     * Switch to a different AI model/provider.
     */
    public function switchModel(string $model): void
    {
        if (in_array($model, ['gemini', 'groq-openai', 'groq-meta'])) {
            $this->currentModel = $model;
        }
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
            default => "Please wait {$seconds} seconds before sending another message. You've reached the rate limit.",
        };
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.chat-interface');
    }
}
