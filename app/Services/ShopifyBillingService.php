<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ShopifyStore;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShopifyBillingService
{
    public function __construct(
        protected ShopifyService $shopifyService
    ) {}

    /**
     * Create a recurring application charge via Shopify Admin GraphQL API.
     *
     * @return array{confirmationUrl: string, subscriptionId: string}
     *
     * @throws RuntimeException
     */
    public function createRecurringSubscription(
        ShopifyStore $store,
        Plan $plan,
        string $returnUrl,
        ?bool $test = null
    ): array {
        Log::info('ShopifyBillingService::createRecurringSubscription deactivated - application uses internal dummy subscription workflow', [
            'shop' => $store->shop,
            'plan' => $plan->name,
        ]);

        throw new RuntimeException('Live Shopify Billing API is deactivated. SocialNest operates exclusively using its internal dummy subscription workflow.');
    }

    /**
     * Query and verify the current status of an AppSubscription directly from Shopify.
     *
     * @return array{id: string, name: string, status: string, currentPeriodEnd: ?string}|null
     *
     * @throws RuntimeException
     */
    public function getSubscriptionStatus(ShopifyStore $store, string $chargeId): ?array
    {
        $query = <<<'GRAPHQL'
query GetAppSubscription($id: ID!) {
    node(id: $id) {
        ... on AppSubscription {
            id
            name
            status
            currentPeriodEnd
            createdAt
        }
    }
}
GRAPHQL;

        $response = $this->shopifyService->executeGraphQL($store, $query, ['id' => $chargeId]);

        $node = $response['data']['node'] ?? null;
        if (! $node || empty($node['id'])) {
            return null;
        }

        return [
            'id' => (string) $node['id'],
            'name' => (string) ($node['name'] ?? ''),
            'status' => strtoupper((string) ($node['status'] ?? 'UNKNOWN')),
            'currentPeriodEnd' => isset($node['currentPeriodEnd']) ? (string) $node['currentPeriodEnd'] : null,
        ];
    }

    /**
     * Cancel a recurring app subscription via Shopify GraphQL API.
     *
     * @return array{cancelled: bool, status: string}
     *
     * @throws RuntimeException
     */
    public function cancelRecurringSubscription(ShopifyStore $store, string $chargeId): array
    {
        Log::info('ShopifyBillingService::cancelRecurringSubscription deactivated - application uses internal dummy subscription workflow', [
            'shop' => $store->shop,
            'charge_id' => $chargeId,
        ]);

        return [
            'cancelled' => true,
            'status' => 'CANCELLED',
        ];
    }
}
