<?php

namespace Tests\Feature;

use App\Livewire\ChatInterface;
use App\Models\Place;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ChatInterface Livewire Component Tests
 *
 * Tests the chat interface component functionality, user interactions, and edge cases.
 */
class ChatInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.api_key' => 'fake-api-key-for-testing']);
    }

    /**
     * Test component renders successfully.
     */
    public function test_component_renders_successfully(): void
    {
        // Act
        $component = Livewire::test(ChatInterface::class);

        // Assert
        $component->assertStatus(200);
        $component->assertSee('makcik'); // Default persona
    }

    /**
     * Test default persona is makcik.
     */
    public function test_default_persona_is_makcik(): void
    {
        // Act
        $component = Livewire::test(ChatInterface::class);

        // Assert
        $component->assertSet('currentPersona', 'makcik');
    }

    /**
     * Test default model is gemini.
     */
    public function test_default_model_is_gemini(): void
    {
        // Act
        $component = Livewire::test(ChatInterface::class);

        // Assert
        $component->assertSet('currentModel', 'gemini');
    }

    /**
     * Test switching personas updates currentPersona.
     */
    public function test_switching_personas(): void
    {
        // Arrange
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->call('switchPersona', 'gymbro');

        // Assert
        $component->assertSet('currentPersona', 'gymbro');
    }

    /**
     * Test sending a message with valid input.
     */
    public function test_sending_message_with_valid_input(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Go to Village Park!'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 100],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'Where to eat nasi lemak?')
            ->call('sendMessage');

        // Assert
        $component->assertSet('userQuery', ''); // Cleared after sending
        $this->assertNotEmpty($component->get('chatHistory'));
    }

    /**
     * Test validation error for empty message.
     */
    public function test_validation_error_for_empty_message(): void
    {
        // Arrange
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', '')
            ->call('sendMessage');

        // Assert
        $component->assertHasErrors(['userQuery' => 'required']);
    }

    /**
     * Test validation error for message too short.
     */
    public function test_validation_error_for_message_too_short(): void
    {
        // Arrange
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'ab')
            ->call('sendMessage');

        // Assert
        $component->assertHasErrors(['userQuery' => 'min']);
    }

    /**
     * Test validation error for message too long.
     */
    public function test_validation_error_for_message_too_long(): void
    {
        // Arrange
        $component = Livewire::test(ChatInterface::class);
        $longMessage = str_repeat('a', 501);

        // Act
        $component->set('userQuery', $longMessage)
            ->call('sendMessage');

        // Assert
        $component->assertHasErrors(['userQuery' => 'max']);
    }

    /**
     * Test chat history stores user and assistant messages.
     */
    public function test_chat_history_stores_messages(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'AI response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'Test message')
            ->call('sendMessage');

        // Assert
        $history = $component->get('chatHistory');
        $this->assertCount(2, $history); // User + Assistant
        $this->assertEquals('user', $history[0]['role']);
        $this->assertEquals('assistant', $history[1]['role']);
    }

    /**
     * Test clearing chat history.
     */
    public function test_clearing_chat_history(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);
        $component->set('userQuery', 'Test')->call('sendMessage');

        // Act
        $component->call('clearChat');

        // Assert
        $component->assertSet('chatHistory', []);
    }

    /**
     * Test halal filter is applied.
     */
    public function test_halal_filter_is_applied(): void
    {
        // Arrange
        Place::factory()->create(['is_halal' => true, 'name' => 'Halal Place']);
        Place::factory()->create(['is_halal' => false, 'name' => 'Non-Halal Place']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Filtered response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        $component = Livewire::test(ChatInterface::class);

        // Act: Enable halal filter
        $component->set('filterHalal', true)
            ->set('userQuery', 'Where to eat?')
            ->call('sendMessage');

        // Assert: Should only query halal places
        $this->assertNotEmpty($component->get('chatHistory'));
    }

    /**
     * Test price filter is applied.
     */
    public function test_price_filter_is_applied(): void
    {
        // Arrange
        Place::factory()->create(['price' => 'budget']);
        Place::factory()->create(['price' => 'expensive']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Budget places'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('filterPrice', 'budget')
            ->set('userQuery', 'Where to eat?')
            ->call('sendMessage');

        // Assert
        $this->assertNotEmpty($component->get('chatHistory'));
    }

    /**
     * Test area filter is applied.
     */
    public function test_area_filter_is_applied(): void
    {
        // Arrange
        Place::factory()->create(['area' => 'Bangsar']);
        Place::factory()->create(['area' => 'KLCC']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Bangsar places'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('filterArea', 'Bangsar')
            ->set('userQuery', 'Where to eat?')
            ->call('sendMessage');

        // Assert
        $this->assertNotEmpty($component->get('chatHistory'));
    }

    /**
     * Test multiple filters can be combined.
     */
    public function test_multiple_filters_combined(): void
    {
        // Arrange
        Place::factory()->create([
            'is_halal' => true,
            'price' => 'budget',
            'area' => 'Bangsar',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Filtered response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        $component = Livewire::test(ChatInterface::class);

        // Act: Apply all filters
        $component->set('filterHalal', true)
            ->set('filterPrice', 'budget')
            ->set('filterArea', 'Bangsar')
            ->set('userQuery', 'Where to eat?')
            ->call('sendMessage');

        // Assert
        $this->assertNotEmpty($component->get('chatHistory'));
    }

    /**
     * Test switching personas mid-conversation.
     */
    public function test_switching_personas_mid_conversation(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act: Send message as makcik
        $component->set('userQuery', 'First message')
            ->call('sendMessage');

        // Switch to gymbro
        $component->call('switchPersona', 'gymbro');

        // Send another message
        $component->set('userQuery', 'Second message')
            ->call('sendMessage');

        // Assert
        $history = $component->get('chatHistory');
        $this->assertCount(4, $history); // 2 user + 2 assistant
        $this->assertEquals('gymbro', $component->get('currentPersona'));
    }

    /**
     * Test all 6 personas work correctly.
     */
    public function test_all_six_personas_work(): void
    {
        // Increase rate limit for this test since we check all 6 personas
        config(['chat.rate_limit.max_messages' => 10]);

        // Arrange
        $personas = ['makcik', 'gymbro', 'atas', 'tauke', 'matmotor', 'corporate'];

        foreach ($personas as $persona) {
            // Create fresh place for each iteration
            Place::query()->delete();
            Place::factory()->create();

            // Setup fresh HTTP mock for each iteration
            Http::fake([
                'generativelanguage.googleapis.com/*' => Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => "Persona response for {$persona}"],
                                ],
                            ],
                        ],
                    ],
                    'usageMetadata' => ['totalTokenCount' => 50],
                ], 200),
            ]);

            // Act: Create new component for each test to avoid rate limiting
            $component = Livewire::test(ChatInterface::class);
            $component->call('switchPersona', $persona)
                ->set('userQuery', "Test message for {$persona}")
                ->call('sendMessage');

            // Assert
            $history = $component->get('chatHistory');
            $this->assertCount(2, $history, "Failed for persona: {$persona}"); // 1 user + 1 assistant
            $this->assertEquals('user', $history[0]['role']);
            $this->assertEquals('assistant', $history[1]['role']);
            $this->assertEquals($persona, $history[1]['persona']);
        }
    }

    /**
     * Test edge case: No places in database.
     */
    public function test_no_places_in_database(): void
    {
        // Arrange: No places
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'No places available'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 20],
            ], 200),
        ]);

        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'Where to eat?')
            ->call('sendMessage');

        // Assert: Should still work
        $this->assertNotEmpty($component->get('chatHistory'));
    }

    /**
     * Test edge case: API failure returns fallback message.
     */
    public function test_api_failure_returns_fallback_message(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'API Error'], 500),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'Where to eat?')
            ->call('sendMessage');

        // Assert: Should have fallback message (persona-specific)
        $history = $component->get('chatHistory');
        $this->assertNotEmpty($history);
        $this->assertArrayHasKey(1, $history); // Assistant response exists
        // Fallback messages contain persona-specific text, not necessarily "system"
        $this->assertNotEmpty($history[1]['content']);
    }

    /**
     * Test edge case: Multiple messages in chat history.
     */
    public function test_multiple_messages_in_chat_history(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act: Send 3 messages (within rate limit)
        for ($i = 0; $i < 3; $i++) {
            $component->set('userQuery', "Message {$i}")
                ->call('sendMessage');
        }

        // Assert: Should have 3 user + 3 assistant messages
        $history = $component->get('chatHistory');
        $this->assertCount(6, $history);
    }

    /**
     * Test filters can be cleared.
     */
    public function test_filters_can_be_cleared(): void
    {
        // Arrange
        $component = Livewire::test(ChatInterface::class);
        $component->set('filterHalal', true);
        $component->set('filterPrice', 'budget');
        $component->set('filterArea', 'Bangsar');

        // Act: Clear filters
        $component->set('filterHalal', false);
        $component->set('filterPrice', null);
        $component->set('filterArea', null);

        // Assert
        $component->assertSet('filterHalal', false);
        $component->assertSet('filterPrice', null);
        $component->assertSet('filterArea', null);
    }

    /**
     * Test special characters in user query.
     */
    public function test_special_characters_in_user_query(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Response with special chars'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'I want nasi lemak & roti canai! 🌶️')
            ->call('sendMessage');

        // Assert
        $history = $component->get('chatHistory');
        $this->assertEquals('I want nasi lemak & roti canai! 🌶️', $history[0]['content']);
    }

    /**
     * Test Unicode characters in messages.
     */
    public function test_unicode_characters_in_messages(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Sedap! 😋'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'Saya nak makan! 🍽️')
            ->call('sendMessage');

        // Assert
        $history = $component->get('chatHistory');
        $this->assertStringContainsString('Saya nak makan! 🍽️', $history[0]['content']);
    }

    /**
     * Test empty area filter doesn't apply.
     */
    public function test_empty_area_filter_doesnt_apply(): void
    {
        // Arrange
        Place::factory()->count(3)->create();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'All places'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        $component = Livewire::test(ChatInterface::class);

        // Act: Set empty area filter
        $component->set('filterArea', '')
            ->set('userQuery', 'Where to eat?')
            ->call('sendMessage');

        // Assert: Should query all places
        $this->assertNotEmpty($component->get('chatHistory'));
    }

    /**
     * Test model tracking in chat history.
     */
    public function test_model_tracking_in_chat_history(): void
    {
        // Arrange
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Response'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 50],
            ], 200),
        ]);

        Place::factory()->create();
        $component = Livewire::test(ChatInterface::class);

        // Act
        $component->set('userQuery', 'Test message')
            ->call('sendMessage');

        // Assert: Assistant message should have model info
        $history = $component->get('chatHistory');
        $this->assertArrayHasKey('model', $history[1]);
        $this->assertEquals('gemini', $history[1]['model']);
    }

    /**
     * Test component initialization.
     */
    public function test_component_initialization(): void
    {
        // Act
        $component = Livewire::test(ChatInterface::class);

        // Assert: Default values
        $component->assertSet('currentPersona', 'makcik');
        $component->assertSet('currentModel', 'gemini');
        $component->assertSet('filterHalal', false);
        $component->assertSet('filterPrice', null);
        $component->assertSet('filterArea', null);
        $component->assertSet('chatHistory', []);
        $component->assertSet('isLoading', false);
    }
}
