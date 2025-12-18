<?php

namespace App\Services;

use App\AI\PromptBuilder;
use App\Contracts\AIRecommendationInterface;
use App\DTOs\RecommendationDTO;
use Illuminate\Http\Client\Response;
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
    private const BASE_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models';
    private const LIST_MODELS_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models';
    private const TIMEOUT_SECONDS = 30;
    private const MAX_RETRIES = 2;

    /**
     * Fallback models to use when rate limits are hit.
     * Models are tried in order until one succeeds.
     */
    private const FALLBACK_MODELS = [
        'gemini-2.5-flash',      // Primary: Fast and efficient
        'gemini-2.0-flash',      // Fallback 1: Slightly older but stable
        'gemini-2.5-flash-lite', // Fallback 2: Lite version
        'gemini-2.0-flash-lite', // Fallback 3: Older lite version
    ];

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

            // Simple test request using list models endpoint
            /** @var Response $response */
            $response = Http::timeout(10)
                ->get(self::LIST_MODELS_ENDPOINT . "?key={$apiKey}");

            // Even a 400 means the API is responding
            return $response->status() !== 500 && $response->status() !== 503;
        } catch (\Exception $e) {
            Log::error('Gemini Health Check Failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Call the Gemini API with model fallback and retry logic.
     *
     * Tries multiple models in sequence when rate limits are hit.
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

        $lastException = null;

        // Try each model in the fallback array
        foreach (self::FALLBACK_MODELS as $modelIndex => $model) {
            $attempt = 0;

            while ($attempt < self::MAX_RETRIES) {
                try {
                    $endpoint = self::BASE_API_ENDPOINT . "/{$model}:generateContent";

                    /** @var Response $response */
                    $response = Http::timeout(self::TIMEOUT_SECONDS)
                        ->post($endpoint . "?key={$apiKey}", [
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
                                'maxOutputTokens' => 10000,
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

                    // Check for rate limit errors (429 status or quota exceeded)
                    if ($response->status() === 429 || $this->isRateLimitError($response)) {
                        Log::warning("Rate limit hit for model {$model}", [
                            'model' => $model,
                            'modelIndex' => $modelIndex,
                            'attempt' => $attempt + 1,
                            'status' => $response->status(),
                        ]);

                        // Break out of retry loop and try next model
                        break;
                    }

                    // Check for other HTTP errors
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
                            'model' => $model,
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
                        'model' => $model,
                        'modelIndex' => $modelIndex,
                        'tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                        'attempt' => $attempt + 1,
                        'finishReason' => $finishReason,
                    ]);

                    // Add model name to response data for tracking
                    $data['_model_used'] = $model;

                    return $data;
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastException = $e;
                    $attempt++;

                    if ($attempt < self::MAX_RETRIES) {
                        // Exponential backoff: 1s, 2s, 4s...
                        $waitTime = pow(2, $attempt);
                        Log::warning("Gemini API retry {$attempt} for model {$model} after {$waitTime}s", [
                            'model' => $model,
                            'error' => $e->getMessage(),
                        ]);
                        sleep($waitTime);
                    }
                } catch (\Exception $e) {
                    // Don't retry on other exceptions (validation, auth errors, etc.)
                    throw $e;
                }
            }

            // If we got here, all retries for this model failed
            // Continue to next model in fallback array
            Log::warning("All retries exhausted for model {$model}, trying next model", [
                'model' => $model,
                'modelIndex' => $modelIndex,
            ]);
        }

        // All models and retries exhausted
        throw new \Exception(
            'Gemini API failed for all ' . count(self::FALLBACK_MODELS) . ' models after ' . self::MAX_RETRIES . ' attempts each: ' . $lastException?->getMessage()
        );
    }

    /**
     * Check if the response contains a rate limit error message.
     *
     * @param Response $response
     * @return bool
     */
    private function isRateLimitError(Response $response): bool
    {
        $body = $response->body();

        // Check for common rate limit error messages
        $rateLimitIndicators = [
            'quota',
            'rate limit',
            'too many requests',
            'RESOURCE_EXHAUSTED',
        ];

        foreach ($rateLimitIndicators as $indicator) {
            if (stripos($body, $indicator) !== false) {
                return true;
            }
        }

        return false;
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
            /** @var Response $response */
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
