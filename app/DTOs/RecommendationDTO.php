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
        $recommendation = trim($apiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');

        // Extract suggested places from the recommendation text (if AI mentions specific names)
        $suggestedPlaces = self::extractPlaceNames($recommendation);

        $metadata = [
            'tokens_used' => $apiResponse['usageMetadata']['totalTokenCount'] ?? 0,
            'model' => $apiResponse['_model_used'] ?? 'gemini-2.5-flash',
            'timestamp' => now()->toIso8601String(),
        ];

        return new self($recommendation, $persona, $suggestedPlaces, $metadata);
    }

    /**
     * Create DTO from Groq (OpenAI-compatible) API response.
     *
     * @param array<string, mixed> $apiResponse
     * @param string $persona
     * @return self
     */
    public static function fromGroqResponse(array $apiResponse, string $persona): self
    {
        $recommendation = trim($apiResponse['choices'][0]['message']['content'] ?? '');

        // Extract suggested places from the recommendation text
        $suggestedPlaces = self::extractPlaceNames($recommendation);

        $metadata = [
            'tokens_used' => $apiResponse['usage']['total_tokens'] ?? 0,
            'model' => $apiResponse['model'] ?? 'unknown',
            'timestamp' => now()->toIso8601String(),
            'provider' => 'groq',
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
            'tauke' => "Wa lao eh! System down, wasting time! Cincai go kopitiam nearby lah, order economy rice, fast and worth it. Come back later try again!",
            'matmotor' => "Member, connection koyak already! No worries lah, just ride to nearest mamak, order teh tarik and roti canai. We lepak again later, on!",
            'corporate' => "System's down. Great. Another Monday. Just grab whatever's nearest from Grab Food lah, I need a coffee break too. Try again after lunch.",
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

    /**
     * Format the recommendation with persona-specific template.
     * Adds structured formatting based on persona style.
     *
     * @return string Formatted recommendation
     */
    public function getFormattedRecommendation(): string
    {
        return match ($this->persona) {
            'makcik' => $this->formatMakCikStyle(),
            'gymbro' => $this->formatGymBroStyle(),
            'atas' => $this->formatAtasStyle(),
            'tauke' => $this->formatTaukeStyle(),
            'matmotor' => $this->formatMatMotorStyle(),
            'corporate' => $this->formatCorporateStyle(),
            default => $this->recommendation,
        };
    }

    /**
     * Format in Mak Cik style: Warm, caring, with emojis.
     */
    private function formatMakCikStyle(): string
    {
        $text = $this->recommendation;

        // Add caring emoji at the start if not present
        if (!str_contains($text, '👵') && !str_contains($text, '❤️')) {
            $text = "👵 " . $text;
        }

        return $text;
    }

    /**
     * Format in Gym Bro style: Energetic, with power emojis.
     */
    private function formatGymBroStyle(): string
    {
        $text = $this->recommendation;

        // Add power emoji at the start if not present
        if (!str_contains($text, '💪') && !str_contains($text, '🔥')) {
            $text = "💪 " . $text;
        }

        return $text;
    }

    /**
     * Format in Atas Friend style: Sophisticated, minimal emojis.
     */
    private function formatAtasStyle(): string
    {
        $text = $this->recommendation;

        // Add sophistication emoji at the start if not present
        if (!str_contains($text, '💅') && !str_contains($text, '✨')) {
            $text = "💅 " . $text;
        }

        return $text;
    }

    /**
     * Format in Tauke style: Business-focused, with money/luck emojis.
     */
    private function formatTaukeStyle(): string
    {
        $text = $this->recommendation;

        // Add business emoji at the start if not present
        if (!str_contains($text, '🧧') && !str_contains($text, '💰')) {
            $text = "🧧 " . $text;
        }

        return $text;
    }

    /**
     * Format in Mat Motor style: Casual, with motor/night emojis.
     */
    private function formatMatMotorStyle(): string
    {
        $text = $this->recommendation;

        // Add motor emoji at the start if not present
        if (!str_contains($text, '🏍️') && !str_contains($text, '🌙')) {
            $text = "🏍️ " . $text;
        }

        return $text;
    }

    /**
     * Format in Corporate Slave style: Stressed, with office emojis.
     */
    private function formatCorporateStyle(): string
    {
        $text = $this->recommendation;

        // Add office emoji at the start if not present
        if (!str_contains($text, '💼') && !str_contains($text, '☕')) {
            $text = "💼 " . $text;
        }

        return $text;
    }
}
