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

        // Assert
        $this->assertCount(3, $personas);
        $this->assertContains('makcik', $personas);
        $this->assertContains('gymbro', $personas);
        $this->assertContains('atas', $personas);
    }
}
