<?php

namespace Tests\Unit;

use App\DTOs\RecommendationDTO;
use Tests\TestCase;

/**
 * RecommendationDTO Unit Tests
 *
 * Tests DTO creation, transformation, and edge cases.
 */
class RecommendationDTOTest extends TestCase
{
    /**
     * Test DTO can be created with minimum parameters.
     */
    public function test_dto_creation_with_minimum_parameters(): void
    {
        // Act
        $dto = new RecommendationDTO('Go to Village Park!', 'makcik');

        // Assert
        $this->assertEquals('Go to Village Park!', $dto->recommendation);
        $this->assertEquals('makcik', $dto->persona);
        $this->assertEmpty($dto->suggestedPlaces);
        $this->assertEmpty($dto->metadata);
    }

    /**
     * Test DTO can be created with all parameters.
     */
    public function test_dto_creation_with_all_parameters(): void
    {
        // Arrange
        $metadata = [
            'tokens_used' => 250,
            'model' => 'gemini-2.5-flash',
            'timestamp' => '2024-01-01T12:00:00Z',
        ];

        // Act
        $dto = new RecommendationDTO(
            'Go to Village Park!',
            'gymbro',
            ['Village Park', 'Jalan Alor'],
            $metadata
        );

        // Assert
        $this->assertEquals('Go to Village Park!', $dto->recommendation);
        $this->assertEquals('gymbro', $dto->persona);
        $this->assertEquals(['Village Park', 'Jalan Alor'], $dto->suggestedPlaces);
        $this->assertEquals($metadata, $dto->metadata);
    }

