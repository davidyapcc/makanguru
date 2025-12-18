<?php

namespace App\Livewire;

use App\Contracts\AIRecommendationInterface;
use App\Models\Place;
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
     * The AI recommendation service.
     */
    private AIRecommendationInterface $aiService;

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
     * Boot the component with dependency injection.
     */
    public function boot(AIRecommendationInterface $aiService): void
    {
        $this->aiService = $aiService;
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

        // Add user message to history
        $this->chatHistory[] = [
            'role' => 'user',
            'content' => $this->userQuery,
            'persona' => $this->currentPersona,
            'model' => $this->currentModel,
        ];

        // Set loading state
        $this->isLoading = true;

        try {
            // Get filtered places based on current filters
            $places = $this->getFilteredPlaces();

            // Get AI recommendation
            $recommendation = $this->aiService->recommend(
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
     */
    private function getFilteredPlaces(): \Illuminate\Support\Collection
    {
        $query = Place::query();

        if ($this->filterHalal) {
            $query->halalOnly();
        }

        if ($this->filterPrice) {
            $query->byPrice($this->filterPrice);
        }

        if ($this->filterArea) {
            $query->inArea($this->filterArea);
        }

        return $query->get();
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
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.chat-interface');
    }
}
