<?php

namespace App\Providers;

use App\AI\PromptBuilder;
use App\Contracts\AIRecommendationInterface;
use App\Services\GeminiService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for AI-related services.
 *
 * Binds the AIRecommendationInterface to concrete implementations,
 * allowing for easy swapping between AI providers (Gemini, OpenAI, etc.).
 */
class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the PromptBuilder as a singleton
        $this->app->singleton(PromptBuilder::class, function () {
            return new PromptBuilder();
        });

        // Bind the AIRecommendationInterface to GeminiService
        // This allows us to swap implementations without changing consumer code
        $this->app->bind(AIRecommendationInterface::class, function ($app) {
            return new GeminiService(
                $app->make(PromptBuilder::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
