<?php

namespace Tests\Unit;

use App\AI\PromptBuilder;
use App\DTOs\RecommendationDTO;
use App\Models\Place;
use App\Services\GroqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqServiceTest extends TestCase
{
    use RefreshDatabase;

    private GroqService $service;
    private PromptBuilder $promptBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promptBuilder = new PromptBuilder();
        $this->service = new GroqService($this->promptBuilder);

        // Set fake API key for testing
        config(['services.groq.api_key' => 'fake-groq-api-key-for-testing']);
    }

    public function test_it_returns_recommendation_dto_on_success(): void
    {
        // Arrange: Mock successful API response (OpenAI-compatible format)
        $mockResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Bro! For high protein nasi lemak, hit up Village Park. Extra telur, double ayam. Gains guaranteed, padu!',
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 150,
                'completion_tokens' => 50,
                'total_tokens' => 200,
            ],
            'model' => 'llama-3.3-70b-versatile',
        ];

        Http::fake([
            'api.groq.com/*' => Http::response($mockResponse, 200),
        ]);

        // Create test data
        $place = Place::factory()->create([
            'name' => 'Village Park Restaurant',
            'price' => 'budget',
            'is_halal' => true,
        ]);

        $places = collect([$place]);

        // Act
        $result = $this->service->recommend('I want high protein nasi lemak', 'gymbro', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertEquals('gymbro', $result->persona);
        $this->assertStringContainsString('Village Park', $result->recommendation);
        $this->assertEquals(200, $result->getTokensUsed());
        $this->assertFalse($result->isFallback());
    }

    public function test_it_returns_fallback_on_api_failure(): void
    {
        // Arrange: Mock failed API response
        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $places = collect([
            Place::factory()->make(['name' => 'Test Place']),
        ]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertTrue($result->isFallback());
        $this->assertStringContainsString('tired', strtolower($result->recommendation));
    }

    public function test_it_handles_rate_limit_with_model_fallback(): void
    {
        // Arrange: Mock 429 rate limit for first model, success for second
        Http::fake([
            'api.groq.com/*' => Http::sequence()
                ->push(['error' => 'Rate limit exceeded'], 429)  // First model fails
                ->push([  // Second model succeeds
                    'choices' => [
                        ['message' => ['content' => 'Fallback model response']],
                    ],
                    'usage' => ['total_tokens' => 100, 'prompt_tokens' => 70, 'completion_tokens' => 30],
                    'model' => 'llama-3.1-8b-instant',
                ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Test query', 'atas', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertStringContainsString('Fallback model response', $result->recommendation);
    }

    public function test_set_model_changes_active_model(): void
    {
        // Arrange
        $customModel = 'llama-3.1-8b-instant';

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Test']]],
                'usage' => ['total_tokens' => 50, 'prompt_tokens' => 40, 'completion_tokens' => 10],
                'model' => $customModel,
            ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $this->service->setModel($customModel);
        $result = $this->service->recommend('Test', 'makcik', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        Http::assertSent(function ($request) use ($customModel) {
            return $request->data()['model'] === $customModel;
        });
    }

    public function test_recommendation_dto_from_groq_response(): void
    {
        // Arrange
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Go to Village Park for amazing nasi lemak!',
                    ],
                ],
            ],
            'usage' => [
                'total_tokens' => 120,
                'prompt_tokens' => 80,
                'completion_tokens' => 40,
            ],
            'model' => 'llama-3.3-70b-versatile',
        ];

        // Act
        $dto = RecommendationDTO::fromGroqResponse($apiResponse, 'atas');

        // Assert
        $this->assertEquals('atas', $dto->persona);
        $this->assertStringContainsString('Village Park', $dto->recommendation);
        $this->assertEquals(120, $dto->getTokensUsed());
        $this->assertEquals('llama-3.3-70b-versatile', $dto->metadata['model']);
        $this->assertEquals('groq', $dto->metadata['provider']);
        $this->assertContains('Village Park', $dto->suggestedPlaces);
    }

    public function test_cost_estimation_for_llama_model(): void
    {
        // Arrange & Act
        $cost = GroqService::estimateCost(1000, 500, 'llama-3.3-70b-versatile');

        // Assert
        // Input: 1000 * 0.59 / 1M = 0.00059
        // Output: 500 * 0.79 / 1M = 0.000395
        // Total = 0.000985
        $this->assertEquals(0.000985, $cost);
    }

    public function test_cost_estimation_for_openai_model(): void
    {
        // Arrange & Act
        $cost = GroqService::estimateCost(2000, 1000, 'openai/gpt-oss-120b');

        // Assert
        // Input: 2000 * 0.80 / 1M = 0.0016
        // Output: 1000 * 1.20 / 1M = 0.0012
        // Total = 0.0028
        $this->assertEquals(0.0028, $cost);
    }

    public function test_cost_estimation_for_unknown_model_uses_default(): void
    {
        // Arrange & Act
        $cost = GroqService::estimateCost(1000, 500, 'unknown-model');

        // Assert
        // Input: 1000 * 0.10 / 1M = 0.0001
        // Output: 500 * 0.15 / 1M = 0.000075
        // Total = 0.000175
        $this->assertEquals(0.000175, $cost);
    }

    public function test_health_check_returns_true_with_valid_api_key(): void
    {
        // Arrange
        Http::fake([
            'api.groq.com/openai/v1/models' => Http::response(['data' => []], 200),
        ]);

        // Act
        $result = $this->service->healthCheck();

        // Assert
        $this->assertTrue($result);
    }

    public function test_health_check_returns_false_without_api_key(): void
    {
        // Arrange
        config(['services.groq.api_key' => null]);

        // Act
        $result = $this->service->healthCheck();

        // Assert
        $this->assertFalse($result);
    }

    public function test_health_check_returns_false_on_api_error(): void
    {
        // Arrange
        Http::fake([
            'api.groq.com/openai/v1/models' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        // Act
        $result = $this->service->healthCheck();

        // Assert
        $this->assertFalse($result);
    }

    public function test_list_models_returns_available_models(): void
    {
        // Arrange
        $mockModels = [
            'data' => [
                [
                    'id' => 'llama-3.3-70b-versatile',
                    'owned_by' => 'Meta',
                    'created' => 1234567890,
                    'context_window' => 8192,
                ],
                [
                    'id' => 'llama-3.1-8b-instant',
                    'owned_by' => 'Meta',
                    'created' => 1234567890,
                    'context_window' => 8192,
                ],
            ],
        ];

        Http::fake([
            'api.groq.com/openai/v1/models' => Http::response($mockModels, 200),
        ]);

        // Act
        $result = GroqService::listModels();

        // Assert
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('llama-3.3-70b-versatile', $result['data'][0]['id']);
    }

    public function test_list_models_throws_exception_without_api_key(): void
    {
        // Arrange
        config(['services.groq.api_key' => null]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Groq API key not configured');

        GroqService::listModels();
    }

    public function test_recommend_throws_exception_without_api_key(): void
    {
        // Arrange
        config(['services.groq.api_key' => null]);
        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Test query', 'gymbro', $places);

        // Assert: Should return fallback instead of throwing
        $this->assertTrue($result->isFallback());
    }
}
