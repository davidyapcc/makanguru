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
    protected $signature = 'makanguru:cleanup-cards {--days=7 : Number of days to keep files. 0 to delete all.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old social media cards';

    /**
     * Execute the console command.
     */
    public function handle(SocialCardService $cardService): int
    {
        $days = (int) $this->option('days');

        if ($days === 0) {
            $this->info('Cleaning up ALL social cards...');
        } else {
            $this->info("Cleaning up social cards older than {$days} days...");
        }

        $deleted = $cardService->cleanupOldCards($days);

        if ($deleted > 0) {
            $this->info("Successfully deleted {$deleted} social card(s).");
        } else {
            $this->info('No social cards found to delete.');
        }

        return Command::SUCCESS;
    }
}
