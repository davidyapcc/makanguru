<?php

namespace App\Services;

use App\AI\PromptBuilder;
use App\Contracts\AIRecommendationInterface;
use App\DTOs\RecommendationDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini AI Service Implementation.
 *
 * Handles communication with Google's Gemini 2.5 Flash API for
 * generating personalized restaurant recommendations.
 */
class GeminiService implements AIRecommendationInterface
{
    private const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const LIST_MODELS_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models';
    private const TIMEOUT_SECONDS = 30;
    private const MAX_RETRIES = 2;

    /**
     * Create a new GeminiService instance.
     *
     * @param PromptBuilder $promptBuilder
     */
    public function __construct(
        private readonly PromptBuilder $promptBuilder
    ) {
    }

    /**
     * Get AI-powered restaurant recommendation.
     *
     * @param string $userQuery
     * @param string $persona
     * @param Collection $places
     * @return RecommendationDTO
     */
    public function recommend(string $userQuery, string $persona, Collection $places): RecommendationDTO
    {
        try {
            // Build the prompt with context
            $prompt = $this->promptBuilder->build($userQuery, $persona, $places);

            // Make API call with retry logic
            $response = $this->callGeminiAPI($prompt);

            // Parse and return DTO
            return RecommendationDTO::fromGeminiResponse($response, $persona);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Gemini API Error', [
                'message' => $e->getMessage(),
                'persona' => $persona,
                'query' => $userQuery,
                'trace' => $e->getTraceAsString(),
            ]);

            // Return fallback recommendation
            return RecommendationDTO::fallback($persona);
        }
    }

    /**
     * Check if Gemini API is responding.
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $apiKey = config('services.gemini.api_key');

            if (empty($apiKey)) {
                Log::warning('Gemini API key not configured');
                return false;
            }

            // Simple test request
            $response = Http::timeout(10)
                ->get(self::API_ENDPOINT, ['key' => $apiKey]);

            // Even a 400 means the API is responding
            return $response->status() !== 500 && $response->status() !== 503;
        } catch (\Exception $e) {
            Log::error('Gemini Health Check Failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Call the Gemini API with retry logic and error handling.
     *
     * @param string $prompt
     * @return array<string, mixed>
     * @throws \Exception
     */
    private function callGeminiAPI(string $prompt): array
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new \Exception('Gemini API key not configured. Set GEMINI_API_KEY in .env');
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->post(self::API_ENDPOINT . "?key={$apiKey}", [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.9,
                            'maxOutputTokens' => 1000,
                            'topP' => 0.95,
                        ],
                        'safetySettings' => [
                            [
                                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                                'threshold' => 'BLOCK_NONE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                                'threshold' => 'BLOCK_NONE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                                'threshold' => 'BLOCK_NONE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_HARASSMENT',
                                'threshold' => 'BLOCK_NONE',
                            ],
                        ],
                    ]);

                // Check for HTTP errors
                if ($response->failed()) {
                    throw new \Exception(
                        "Gemini API returned {$response->status()}: " . $response->body()
                    );
                }

                $data = $response->json();

                // Check for finish reason
                $finishReason = $data['candidates'][0]['finishReason'] ?? null;
                if ($finishReason && $finishReason !== 'STOP') {
                    Log::warning('Gemini API unusual finish reason', [
                        'finishReason' => $finishReason,
                        'safetyRatings' => $data['candidates'][0]['safetyRatings'] ?? [],
                    ]);
                }

                // Validate response structure
                if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    throw new \Exception('Invalid response structure from Gemini API. Response: ' . json_encode($data));
                }

                // Log successful call
                Log::info('Gemini API Success', [
                    'tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                    'attempt' => $attempt + 1,
                    'finishReason' => $finishReason,
                ]);

                return $data;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < self::MAX_RETRIES) {
                    // Exponential backoff: 1s, 2s, 4s...
                    $waitTime = pow(2, $attempt);
                    Log::warning("Gemini API retry {$attempt} after {$waitTime}s", [
                        'error' => $e->getMessage(),
                    ]);
                    sleep($waitTime);
                }
            } catch (\Exception $e) {
                // Don't retry on other exceptions (validation, auth errors, etc.)
                throw $e;
            }
        }

        // All retries exhausted
        throw new \Exception(
            'Gemini API failed after ' . self::MAX_RETRIES . ' attempts: ' . $lastException?->getMessage()
        );
    }

    /**
     * Get the cost estimate for a request (in USD).
     *
     * Gemini 1.5 Flash pricing (as of 2024):
     * - Input: $0.075 per 1M tokens
     * - Output: $0.30 per 1M tokens
     *
     * @param int $inputTokens
     * @param int $outputTokens
     * @return float
     */
    public static function estimateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens / 1_000_000) * 0.075;
        $outputCost = ($outputTokens / 1_000_000) * 0.30;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * List all available Gemini models.
     *
     * @param string|null $apiKey Optional API key (uses config if not provided)
     * @return array<string, mixed>
     * @throws \Exception
     */
    public static function listModels(?string $apiKey = null): array
    {
        $apiKey = $apiKey ?? config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new \Exception('Gemini API key not configured');
        }

        try {
            $response = Http::timeout(10)
                ->get(self::LIST_MODELS_ENDPOINT . "?key={$apiKey}");

            if ($response->failed()) {
                throw new \Exception(
                    "Failed to list models: {$response->status()} - {$response->body()}"
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Error listing Gemini models: {$e->getMessage()}");
        }
    }
}
