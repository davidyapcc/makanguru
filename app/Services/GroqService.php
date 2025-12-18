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
 * Groq AI Service Implementation (OpenAI-compatible).
 *
 * Handles communication with Groq's API for fast,
 * high-performance restaurant recommendations.
 */
class GroqService implements AIRecommendationInterface
{
    private const BASE_API_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
    private const TIMEOUT_SECONDS = 30;
    private const MAX_RETRIES = 2;

    /**
     * The specific model to use for the next request.
     */
    private ?string $activeModel = null;

    /**
     * Fallback models to use for Groq.
     */
    private const FALLBACK_MODELS = [
        'llama-3.3-70b-versatile',
        'llama-3.1-8b-instant',
        'openai/gpt-oss-120b',
        'openai/gpt-oss-20b',
    ];

    /**
     * Create a new GroqService instance.
     *
     * @param PromptBuilder $promptBuilder
     */
    public function __construct(
        private readonly PromptBuilder $promptBuilder
    ) {
    }

    /**
     * Set the model to be used for the next recommendation.
     *
     * @param string $model
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->activeModel = $model;
        return $this;
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
            $response = $this->callGroqAPI($prompt, $persona);

            // Parse and return DTO
            return RecommendationDTO::fromGroqResponse($response, $persona);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Groq API Error', [
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
     * Check if Groq API is responding.
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $apiKey = config('services.groq.api_key');

            if (empty($apiKey)) {
                Log::warning('Groq API key not configured');
                return false;
            }

            // Simple test request
            /** @var Response $response */
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get('https://api.groq.com/openai/v1/models');

            $isHealthy = $response->successful();

            Log::info('Groq Health Check', [
                'healthy' => $isHealthy,
                'status' => $response->status(),
            ]);

            return $isHealthy;
        } catch (\Exception $e) {
            Log::error('Groq Health Check Failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Call the Groq API with model fallback and retry logic.
     *
     * @param string $prompt
     * @param string $persona
     * @return array<string, mixed>
     * @throws \Exception
     */
    private function callGroqAPI(string $prompt, string $persona): array
    {
        $apiKey = config('services.groq.api_key');

        // Use active model if set, otherwise use config, otherwise first fallback
        $configuredModel = $this->activeModel ?? config('services.groq.models.default', self::FALLBACK_MODELS[0]);

        if (empty($apiKey)) {
            throw new \Exception('Groq API key not configured. Set GROQ_API_KEY in .env');
        }

        $modelsToTry = array_unique(array_merge([$configuredModel], self::FALLBACK_MODELS));
        $lastException = null;

        Log::debug('GroqService: Calling API', [
            'activeModel' => $this->activeModel,
            'configuredModel' => $configuredModel,
            'modelsToTry' => $modelsToTry
        ]);

        foreach ($modelsToTry as $modelIndex => $model) {
            $attempt = 0;

            while ($attempt < self::MAX_RETRIES) {
                try {
                    /** @var Response $response */
                    $response = Http::withToken($apiKey)
                        ->timeout(self::TIMEOUT_SECONDS)
                        ->post(self::BASE_API_ENDPOINT, [
                            'model' => $model,
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => "You are a helpful assistant providing restaurant recommendations in the persona of {$persona}."
                                ],
                                [
                                    'role' => 'user',
                                    'content' => $prompt,
                                ],
                            ],
                            'temperature' => 0.7,
                            'max_tokens' => 2048,
                            'top_p' => 1,
                        ]);

                    // Handle rate limits (429)
                    if ($response->status() === 429) {
                        Log::warning("Groq Rate limit hit for model {$model}", [
                            'model' => $model,
                            'attempt' => $attempt + 1,
                        ]);
                        break; // Try next model
                    }

                    if ($response->failed()) {
                        throw new \Exception(
                            "Groq API returned {$response->status()}: " . $response->body()
                        );
                    }

                    $data = $response->json();

                    // Extract token usage
                    $inputTokens = $data['usage']['prompt_tokens'] ?? 0;
                    $outputTokens = $data['usage']['completion_tokens'] ?? 0;
                    $totalTokens = $data['usage']['total_tokens'] ?? 0;

                    // Calculate cost
                    $estimatedCost = self::estimateCost($inputTokens, $outputTokens, $model);

                    // Log success with cost information
                    Log::info('Groq API Success', [
                        'model' => $model,
                        'input_tokens' => $inputTokens,
                        'output_tokens' => $outputTokens,
                        'total_tokens' => $totalTokens,
                        'estimated_cost_usd' => round($estimatedCost, 6),
                    ]);

                    return $data;
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastException = $e;
                    $attempt++;
                    if ($attempt < self::MAX_RETRIES) {
                        sleep(pow(2, $attempt));
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }

        throw new \Exception(
            'Groq API failed for all models: ' . ($lastException?->getMessage() ?? 'Unknown error'),
            0,
            $lastException  // Chain the original exception for better debugging
        );
    }

    /**
     * Estimate cost for Groq API call.
     *
     * Groq pricing (as of December 2024):
     * - llama-3.3-70b-versatile: $0.59/1M input, $0.79/1M output
     * - llama-3.1-8b-instant: $0.05/1M input, $0.08/1M output
     * - openai/gpt-oss-120b: $0.80/1M input, $1.20/1M output
     *
     * @param int $inputTokens Number of input tokens
     * @param int $outputTokens Number of output tokens
     * @param string $model Model identifier
     * @return float Estimated cost in USD
     */
    public static function estimateCost(int $inputTokens, int $outputTokens, string $model): float
    {
        // Pricing per 1 million tokens (as of December 2024)
        $pricing = [
            'llama-3.3-70b-versatile' => ['input' => 0.59, 'output' => 0.79],
            'llama-3.1-8b-instant' => ['input' => 0.05, 'output' => 0.08],
            'llama-3.1-70b-versatile' => ['input' => 0.59, 'output' => 0.79],
            'openai/gpt-oss-120b' => ['input' => 0.80, 'output' => 1.20],
            'openai/gpt-oss-20b' => ['input' => 0.20, 'output' => 0.30],
        ];

        // Default pricing if model not found
        $rate = $pricing[$model] ?? ['input' => 0.10, 'output' => 0.15];

        $inputCost = ($inputTokens * $rate['input']) / 1_000_000;
        $outputCost = ($outputTokens * $rate['output']) / 1_000_000;

        return $inputCost + $outputCost;
    }

    /**
     * List all available Groq models.
     *
     * @return array<string, mixed>
     * @throws \Exception
     */
    public static function listModels(): array
    {
        $apiKey = config('services.groq.api_key');

        if (empty($apiKey)) {
            throw new \Exception('Groq API key not configured');
        }

        try {
            /** @var Response $response */
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get('https://api.groq.com/openai/v1/models');

            if ($response->failed()) {
                throw new \Exception(
                    "Failed to list models: {$response->status()} - {$response->body()}"
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Error listing Groq models: {$e->getMessage()}");
        }
    }
}

