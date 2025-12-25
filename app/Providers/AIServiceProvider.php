<?php

namespace App\Providers;

use App\AI\PromptBuilder;
use App\Contracts\AIRecommendationInterface;
use App\Services\GeminiService;
use App\Services\GroqService;
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

        // Register concrete implementations
        $this->app->singleton(GeminiService::class, function ($app) {
            return new GeminiService($app->make(PromptBuilder::class));
        });

        $this->app->singleton(GroqService::class, function ($app) {
            return new GroqService($app->make(PromptBuilder::class));
        });

        // Bind the AIRecommendationInterface based on configuration
        // This remains for backward compatibility or default usage
        $this->app->bind(AIRecommendationInterface::class, function ($app) {
            $provider = env('AI_PROVIDER', 'groq');

            return match ($provider) {
                'gemini' => $app->make(GeminiService::class),
                default => $app->make(GroqService::class),
            };
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
