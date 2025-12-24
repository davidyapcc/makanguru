<?php

namespace Tests\Unit;

use App\AI\PromptBuilder;
use App\DTOs\RecommendationDTO;
use App\Models\Place;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeminiService $service;
    private PromptBuilder $promptBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promptBuilder = new PromptBuilder();
        $this->service = new GeminiService($this->promptBuilder);

        // Set fake API key for testing
        config(['services.gemini.api_key' => 'fake-api-key-for-testing']);
    }

    public function test_it_returns_recommendation_dto_on_success(): void
    {
        // Arrange: Mock successful API response
        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Aiyah, you want nasi lemak? Must go to Village Park lah! The sambal there power!',
                            ],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 250,
            ],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200),
        ]);

        // Create test data
        $place = Place::factory()->create([
            'name' => 'Village Park Restaurant',
            'price' => 'budget',
            'is_halal' => true,
        ]);

        $places = collect([$place]);

        // Act
        $result = $this->service->recommend('I want nasi lemak', 'makcik', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertEquals('makcik', $result->persona);
        $this->assertStringContainsString('Village Park', $result->recommendation);
        $this->assertEquals(250, $result->getTokensUsed());
        $this->assertFalse($result->isFallback());
    }

    public function test_it_returns_fallback_on_api_failure(): void
    {
        // Arrange: Mock failed API response
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $places = collect([
            Place::factory()->make(['name' => 'Test Place']),
        ]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'gymbro', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertTrue($result->isFallback());
        $this->assertStringContainsString('system', strtolower($result->recommendation));
    }

    public function test_it_validates_persona(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid persona');

        // This should fail in PromptBuilder
        $this->promptBuilder->build('Test query', 'invalid-persona', $places);
    }

    public function test_prompt_builder_creates_proper_structure(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make([
                'name' => 'Test Restaurant',
                'area' => 'Bangsar',
                'price' => 'expensive',
            ]),
        ]);

        // Act
        $prompt = $this->promptBuilder->build('Find me expensive food', 'atas', $places);

        // Assert
        $this->assertStringContainsString('The Atas Friend', $prompt);
        $this->assertStringContainsString('Test Restaurant', $prompt);
        $this->assertStringContainsString('Bangsar', $prompt);
        $this->assertStringContainsString('Find me expensive food', $prompt);
    }

    public function test_recommendation_dto_from_gemini_response(): void
    {
        // Arrange
        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Go to Village Park for great nasi lemak!'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 150,
            ],
        ];

        // Act
        $dto = RecommendationDTO::fromGeminiResponse($apiResponse, 'makcik');

        // Assert
        $this->assertEquals('makcik', $dto->persona);
        $this->assertStringContainsString('Village Park', $dto->recommendation);
        $this->assertEquals(150, $dto->getTokensUsed());
        $this->assertContains('Village Park', $dto->suggestedPlaces);
    }

    public function test_fallback_dto_has_correct_structure(): void
    {
        // Act
        $dto = RecommendationDTO::fallback('gymbro');

        // Assert
        $this->assertEquals('gymbro', $dto->persona);
        $this->assertTrue($dto->isFallback());
        $this->assertNotEmpty($dto->recommendation);
        $this->assertEmpty($dto->suggestedPlaces);
    }

    public function test_cost_estimation_is_accurate(): void
    {
        // Arrange & Act
        $cost = GeminiService::estimateCost(1000, 500);

        // Assert
        // 1000 input tokens * 0.075 / 1M = 0.000075
        // 500 output tokens * 0.30 / 1M = 0.00015
        // Total = 0.000225
        $this->assertEquals(0.000225, $cost);
    }

    public function test_health_check_returns_false_without_api_key(): void
    {
        // Arrange
        config(['services.gemini.api_key' => null]);

        // Act
        $result = $this->service->healthCheck();

        // Assert
        $this->assertFalse($result);
    }

    public function test_available_personas_list(): void
    {
        // Act
        $personas = PromptBuilder::getAvailablePersonas();

        // Assert: Now 6 personas (Phase 7)
        $this->assertCount(6, $personas);
        $this->assertContains('makcik', $personas);
        $this->assertContains('gymbro', $personas);
        $this->assertContains('atas', $personas);
        $this->assertContains('tauke', $personas);
        $this->assertContains('matmotor', $personas);
        $this->assertContains('corporate', $personas);
    }

    /**
     * Test model fallback system with rate limiting.
     */
    public function test_model_fallback_on_rate_limit(): void
    {
        // Arrange: Mock rate limit error (429)
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Resource exhausted']], 429)
                ->push(['error' => ['message' => 'Resource exhausted']], 429)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => 'Success with fallback model!'],
                                ],
                            ],
                        ],
                    ],
                    'usageMetadata' => ['totalTokenCount' => 100],
                ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'makcik', $places);

        // Assert: Should eventually succeed with fallback model
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertStringContainsString('Success', $result->recommendation);
    }

    /**
     * Test all fallback models exhausted returns fallback response.
     */
    public function test_all_fallback_models_exhausted(): void
    {
        // Arrange: All models return rate limit
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'Resource exhausted']],
                429
            ),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'gymbro', $places);

        // Assert: Should return graceful fallback
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertTrue($result->isFallback());
    }

    /**
     * Test network timeout handling.
     */
    public function test_network_timeout_returns_fallback(): void
    {
        // Arrange: Simulate timeout
        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'atas', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertTrue($result->isFallback());
    }

    /**
     * Test malformed API response returns fallback.
     */
    public function test_malformed_api_response_returns_fallback(): void
    {
        // Arrange: Invalid response structure
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                ['unexpected' => 'structure'],
                200
            ),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertTrue($result->isFallback());
    }

    /**
     * Test empty places collection.
     */
    public function test_recommend_with_empty_places_collection(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'No places available, try somewhere else!'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        $places = collect([]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'gymbro', $places);

        // Assert: Should still work with empty places
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertFalse($result->isFallback());
    }

    /**
     * Test very long user query.
     */
    public function test_recommend_with_very_long_query(): void
    {
        // Arrange
        $longQuery = str_repeat('I want to eat delicious food. ', 100);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Response to long query'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 500],
            ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend($longQuery, 'atas', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertEquals(500, $result->getTokensUsed());
    }

    /**
     * Test health check returns true with valid API key.
     */
    public function test_health_check_returns_true_with_api_key(): void
    {
        // Arrange
        config(['services.gemini.api_key' => 'valid-api-key']);

        // Act
        $result = $this->service->healthCheck();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test cost estimation with zero tokens.
     */
    public function test_cost_estimation_with_zero_tokens(): void
    {
        // Act
        $cost = GeminiService::estimateCost(0, 0);

        // Assert
        $this->assertEquals(0.0, $cost);
    }

    /**
     * Test cost estimation with large token counts.
     */
    public function test_cost_estimation_with_large_token_counts(): void
    {
        // Arrange & Act: 1M input, 1M output
        $cost = GeminiService::estimateCost(1000000, 1000000);

        // Assert
        // 1M * 0.075 / 1M = 0.075
        // 1M * 0.30 / 1M = 0.30
        // Total = 0.375
        $this->assertEquals(0.375, $cost);
    }

    /**
     * Test API response with safety block.
     */
    public function test_api_response_with_safety_block(): void
    {
        // Arrange: Response blocked by safety settings
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [],
                        ],
                        'finishReason' => 'SAFETY',
                    ],
                ],
            ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Controversial query', 'makcik', $places);

        // Assert: Should return fallback
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertTrue($result->isFallback());
    }

    /**
     * Test API response with multiple candidates (uses first).
     */
    public function test_api_response_with_multiple_candidates(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'First candidate response'],
                            ],
                        ],
                    ],
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Second candidate response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 100],
            ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'gymbro', $places);

        // Assert: Should use first candidate
        $this->assertStringContainsString('First candidate', $result->recommendation);
    }

    /**
     * Test recommend with all 6 personas.
     */
    public function test_recommend_works_with_all_six_personas(): void
    {
        // Arrange
        $personas = ['makcik', 'gymbro', 'atas', 'tauke', 'matmotor', 'corporate'];
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Persona-specific response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 100],
            ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        foreach ($personas as $persona) {
            // Act
            $result = $this->service->recommend('Where to eat?', $persona, $places);

            // Assert
            $this->assertInstanceOf(RecommendationDTO::class, $result);
            $this->assertEquals($persona, $result->persona);
            $this->assertFalse($result->isFallback());
        }
    }

    /**
     * Test edge case: API returns empty text.
     */
    public function test_api_returns_empty_text(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => ''],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 10],
            ], 200),
        ]);

        $places = collect([Place::factory()->make()]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'atas', $places);

        // Assert: Empty text should be handled
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertEquals('', $result->recommendation);
    }

    /**
     * Test edge case: Large number of places (token optimization).
     */
    public function test_recommend_with_many_places(): void
    {
        // Arrange
        $places = collect();
        for ($i = 0; $i < 50; $i++) {
            $places->push(Place::factory()->make(['name' => "Restaurant {$i}"]));
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Response with many places'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 5000],
            ], 200),
        ]);

        // Act
        $result = $this->service->recommend('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertInstanceOf(RecommendationDTO::class, $result);
        $this->assertGreaterThan(1000, $result->getTokensUsed());
    }
}
