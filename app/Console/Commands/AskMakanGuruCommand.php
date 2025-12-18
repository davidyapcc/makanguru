<?php

namespace App\Console\Commands;

use App\Contracts\AIRecommendationInterface;
use App\Models\Place;
use App\Services\GeminiService;
use App\Services\GroqService;
use Illuminate\Console\Command;

class AskMakanGuruCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'makanguru:ask
                            {query : Your food query (e.g., "I want nasi lemak in Damansara")}
                            {--persona=makcik : The AI persona to use (makcik|gymbro|atas)}
                            {--model=gemini : The AI model to use (gemini|groq-openai|groq-meta)}
                            {--area= : Optional: Filter by area}
                            {--halal : Optional: Only show halal places}
                            {--price= : Optional: Filter by price (budget|moderate|expensive)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ask MakanGuru for restaurant recommendations using AI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = $this->argument('query');
        $persona = $this->option('persona');
        $model = $this->option('model');

        // Validate persona
        if (!in_array($persona, ['makcik', 'gymbro', 'atas'])) {
            $this->error("Invalid persona '{$persona}'. Must be: makcik, gymbro, or atas");
            return self::FAILURE;
        }

        // Validate model
        if (!in_array($model, ['gemini', 'groq-openai', 'groq-meta'])) {
            $this->error("Invalid model '{$model}'. Must be: gemini, groq-openai, or groq-meta");
            return self::FAILURE;
        }

        $this->info("🍜 Asking {$persona} (using {$model}) about: {$query}");
        $this->newLine();

        // Resolve the AI service
        $aiService = $this->getAiService($model);

        // Build query based on filters
        $placesQuery = Place::query();

        if ($area = $this->option('area')) {
            $placesQuery->inArea($area);
            $this->line("📍 Filtering by area: {$area}");
        }

        if ($this->option('halal')) {
            $placesQuery->halalOnly();
            $this->line("✅ Filtering halal only");
        }

        if ($price = $this->option('price')) {
            $placesQuery->byPrice($price);
            $this->line("💰 Filtering by price: {$price}");
        }

        $places = $placesQuery->get();

        if ($places->isEmpty()) {
            $this->error('No places found matching your filters!');
            return self::FAILURE;
        }

        $this->line("📊 Found {$places->count()} places to analyze");
        $this->newLine();

        // Show loading spinner
        $this->info('🤖 Thinking...');

        try {
            // Get recommendation
            $recommendation = $aiService->recommend($query, $persona, $places);

            // Display result
            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line($recommendation->recommendation);
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();

            // Show metadata
            if ($recommendation->isFallback()) {
                $this->warn('⚠️  This is a fallback response (API unavailable)');
            } else {
                $this->comment("📝 Tokens used: {$recommendation->getTokensUsed()}");
            }

            if (!empty($recommendation->suggestedPlaces)) {
                $this->newLine();
                $this->comment('Mentioned places: ' . implode(', ', $recommendation->suggestedPlaces));
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to get recommendation: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Get the AI service based on the selected model.
     *
     * @param string $model
     * @return AIRecommendationInterface
     */
    private function getAiService(string $model): AIRecommendationInterface
    {
        $service = match ($model) {
            'groq-openai', 'groq-meta' => app(GroqService::class),
            default => app(GeminiService::class),
        };

        // If it's Groq, we can set the specific model based on selection
        if ($service instanceof GroqService) {
            $specificModel = match ($model) {
                'groq-openai' => config('services.groq.models.openai'),
                'groq-meta' => config('services.groq.models.meta'),
                default => config('services.groq.models.default'),
            };
            $service->setModel($specificModel);
        }

        return $service;
    }
}
