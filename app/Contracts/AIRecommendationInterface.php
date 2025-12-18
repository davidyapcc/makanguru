<?php

namespace App\Contracts;

use App\DTOs\RecommendationDTO;
use Illuminate\Support\Collection;

/**
 * Interface for AI-powered restaurant recommendations.
 *
 * This contract defines the standard method for obtaining personalized
 * restaurant recommendations from various AI providers (Gemini, OpenAI, etc.).
 */
interface AIRecommendationInterface
{
    /**
     * Get AI-powered restaurant recommendation based on user query and persona.
     *
     * @param string $userQuery The user's natural language query (e.g., "I want spicy food in PJ")
     * @param string $persona The AI persona to use (makcik|gymbro|atas)
     * @param Collection $places Collection of Place models to use as context
     * @return RecommendationDTO
     * @throws \Exception When API call fails or returns invalid data
     */
    public function recommend(string $userQuery, string $persona, Collection $places): RecommendationDTO;

    /**
     * Check if the AI service is available and responding.
     *
     * @return bool True if service is available, false otherwise
     */
    public function healthCheck(): bool;
}
