<?php

namespace App\Console\Commands;

use App\Services\GeminiService;
use Illuminate\Console\Command;

class ListGeminiModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gemini:list-models
                            {--json : Output as JSON}
                            {--filter= : Filter models by name (e.g., "gemini-2.5")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available Google Gemini models';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Fetching available Gemini models...');
        $this->newLine();

        try {
            $data = GeminiService::listModels();

            if (!isset($data['models']) || empty($data['models'])) {
                $this->warn('No models found or unexpected response format.');
                return self::FAILURE;
            }

            $models = collect($data['models']);

            // Apply filter if provided
            if ($filter = $this->option('filter')) {
                $models = $models->filter(function ($model) use ($filter) {
                    return str_contains($model['name'] ?? '', $filter);
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
                    'name' => str_replace('models/', '', $model['name'] ?? 'N/A'),
                    'display_name' => $model['displayName'] ?? 'N/A',
                    'version' => $model['version'] ?? 'N/A',
                    'description' => $this->truncate($model['description'] ?? 'No description', 60),
                    'input_limit' => number_format($model['inputTokenLimit'] ?? 0),
                    'output_limit' => number_format($model['outputTokenLimit'] ?? 0),
                    'supports_generation' => in_array('generateContent', $model['supportedGenerationMethods'] ?? []) ? '✓' : '✗',
                ];
            })->all();

            $this->table(
                ['Model Name', 'Display Name', 'Version', 'Description', 'Input Tokens', 'Output Tokens', 'Generate'],
                $tableData
            );

            $this->newLine();
            $this->comment('💡 Tip: Use --filter="gemini-2.5" to filter specific models');
            $this->comment('💡 Tip: Use --json for JSON output');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to fetch models: ' . $e->getMessage());
            $this->newLine();
            $this->comment('💡 Make sure GEMINI_API_KEY is set in your .env file');
            return self::FAILURE;
        }
    }

    /**
     * Truncate string to specified length.
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - 3) . '...';
    }
}
