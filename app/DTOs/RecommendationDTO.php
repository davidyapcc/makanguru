<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Data Transfer Object for AI Recommendations.
 *
 * Ensures type safety when passing recommendation data between layers.
 */
class RecommendationDTO implements Arrayable
{
    /**
     * Create a new RecommendationDTO instance.
     *
     * @param string $recommendation The AI-generated recommendation text
     * @param string $persona The persona used (makcik|gymbro|atas)
     * @param array<string> $suggestedPlaces Array of suggested place names/IDs
     * @param array<string, mixed> $metadata Additional metadata (tokens used, response time, etc.)
     */
    public function __construct(
        public readonly string $recommendation,
        public readonly string $persona,
        public readonly array $suggestedPlaces = [],
        public readonly array $metadata = []
    ) {
    }

    /**
     * Create DTO from Gemini API response.
     *
     * @param array<string, mixed> $apiResponse
     * @param string $persona
     * @return self
     */
    public static function fromGeminiResponse(array $apiResponse, string $persona): self
    {
        $recommendation = $apiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Extract suggested places from the recommendation text (if AI mentions specific names)
        $suggestedPlaces = self::extractPlaceNames($recommendation);

        $metadata = [
            'tokens_used' => $apiResponse['usageMetadata']['totalTokenCount'] ?? 0,
            'model' => 'gemini-2.5-flash',
            'timestamp' => now()->toIso8601String(),
        ];

        return new self($recommendation, $persona, $suggestedPlaces, $metadata);
    }

    /**
     * Create a fallback DTO when API fails.
     *
     * @param string $persona
     * @return self
     */
    public static function fallback(string $persona): self
    {
        $fallbackMessages = [
            'makcik' => "Aiyah, Mak Cik's brain got too tired lah! But you know what, just go to Village Park for nasi lemak. Cannot go wrong one!",
            'gymbro' => "Bro, system's down but you know the drill - hit up any chicken rice place, get extra protein, skip the carbs. We'll be back stronger!",
            'atas' => "Darling, technical difficulties. But honestly? Just go to Bangsar, walk into any minimalist cafe, order the avocado toast. You'll survive.",
        ];

        return new self(
            recommendation: $fallbackMessages[$persona] ?? "Sorry, our recommendation system is temporarily unavailable. Please try again later.",
            persona: $persona,
            suggestedPlaces: [],
            metadata: [
                'is_fallback' => true,
                'timestamp' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Extract place names mentioned in the recommendation.
     *
     * @param string $text
     * @return array<string>
     */
    private static function extractPlaceNames(string $text): array
    {
        // Simple extraction - can be enhanced with NLP later
        // Look for capitalized words that might be place names
        preg_match_all('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\b/', $text, $matches);

        return array_unique($matches[0] ?? []);
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'recommendation' => $this->recommendation,
            'persona' => $this->persona,
            'suggested_places' => $this->suggestedPlaces,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if this is a fallback response.
     *
     * @return bool
     */
    public function isFallback(): bool
    {
        return $this->metadata['is_fallback'] ?? false;
    }

    /**
     * Get the tokens used (if available).
     *
     * @return int
     */
    public function getTokensUsed(): int
    {
        return $this->metadata['tokens_used'] ?? 0;
    }
}
