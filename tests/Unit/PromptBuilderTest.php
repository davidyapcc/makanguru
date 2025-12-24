<?php

namespace Tests\Unit;

use App\AI\PromptBuilder;
use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * PromptBuilder Unit Tests
 *
 * Tests prompt generation for all 6 personas with various edge cases.
 */
class PromptBuilderTest extends TestCase
{
    use RefreshDatabase;

    private PromptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new PromptBuilder();
    }

    /**
     * Test all 6 personas are available.
     */
    public function test_all_six_personas_are_available(): void
    {
        // Act
        $personas = PromptBuilder::getAvailablePersonas();

        // Assert
        $this->assertCount(6, $personas);
        $this->assertContains('makcik', $personas);
        $this->assertContains('gymbro', $personas);
        $this->assertContains('atas', $personas);
        $this->assertContains('tauke', $personas);
        $this->assertContains('matmotor', $personas);
        $this->assertContains('corporate', $personas);
    }

    /**
     * Test Mak Cik persona includes correct characteristics.
     */
    public function test_makcik_persona_includes_correct_characteristics(): void
    {
        // Arrange
        $places = collect([Place::factory()->make(['name' => 'Test Place'])]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert: Case-insensitive checks for key characteristics
        $this->assertStringContainsStringIgnoringCase('Mak Cik', $prompt);
        $this->assertStringContainsStringIgnoringCase('nurturing', $prompt);
        $this->assertStringContainsStringIgnoringCase('halal', $prompt);
        $this->assertStringContainsStringIgnoringCase('value', $prompt);
        $this->assertStringContainsString('Aiyah', $prompt);
    }

    /**
     * Test Gym Bro persona includes correct characteristics.
     */
    public function test_gymbro_persona_includes_correct_characteristics(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'gymbro', $places);

        // Assert
        $this->assertStringContainsString('Gym Bro', $prompt);
        $this->assertStringContainsString('protein', $prompt);
        $this->assertStringContainsString('padu', $prompt);
        $this->assertStringContainsString('macros', $prompt);
        $this->assertStringContainsString('bro', $prompt);
    }

    /**
     * Test Atas persona includes correct characteristics.
     */
    public function test_atas_persona_includes_correct_characteristics(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'atas', $places);

        // Assert
        $this->assertStringContainsString('Atas Friend', $prompt);
        $this->assertStringContainsString('aesthetic', $prompt);
        $this->assertStringContainsString('Instagram', $prompt);
        $this->assertStringContainsString('Darling', $prompt);
        $this->assertStringContainsString('ambiance', $prompt);
    }

    /**
     * Test Tauke persona includes correct characteristics.
     */
    public function test_tauke_persona_includes_correct_characteristics(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'tauke', $places);

        // Assert: Case-insensitive checks for key characteristics
        $this->assertStringContainsStringIgnoringCase('Tauke', $prompt);
        $this->assertStringContainsStringIgnoringCase('time is money', $prompt);
        $this->assertStringContainsStringIgnoringCase('efficiency', $prompt);
        $this->assertStringContainsString('Wa tell you ah', $prompt);
        $this->assertStringContainsString('Ong', $prompt);
        $this->assertStringContainsStringIgnoringCase('air-cond', $prompt);
    }

    /**
     * Test Mat Motor persona includes correct characteristics.
     */
    public function test_matmotor_persona_includes_correct_characteristics(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'matmotor', $places);

        // Assert
        $this->assertStringContainsString('Mat Motor', $prompt);
        $this->assertStringContainsString('late-night', $prompt);
        $this->assertStringContainsString('parking', $prompt);
        $this->assertStringContainsString('Member', $prompt);
        $this->assertStringContainsString('lepak', $prompt);
        $this->assertStringContainsString('24-7', $prompt);
    }

    /**
     * Test Corporate persona includes correct characteristics.
     */
    public function test_corporate_persona_includes_correct_characteristics(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'corporate', $places);

        // Assert
        $this->assertStringContainsString('Corporate Slave', $prompt);
        $this->assertStringContainsString('lunch break', $prompt);
        $this->assertStringContainsString('WiFi', $prompt);
        $this->assertStringContainsString('coffee', $prompt);
        $this->assertStringContainsString('healing', $prompt);
        $this->assertStringContainsString('B40', $prompt);
    }

    /**
     * Test invalid persona throws exception.
     */
    public function test_invalid_persona_throws_exception(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid persona');

        $this->builder->build('Test query', 'invalid_persona', $places);
    }

    /**
     * Test prompt includes user query.
     */
    public function test_prompt_includes_user_query(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);
        $userQuery = 'Where can I get spicy nasi lemak in Bangsar?';

        // Act
        $prompt = $this->builder->build($userQuery, 'makcik', $places);

        // Assert
        $this->assertStringContainsString($userQuery, $prompt);
        $this->assertStringContainsString('USER QUERY', $prompt);
    }

    /**
     * Test prompt includes restaurant context as JSON.
     */
    public function test_prompt_includes_restaurant_context(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make([
                'name' => 'Village Park Restaurant',
                'area' => 'Damansara',
                'price' => 'budget',
            ]),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertStringContainsString('Village Park Restaurant', $prompt);
        $this->assertStringContainsString('Damansara', $prompt);
        $this->assertStringContainsString('budget', $prompt);
        $this->assertStringContainsString('AVAILABLE RESTAURANTS', $prompt);
    }

    /**
     * Test prompt with multiple places.
     */
    public function test_prompt_with_multiple_places(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make(['name' => 'Place 1']),
            Place::factory()->make(['name' => 'Place 2']),
            Place::factory()->make(['name' => 'Place 3']),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'gymbro', $places);

        // Assert
        $this->assertStringContainsString('Place 1', $prompt);
        $this->assertStringContainsString('Place 2', $prompt);
        $this->assertStringContainsString('Place 3', $prompt);
    }

    /**
     * Test prompt with empty places collection.
     */
    public function test_prompt_with_empty_places_collection(): void
    {
        // Arrange
        $places = collect([]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertStringContainsString('[]', $prompt);
        $this->assertStringContainsString('AVAILABLE RESTAURANTS', $prompt);
    }

    /**
     * Test JSON context includes all relevant fields.
     */
    public function test_json_context_includes_relevant_fields(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make([
                'name' => 'Test Restaurant',
                'description' => 'Great food',
                'area' => 'Bangsar',
                'price' => 'moderate',
                'is_halal' => true,
                'cuisine_type' => 'Malaysian',
                'tags' => ['nasi lemak', 'breakfast'],
                'opening_hours' => '7am-10pm',
            ]),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertStringContainsString('"name": "Test Restaurant"', $prompt);
        $this->assertStringContainsString('"description": "Great food"', $prompt);
        $this->assertStringContainsString('"area": "Bangsar"', $prompt);
        $this->assertStringContainsString('"price": "moderate"', $prompt);
        $this->assertStringContainsString('"halal": true', $prompt);
        $this->assertStringContainsString('"cuisine": "Malaysian"', $prompt);
        $this->assertStringContainsString('"tags"', $prompt);
        $this->assertStringContainsString('"hours": "7am-10pm"', $prompt);
    }

    /**
     * Test JSON context handles null values.
     */
    public function test_json_context_handles_null_values(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make([
                'name' => 'Test Place',
                'description' => null,
                'cuisine_type' => null,
                'opening_hours' => null,
            ]),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'gymbro', $places);

        // Assert: Should include null values in JSON
        $this->assertStringContainsString('"description": null', $prompt);
        $this->assertStringContainsString('"cuisine": null', $prompt);
        $this->assertStringContainsString('"hours": null', $prompt);
    }

    /**
     * Test prompt includes instructions section.
     */
    public function test_prompt_includes_instructions(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertStringContainsString('INSTRUCTIONS', $prompt);
        $this->assertStringContainsString('Analyze the user', $prompt);
        $this->assertStringContainsString('1-3 places', $prompt);
        $this->assertStringContainsString('Stay in character', $prompt);
        $this->assertStringContainsString('200 words', $prompt);
    }

    /**
     * Test edge case: Very long user query.
     */
    public function test_very_long_user_query(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);
        $longQuery = str_repeat('I want to eat something delicious. ', 50);

        // Act
        $prompt = $this->builder->build($longQuery, 'makcik', $places);

        // Assert
        $this->assertStringContainsString($longQuery, $prompt);
    }

    /**
     * Test edge case: User query with special characters.
     */
    public function test_user_query_with_special_characters(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);
        $queryWithSpecialChars = "I want nasi lemak & roti canai! (spicy) 🌶️";

        // Act
        $prompt = $this->builder->build($queryWithSpecialChars, 'gymbro', $places);

        // Assert
        $this->assertStringContainsString($queryWithSpecialChars, $prompt);
    }

    /**
     * Test edge case: Place with empty tags array.
     */
    public function test_place_with_empty_tags(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make([
                'name' => 'No Tags Place',
                'tags' => [],
            ]),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertStringContainsString('"tags": []', $prompt);
    }

    /**
     * Test edge case: Place with many tags.
     */
    public function test_place_with_many_tags(): void
    {
        // Arrange
        $manyTags = ['nasi lemak', 'breakfast', 'halal', 'spicy', 'local', 'popular', 'cheap', 'authentic'];
        $places = collect([
            Place::factory()->make(['tags' => $manyTags]),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert
        foreach ($manyTags as $tag) {
            $this->assertStringContainsString($tag, $prompt);
        }
    }

    /**
     * Test JSON is pretty-printed for readability.
     */
    public function test_json_is_pretty_printed(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make(['name' => 'Test Place']),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert: JSON should have newlines (pretty print)
        $this->assertStringContainsString("{\n", $prompt);
        $this->assertMatchesRegularExpression('/\n\s+\"name\"/', $prompt);
    }

    /**
     * Test Unicode characters are preserved (Malaysian slang).
     */
    public function test_unicode_characters_preserved(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make([
                'description' => 'Sedap gila! Best nasi lemak in KL 🔥',
            ]),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert: Unicode emoji and Malay text should be preserved
        $this->assertStringContainsString('Sedap gila', $prompt);
        $this->assertStringContainsString('🔥', $prompt);
    }

    /**
     * Test prompt structure for each persona.
     */
    public function test_all_personas_have_consistent_structure(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);
        $personas = ['makcik', 'gymbro', 'atas', 'tauke', 'matmotor', 'corporate'];

        foreach ($personas as $persona) {
            // Act
            $prompt = $this->builder->build('Where to eat?', $persona, $places);

            // Assert: All prompts should have these sections
            $this->assertStringContainsString('SYSTEM ROLE', $prompt, "Missing SYSTEM ROLE for {$persona}");
            $this->assertStringContainsString('AVAILABLE RESTAURANTS', $prompt, "Missing RESTAURANTS for {$persona}");
            $this->assertStringContainsString('USER QUERY', $prompt, "Missing USER QUERY for {$persona}");
            $this->assertStringContainsString('INSTRUCTIONS', $prompt, "Missing INSTRUCTIONS for {$persona}");
        }
    }

    /**
     * Test persona-specific tag hints are included.
     */
    public function test_persona_specific_tag_hints(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act & Assert: Tauke
        $taukePrompt = $this->builder->build('Where to eat?', 'tauke', $places);
        $this->assertStringContainsString('speedy', $taukePrompt);
        $this->assertStringContainsString('parking', $taukePrompt);

        // Act & Assert: Mat Motor
        $matMotorPrompt = $this->builder->build('Where to eat?', 'matmotor', $places);
        $this->assertStringContainsString('late-night', $matMotorPrompt);
        $this->assertStringContainsString('mamak', $matMotorPrompt);

        // Act & Assert: Corporate
        $corporatePrompt = $this->builder->build('Where to eat?', 'corporate', $places);
        $this->assertStringContainsString('coffee', $corporatePrompt);
        $this->assertStringContainsString('wifi', $corporatePrompt);
    }

    /**
     * Test case sensitivity of persona validation.
     */
    public function test_persona_validation_is_case_sensitive(): void
    {
        // Arrange
        $places = collect([Place::factory()->make()]);

        // Act & Assert: Uppercase should fail
        $this->expectException(InvalidArgumentException::class);
        $this->builder->build('Where to eat?', 'MAKCIK', $places);
    }

    /**
     * Test edge case: Single place in collection.
     */
    public function test_single_place_in_collection(): void
    {
        // Arrange
        $places = collect([
            Place::factory()->make(['name' => 'Only One Place']),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'makcik', $places);

        // Assert
        $this->assertStringContainsString('Only One Place', $prompt);
        $this->assertStringNotContainsString('[]', $prompt);
    }

    /**
     * Test edge case: Place with very long description.
     */
    public function test_place_with_very_long_description(): void
    {
        // Arrange
        $longDescription = str_repeat('This is a very long description of the restaurant. ', 20);
        $places = collect([
            Place::factory()->make(['description' => $longDescription]),
        ]);

        // Act
        $prompt = $this->builder->build('Where to eat?', 'gymbro', $places);

        // Assert: Full description should be included
        $this->assertStringContainsString($longDescription, $prompt);
    }
}
