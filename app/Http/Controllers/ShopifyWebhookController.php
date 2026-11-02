<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Services\ShopifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function __construct(
        protected ShopifyService $shopifyService
    ) {}

    /**
     * Process incoming webhooks from Shopify.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $topic = $request->header('X-Shopify-Topic');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        // Verify HMAC signature
        if (! $this->shopifyService->verifyWebhookHmac($rawPayload, $hmacHeader)) {
            Log::warning('Rejected Shopify webhook with invalid HMAC signature', [
                'topic' => $topic,
                'shop' => $shopDomain,
            ]);

            return response()->json(['error' => 'Invalid HMAC signature'], 401);
        }

        $canonicalShop = $this->shopifyService->normalizeShopDomain($shopDomain);
        $data = json_decode($rawPayload, true) ?? [];

        Log::info("Shopify webhook received: {$topic}", [
            'shop' => $canonicalShop,
            'topic' => $topic,
        ]);

        switch ($topic) {
            case 'app/uninstalled':
                $this->handleAppUninstalled($canonicalShop);
                break;

            case 'customers/data_request':
                // Mandatory GDPR compliance webhook
                Log::info('Shopify customer data request received', ['shop' => $canonicalShop]);
                break;

            case 'customers/redact':
                // Mandatory GDPR customer redact webhook
                Log::info('Shopify customer redact request received', ['shop' => $canonicalShop]);
                break;

            case 'shop/redact':
                // Mandatory 48h shop redact webhook
                Log::info('Shopify shop redact request received', ['shop' => $canonicalShop]);
                $this->handleAppUninstalled($canonicalShop);
                break;

            default:
                Log::info("Unhandled Shopify webhook topic: {$topic}", ['shop' => $canonicalShop]);
                break;
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook received']);
    }

    /**
     * Mark a store as uninstalled idempotently.
     */
    protected function handleAppUninstalled(?string $shop): void
    {
        if (! $shop) {
            return;
        }

        $store = ShopifyStore::where('shop', $shop)->first();

        if ($store) {
            $store->markUninstalled();
            Log::info("Store marked as uninstalled: {$shop}");
        }
    }
}