    /**
     * Test fromGeminiResponse extracts text correctly.
     */
    public function test_from_gemini_response_extracts_text(): void
    {
        // Arrange
        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Bro, go to Village Park for nasi lemak!'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 150,
            ],
        ];

        // Act
        $dto = RecommendationDTO::fromGeminiResponse($apiResponse, 'gymbro');

        // Assert
        $this->assertEquals('Bro, go to Village Park for nasi lemak!', $dto->recommendation);
        $this->assertEquals('gymbro', $dto->persona);
    }

    /**
     * Test fromGeminiResponse extracts token usage.
     */
    public function test_from_gemini_response_extracts_token_usage(): void
    {
        // Arrange
        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Test response'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 500,
            ],
        ];

        // Act
        $dto = RecommendationDTO::fromGeminiResponse($apiResponse, 'makcik');

        // Assert
        $this->assertEquals(500, $dto->getTokensUsed());
    }

    /**
     * Test fromGeminiResponse extracts model name.
     */
    public function test_from_gemini_response_extracts_model_name(): void
    {
        // Arrange
        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Test response'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 100,
            ],
            '_model_used' => 'gemini-2.0-flash',
        ];

        // Act
        $dto = RecommendationDTO::fromGeminiResponse($apiResponse, 'atas');

        // Assert
        $this->assertEquals('gemini-2.0-flash', $dto->metadata['model']);
    }

    /**
     * Test fromGeminiResponse extracts place names from text.
     */
    public function test_from_gemini_response_extracts_place_names(): void
    {
        // Arrange
        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Visit Village Park Restaurant and Jalan Alor Food Street for great food!'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 50,
            ],
        ];

        // Act
        $dto = RecommendationDTO::fromGeminiResponse($apiResponse, 'makcik');

        // Assert: Place name extraction uses simple regex for capitalized words
        $this->assertNotEmpty($dto->suggestedPlaces);
        // The extraction looks for capitalized words, so we check for presence
        $placesString = implode(' ', $dto->suggestedPlaces);
        $this->assertStringContainsString('Village', $placesString);
    }

    /**
     * Test fromGeminiResponse trims whitespace.
     */
    public function test_from_gemini_response_trims_whitespace(): void
    {
        // Arrange
        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => "  \n  Test response with whitespace  \n  "],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 50,
            ],
        ];

        // Act
        $dto = RecommendationDTO::fromGeminiResponse($apiResponse, 'makcik');

        // Assert
        $this->assertEquals('Test response with whitespace', $dto->recommendation);
    }

    /**
     * Test fromGroqResponse extracts text correctly.
     */
    public function test_from_groq_response_extracts_text(): void
    {
        // Arrange
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Check out this amazing cafe!',
                    ],
                ],
            ],
            'usage' => [
                'total_tokens' => 200,
            ],
            'model' => 'llama-3.3-70b-versatile',
        ];

        // Act
        $dto = RecommendationDTO::fromGroqResponse($apiResponse, 'atas');

        // Assert
        $this->assertEquals('Check out this amazing cafe!', $dto->recommendation);
        $this->assertEquals('atas', $dto->persona);
    }

    /**
     * Test fromGroqResponse includes provider metadata.
     */
    public function test_from_groq_response_includes_provider_metadata(): void
    {
        // Arrange
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Test',
                    ],
                ],
            ],
            'usage' => [
                'total_tokens' => 100,
            ],
            'model' => 'llama-3.1-8b-instant',
        ];

        // Act
        $dto = RecommendationDTO::fromGroqResponse($apiResponse, 'gymbro');

        // Assert
        $this->assertEquals('groq', $dto->metadata['provider']);
        $this->assertEquals('llama-3.1-8b-instant', $dto->metadata['model']);
    }

    /**
     * Test fallback creates persona-specific messages for all 6 personas.
     */
    public function test_fallback_creates_persona_specific_messages(): void
    {
        // Arrange & Act
        $makcik = RecommendationDTO::fallback('makcik');
        $gymbro = RecommendationDTO::fallback('gymbro');
        $atas = RecommendationDTO::fallback('atas');
        $tauke = RecommendationDTO::fallback('tauke');
        $matmotor = RecommendationDTO::fallback('matmotor');
        $corporate = RecommendationDTO::fallback('corporate');

        // Assert: Each should have unique, personality-appropriate message
        $this->assertStringContainsString('Aiyah', $makcik->recommendation);
        $this->assertStringContainsString('Bro', $gymbro->recommendation);
        $this->assertStringContainsString('Darling', $atas->recommendation);
        $this->assertStringContainsString('Wa lao eh', $tauke->recommendation);
        $this->assertStringContainsString('Member', $matmotor->recommendation);
        $this->assertStringContainsString('System', $corporate->recommendation);
    }

    /**
     * Test fallback sets is_fallback flag.
     */
    public function test_fallback_sets_is_fallback_flag(): void
    {
        // Act
        $dto = RecommendationDTO::fallback('makcik');

        // Assert
        $this->assertTrue($dto->isFallback());
        $this->assertTrue($dto->metadata['is_fallback']);
    }

    /**
     * Test fallback has empty suggested places.
     */
    public function test_fallback_has_empty_suggested_places(): void
    {
        // Act
        $dto = RecommendationDTO::fallback('gymbro');

        // Assert
        $this->assertEmpty($dto->suggestedPlaces);
    }

    /**
     * Test fallback includes timestamp.
     */
    public function test_fallback_includes_timestamp(): void
    {
        // Act
        $dto = RecommendationDTO::fallback('atas');

        // Assert
        $this->assertArrayHasKey('timestamp', $dto->metadata);
        $this->assertNotEmpty($dto->metadata['timestamp']);
    }

    /**
     * Test fallback with unknown persona uses default message.
     */
    public function test_fallback_with_unknown_persona_uses_default(): void
    {
        // Act
        $dto = RecommendationDTO::fallback('unknown_persona');

        // Assert
        $this->assertStringContainsString('temporarily unavailable', $dto->recommendation);
        $this->assertEquals('unknown_persona', $dto->persona);
    }

    /**
     * Test toArray includes all properties.
     */
    public function test_to_array_includes_all_properties(): void
    {
        // Arrange
        $dto = new RecommendationDTO(
            'Test recommendation',
            'makcik',
            ['Place 1', 'Place 2'],
            ['tokens_used' => 100]
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertArrayHasKey('recommendation', $array);
        $this->assertArrayHasKey('persona', $array);
        $this->assertArrayHasKey('suggested_places', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals('Test recommendation', $array['recommendation']);
        $this->assertEquals('makcik', $array['persona']);
        $this->assertEquals(['Place 1', 'Place 2'], $array['suggested_places']);
    }

    /**
     * Test isFallback returns false for normal responses.
     */
    public function test_is_fallback_returns_false_for_normal_responses(): void
    {
        // Arrange
        $dto = new RecommendationDTO('Normal response', 'gymbro');

        // Act & Assert
        $this->assertFalse($dto->isFallback());
    }

    /**
     * Test getTokensUsed returns zero when not set.
     */
    public function test_get_tokens_used_returns_zero_when_not_set(): void
    {
        // Arrange
        $dto = new RecommendationDTO('Test', 'atas');

        // Act & Assert
        $this->assertEquals(0, $dto->getTokensUsed());
    }

    /**
     * Test getFormattedRecommendation for all 6 personas.
     */
    public function test_get_formatted_recommendation_for_all_personas(): void
    {
        // Arrange
        $personas = [
            'makcik' => '👵',
            'gymbro' => '💪',
            'atas' => '💅',
            'tauke' => '🧧',
            'matmotor' => '🏍️',
            'corporate' => '💼',
        ];

        foreach ($personas as $persona => $emoji) {
            // Act
            $dto = new RecommendationDTO('Test recommendation', $persona);
            $formatted = $dto->getFormattedRecommendation();

            // Assert: Should add emoji if not present
            $this->assertStringStartsWith($emoji, $formatted, "Failed for persona: {$persona}");
        }
    }

    /**
     * Test getFormattedRecommendation doesn't add duplicate emojis.
     */
    public function test_get_formatted_recommendation_no_duplicate_emojis(): void
    {
        // Arrange: Recommendation already has emoji
        $dto = new RecommendationDTO('👵 Aiyah, go to Village Park!', 'makcik');

        // Act
        $formatted = $dto->getFormattedRecommendation();

        // Assert: Should not add another emoji
        $this->assertEquals('👵 Aiyah, go to Village Park!', $formatted);
    }

    /**
     * Test edge case: Empty recommendation text.
     */
    public function test_empty_recommendation_text(): void
    {
        // Arrange
        $dto = new RecommendationDTO('', 'makcik');

        // Act & Assert
        $this->assertEquals('', $dto->recommendation);
    }

    /**
     * Test edge case: Very long recommendation text.
     */
    public function test_very_long_recommendation_text(): void
    {
        // Arrange
        $longText = str_repeat('This is a very long recommendation. ', 100);
        $dto = new RecommendationDTO($longText, 'gymbro');

        // Act & Assert
        $this->assertEquals($longText, $dto->recommendation);
    }

    /**
     * Test edge case: Recommendation with special characters.
     */
    public function test_recommendation_with_special_characters(): void
    {
        // Arrange
        $specialText = "Test with special chars: <>&\"'🌶️";
        $dto = new RecommendationDTO($specialText, 'atas');

        // Act & Assert
        $this->assertEquals($specialText, $dto->recommendation);
    }

    /**
     * Test edge case: Recommendation with newlines and formatting.
     */
    public function test_recommendation_with_newlines(): void
    {
        // Arrange
        $textWithNewlines = "Line 1\nLine 2\n\nLine 3";
        $dto = new RecommendationDTO($textWithNewlines, 'makcik');

        // Act & Assert
        $this->assertEquals($textWithNewlines, $dto->recommendation);
    }

    /**
     * Test edge case: Empty suggested places array.
     */
    public function test_empty_suggested_places_array(): void
    {
        // Arrange
        $dto = new RecommendationDTO('Test', 'gymbro', []);

        // Act & Assert
        $this->assertEmpty($dto->suggestedPlaces);
    }

    /**
     * Test edge case: Duplicate place names in suggestions.
     */
    public function test_duplicate_place_names_are_preserved(): void
    {
        // Arrange: API might return duplicates (though our extraction removes them)
        $dto = new RecommendationDTO(
            'Test',
            'atas',
            ['Village Park', 'Village Park', 'Jalan Alor']
        );

        // Act & Assert
        $this->assertCount(3, $dto->suggestedPlaces);
    }

    /**
     * Test edge case: Missing fields in Gemini response.
     */
    public function test_from_gemini_response_with_missing_fields(): void
    {
        // Arrange: Minimal response structure
        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Minimal response'],
                        ],
                    ],
                ],
            ],
        ];

        // Act
        $dto = RecommendationDTO::fromGeminiResponse($apiResponse, 'makcik');

        // Assert: Should handle gracefully
        $this->assertEquals('Minimal response', $dto->recommendation);
        $this->assertEquals(0, $dto->getTokensUsed());
        $this->assertEquals('gemini-2.5-flash', $dto->metadata['model']); // Default
    }

    /**
     * Test edge case: Missing fields in Groq response.
     */
    public function test_from_groq_response_with_missing_fields(): void
    {
        // Arrange: Minimal response structure
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Minimal Groq response',
                    ],
                ],
            ],
        ];

        // Act
        $dto = RecommendationDTO::fromGroqResponse($apiResponse, 'gymbro');

        // Assert: Should handle gracefully
        $this->assertEquals('Minimal Groq response', $dto->recommendation);
        $this->assertEquals(0, $dto->getTokensUsed());
        $this->assertEquals('unknown', $dto->metadata['model']); // Default
    }

    /**
     * Test readonly properties cannot be modified.
     */
    public function test_readonly_properties_are_immutable(): void
    {
        // Arrange
        $dto = new RecommendationDTO('Test', 'makcik');

        // Assert: This test documents that properties are readonly
        $this->expectException(\Error::class);

        // Act: Try to modify readonly property
        $dto->recommendation = 'Modified';
    }

    /**
     * Test metadata can contain arbitrary data.
     */
    public function test_metadata_can_contain_arbitrary_data(): void
    {
        // Arrange
        $customMetadata = [
            'custom_field' => 'custom_value',
            'nested' => ['key' => 'value'],
            'numeric' => 12345,
        ];

        $dto = new RecommendationDTO('Test', 'atas', [], $customMetadata);

        // Act & Assert
        $this->assertEquals('custom_value', $dto->metadata['custom_field']);
        $this->assertEquals(['key' => 'value'], $dto->metadata['nested']);
        $this->assertEquals(12345, $dto->metadata['numeric']);
    }
}
