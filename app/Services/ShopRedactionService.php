<?php

namespace App\Services;

use App\Models\PendingShopRedaction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShopRedactionService
{
    /**
     * Safely purge all filesystem assets associated with a specific store ID.
     *
     * @return bool True if all target directories were successfully purged or did not exist.
     */
    public function purgeShopFilesystem(int $storeId): bool
    {
        $allClean = true;

        try {
            $publicDisk = Storage::disk('public');
            if ($publicDisk->exists("logos/{$storeId}")) {
                $deleted = $publicDisk->deleteDirectory("logos/{$storeId}");
                if (! $deleted && $publicDisk->exists("logos/{$storeId}")) {
                    $allClean = false;
                }
            }
        } catch (\Throwable $e) {
            $allClean = false;
            Log::warning('Failed to delete shop logos directory', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $publicUploadsDir = public_path("uploads/{$storeId}");
            if (File::isDirectory($publicUploadsDir)) {
                $deleted = File::deleteDirectory($publicUploadsDir);
                if (! $deleted && File::isDirectory($publicUploadsDir)) {
                    $allClean = false;
                }
            }
        } catch (\Throwable $e) {
            $allClean = false;
            Log::warning('Failed to delete shop public uploads directory', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $localDisk = Storage::disk('local');
            if ($localDisk->exists("gdpr/exports/{$storeId}")) {
                $deleted = $localDisk->deleteDirectory("gdpr/exports/{$storeId}");
                if (! $deleted && $localDisk->exists("gdpr/exports/{$storeId}")) {
                    $allClean = false;
                }
            }
        } catch (\Throwable $e) {
            $allClean = false;
            Log::warning('Failed to delete shop GDPR exports directory', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
        }

        return $allClean;
    }

    /**
     * Process a pending shop redaction record.
     * Deletes the tracking record if filesystem cleanup succeeds, or updates attempts and error state on failure.
     */
    public function processPendingRedaction(PendingShopRedaction $pending): bool
    {
        try {
            $success = $this->purgeShopFilesystem((int) $pending->store_id);

            if ($success) {
                $pending->delete();
                Log::info('Pending shop filesystem cleanup succeeded and record removed', [
                    'shop_domain' => $pending->shop_domain,
                    'store_id' => $pending->store_id,
                ]);

                return true;
            }

            $pending->increment('attempts', 1, [
                'last_attempt_at' => now(),
                'last_error' => 'Filesystem purge did not completely remove all directories',
            ]);

            Log::warning('Pending shop filesystem cleanup failed during retry', [
                'shop_domain' => $pending->shop_domain,
                'store_id' => $pending->store_id,
                'attempts' => $pending->attempts,
            ]);

            return false;
        } catch (\Throwable $e) {
            $pending->increment('attempts', 1, [
                'last_attempt_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            Log::error('Exception during pending shop filesystem cleanup retry', [
                'shop_domain' => $pending->shop_domain,
                'store_id' => $pending->store_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
