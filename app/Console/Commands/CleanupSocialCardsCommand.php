<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SocialCardService;
use Illuminate\Console\Command;

/**
 * Clean up old social media cards from storage.
 *
 * This command removes social cards older than 7 days to prevent
 * storage bloat. Should be run daily via cron.
 *
 * @package App\Console\Commands
 */
class CleanupSocialCardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'makanguru:cleanup-cards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old social media cards (older than 7 days)';

    /**
     * Execute the console command.
     */
    public function handle(SocialCardService $cardService): int
    {
        $this->info('Cleaning up old social cards...');

        $deleted = $cardService->cleanupOldCards();

        if ($deleted > 0) {
            $this->info("Successfully deleted {$deleted} old social card(s).");
        } else {
            $this->info('No old social cards found to delete.');
        }

        return Command::SUCCESS;
    }
}
