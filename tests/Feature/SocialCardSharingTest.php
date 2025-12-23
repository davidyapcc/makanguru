<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ChatInterface;
use App\Services\SocialCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for social card sharing functionality.
 *
 * Tests the integration between ChatInterface and SocialCardService.
 */
class SocialCardSharingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use fake storage for testing
        Storage::fake('public');
    }

    /** @test */
    public function it_generates_social_card_when_share_button_is_clicked(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->set('chatHistory', [
                [
                    'role' => 'user',
                    'content' => 'Where to get nasi lemak?',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Go to Village Park! Best nasi lemak in town!',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                    'is_fallback' => false,
                ],
            ]);

        // Share the assistant message (index 1)
        $component->call('shareMessage', 1);

        // Assert card preview is set
        $component->assertSet('cardPreview.message_index', 1);
        $this->assertNotNull($component->get('cardPreview'));

        // Assert a social card file was created
        $cardPreview = $component->get('cardPreview');
        $this->assertNotNull($cardPreview['filename']);
        Storage::disk('public')->assertExists($cardPreview['filename']);
    }

    /** @test */
    public function it_only_allows_sharing_assistant_messages(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->set('chatHistory', [
                [
                    'role' => 'user',
                    'content' => 'Where to eat?',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
            ]);

        // Try to share a user message (should not work)
        $component->call('shareMessage', 0);

        // Assert card preview was NOT set
        $component->assertSet('cardPreview', null);
    }

    /** @test */
    public function it_handles_invalid_message_index(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->set('chatHistory', [
                [
                    'role' => 'assistant',
                    'content' => 'Test message',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
            ]);

        // Try to share a non-existent message
        $component->call('shareMessage', 999);

        // Assert card preview was NOT set
        $component->assertSet('cardPreview', null);
    }

    /** @test */
    public function it_closes_card_preview_modal(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->set('chatHistory', [
                [
                    'role' => 'user',
                    'content' => 'Where to eat?',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Try this place!',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
            ]);

        // Generate card
        $component->call('shareMessage', 1);
        $this->assertNotNull($component->get('cardPreview'));

        // Close the preview
        $component->call('closeCardPreview');
        $component->assertSet('cardPreview', null);
    }

    /** @test */
    public function it_includes_user_query_in_card(): void
    {
        $userQuery = 'Where can I find the best nasi lemak?';
        $recommendation = 'Visit Village Park Restaurant!';

        $component = Livewire::test(ChatInterface::class)
            ->set('chatHistory', [
                [
                    'role' => 'user',
                    'content' => $userQuery,
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
                [
                    'role' => 'assistant',
                    'content' => $recommendation,
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
            ]);

        $component->call('shareMessage', 1);

        $cardPreview = $component->get('cardPreview');
        $content = Storage::disk('public')->get($cardPreview['filename']);

        // Assert both query and recommendation are in the card
        $this->assertStringContainsString($userQuery, $content);
        $this->assertStringContainsString($recommendation, $content);
    }

    /** @test */
    public function it_generates_cards_with_different_personas(): void
    {
        $personas = ['makcik', 'gymbro', 'atas'];

        foreach ($personas as $persona) {
            $component = Livewire::test(ChatInterface::class)
                ->set('currentPersona', $persona)
                ->set('chatHistory', [
                    [
                        'role' => 'user',
                        'content' => 'Where to eat?',
                        'persona' => $persona,
                        'model' => 'gemini',
                    ],
                    [
                        'role' => 'assistant',
                        'content' => 'Great food here!',
                        'persona' => $persona,
                        'model' => 'gemini',
                    ],
                ]);

            $component->call('shareMessage', 1);

            $cardPreview = $component->get('cardPreview');
            $this->assertNotNull($cardPreview);

            // Cleanup for next iteration
            Storage::disk('public')->delete($cardPreview['filename']);
        }
    }

    /** @test */
    public function it_provides_shareable_url(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->set('chatHistory', [
                [
                    'role' => 'user',
                    'content' => 'Where to eat?',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Try this place!',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
            ]);

        $component->call('shareMessage', 1);

        $cardPreview = $component->get('cardPreview');

        // Assert URL is accessible
        $this->assertNotNull($cardPreview['url']);
        $this->assertStringContainsString('/storage/', $cardPreview['url']);
        $this->assertStringContainsString('.svg', $cardPreview['url']);
    }

    /** @test */
    public function it_handles_multiple_cards_in_conversation(): void
    {
        $component = Livewire::test(ChatInterface::class)
            ->set('chatHistory', [
                [
                    'role' => 'user',
                    'content' => 'First question?',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'First answer',
                    'persona' => 'makcik',
                    'model' => 'gemini',
                ],
                [
                    'role' => 'user',
                    'content' => 'Second question?',
                    'persona' => 'gymbro',
                    'model' => 'gemini',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Second answer',
                    'persona' => 'gymbro',
                    'model' => 'gemini',
                ],
            ]);

        // Share first message
        $component->call('shareMessage', 1);
        $firstCard = $component->get('cardPreview');
        $this->assertNotNull($firstCard);

        // Share second message
        $component->call('shareMessage', 3);
        $secondCard = $component->get('cardPreview');
        $this->assertNotNull($secondCard);

        // Assert they're different cards
        $this->assertNotEquals($firstCard['filename'], $secondCard['filename']);
    }
}
