<?php

namespace App\Console\Commands;

use App\Services\GroqService;
use Illuminate\Console\Command;

class ListGroqModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'groq:list-models
                            {--json : Output as JSON}
                            {--filter= : Filter models by name (e.g., "llama")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available Groq models';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Fetching available Groq models...');
        $this->newLine();

        try {
            $data = GroqService::listModels();

            if (!isset($data['data']) || empty($data['data'])) {
                $this->warn('No models found or unexpected response format.');
                return self::FAILURE;
            }

            $models = collect($data['data']);

            // Apply filter if provided
            if ($filter = $this->option('filter')) {
                $models = $models->filter(function ($model) use ($filter) {
                    return str_contains($model['id'] ?? '', $filter);
                });

                if ($models->isEmpty()) {
                    $this->warn("No models found matching filter: {$filter}");
                    return self::FAILURE;
                }
            }

            // Output as JSON if requested
            if ($this->option('json')) {
                $this->line(json_encode($models->all(), JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            // Pretty table output
            $this->info("Found {$models->count()} model(s):");
            $this->newLine();

            $tableData = $models->map(function ($model) {
                return [
                    'id' => $model['id'] ?? 'N/A',
                    'owned_by' => $model['owned_by'] ?? 'N/A',
                    'created' => date('Y-m-d H:i:s', $model['created'] ?? 0),
                    'context_window' => number_format($model['context_window'] ?? 0),
                ];
            })->all();

            $this->table(
                ['Model ID', 'Owned By', 'Created', 'Context Window'],
                $tableData
            );

            $this->newLine();
            $this->comment('💡 Tip: Use --filter="llama" to filter specific models');
            $this->comment('💡 Tip: Use --json for JSON output');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to fetch models: ' . $e->getMessage());
            $this->newLine();
            $this->comment('💡 Make sure GROQ_API_KEY is set in your .env file');
            return self::FAILURE;
        }
    }
}

