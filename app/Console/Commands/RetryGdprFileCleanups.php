<?php

namespace App\Console\Commands;

use App\Models\PendingShopRedaction;
use App\Services\ShopRedactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gdpr:retry-file-cleanups')]
#[Description('Retry filesystem cleanup for pending GDPR shop redactions')]
class RetryGdprFileCleanups extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ShopRedactionService $shopRedactionService): int
    {
        $pendingRecords = PendingShopRedaction::all();

        if ($pendingRecords->isEmpty()) {
            $this->info('No pending GDPR shop redactions found.');

            return self::SUCCESS;
        }

        $this->info("Found {$pendingRecords->count()} pending GDPR shop redaction(s). Beginning cleanup retries...");

        $successCount = 0;
        $failCount = 0;

        foreach ($pendingRecords as $pending) {
            $this->line("Processing pending redaction for shop: {$pending->shop_domain} (Store ID: {$pending->store_id})...");

            $cleaned = $shopRedactionService->processPendingRedaction($pending);

            if ($cleaned) {
                $this->info("✓ Successfully purged filesystem assets and deleted pending tracking for {$pending->shop_domain}.");
                $successCount++;
            } else {
                $this->warn("✗ Filesystem cleanup failed for {$pending->shop_domain}. Remaining in pending queue.");
                $failCount++;
            }
        }

        $this->info("Cleanup retry completed. Succeeded: {$successCount}, Failed: {$failCount}.");

        return self::SUCCESS;
    }
}
