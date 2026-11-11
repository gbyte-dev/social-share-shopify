<?php

namespace App\Services;

use App\Models\ShopifyStore;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShopifyService
{
    /**
     * Regex pattern to strictly validate a canonical myshopify.com domain.
     * Subdomain must be alphanumeric with optional single hyphens, followed by .myshopify.com
     */
    public const SHOP_DOMAIN_REGEX = '/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/';

    /**
     * Validate that necessary Shopify credentials are configured.
     *
     * @throws RuntimeException
     */
    public function validateCredentials(): void
    {
        $apiKey = config('shopify.api_key');
        $apiSecret = config('shopify.api_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            throw new RuntimeException(
                'Shopify API credentials missing in configuration. ' .
                    'Ensure SHOPIFY_API_KEY and SHOPIFY_API_SECRET are set in your environment file.'
            );
        }
    }

    /**
     * Normalize and sanitize a user-supplied shop domain.
     * Returns the canonical domain (e.g. "my-store.myshopify.com") or null if invalid.
     */
    public function normalizeShopDomain(?string $shop): ?string
    {
        if ($shop === null) {
            return null;
        }

        $shop = trim($shop);

        if ($shop === '') {
            return null;
        }

        // If provided as a full URL with scheme, parse host
        if (preg_match('#^https?://#i', $shop)) {
            $host = parse_url($shop, PHP_URL_HOST);
            if (! $host) {
                return null;
            }
            $shop = $host;
        } else {
            // Reject if it contains slashes, spaces, colons, or URI components
            if (preg_match('~[/?:#\s\\\\]~', $shop)) {
                return null;
            }
        }

        $shop = strtolower(trim($shop));

        // Strip port if present
        $portParts = explode(':', $shop);
        $shop = $portParts[0];

        // If user entered only the subdomain part (e.g. "my-store"), append .myshopify.com
        if (! str_contains($shop, '.')) {
            $shop .= '.myshopify.com';
        }

        // Must strictly match canonical myshopify.com format
        if (! preg_match(self::SHOP_DOMAIN_REGEX, $shop)) {
            return null;
        }

        return $shop;
    }

    /**
     * Check if a shop domain is valid.
     */
    public function isValidShopDomain(?string $shop): bool
    {
        return $this->normalizeShopDomain($shop) !== null;
    }

    /**
     * Build the Shopify OAuth authorization redirect URL.
     */
    public function getAuthorizeUrl(string $shop, string $state): string
    {
        $this->validateCredentials();

        $canonicalShop = $this->normalizeShopDomain($shop);
        if (! $canonicalShop) {
            throw new RuntimeException("Invalid Shopify store domain: {$shop}");
        }

        $redirectUri = config('shopify.redirect_uri');
        if (empty($redirectUri)) {
            throw new RuntimeException('Shopify redirect URI is not configured.');
        }

        $query = [
            'client_id' => config('shopify.api_key'),
            'scope' => config('shopify.api_scopes', 'read_products'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ];

        return "https://{$canonicalShop}/admin/oauth/authorize?" . http_build_query($query);
    }

    /**
     * Build the Shopify Admin embedded app URL for a given store.
     * Used to embed or redirect standalone browser tabs into Shopify Admin.
     */
    public function getAdminAppUrl(string $shop, ?string $host = null): string
    {
        $apiKey = (string) config('shopify.api_key');

        if ($host) {
            $base64 = strtr($host, '-_', '+/');
            $padding = strlen($base64) % 4;
            if ($padding > 0) {
                $base64 .= str_repeat('=', 4 - $padding);
            }
            $decoded = base64_decode($base64, true);
            if ($decoded && (str_contains($decoded, 'admin.shopify.com') || str_contains($decoded, '.myshopify.com'))) {
                return 'https://' . rtrim($decoded, '/') . '/apps/' . $apiKey;
            }
        }

        $canonicalShop = $this->normalizeShopDomain($shop);
        if ($canonicalShop) {
            $shopHandle = str_replace('.myshopify.com', '', $canonicalShop);

            return "https://admin.shopify.com/store/{$shopHandle}/apps/{$apiKey}";
        }

        return "https://admin.shopify.com/store/apps/{$apiKey}";
    }

    /**
     * Verify the HMAC signature of incoming Shopify OAuth callback parameters.
     */
    public function verifyHmac(array $params): bool
    {
        if (! isset($params['hmac']) || empty($params['hmac'])) {
            return false;
        }

        $secret = config('shopify.api_secret');
        if (empty($secret)) {
            return false;
        }

        $hmac = (string) $params['hmac'];

        // Remove hmac and signature from parameters
        unset($params['hmac'], $params['signature']);

        // Sort keys lexicographically
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $pairs[] = "{$key}={$val}";
        }

        $queryString = implode('&', $pairs);
        $calculatedHmac = hash_hmac('sha256', $queryString, $secret);

        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Verify incoming webhook HMAC signature from X-Shopify-Hmac-Sha256 header.
     */
    public function verifyWebhookHmac(string $rawBody, ?string $hmacHeader): bool
    {
        if (empty($hmacHeader)) {
            return false;
        }

        $secret = config('shopify.webhook_secret', config('shopify.api_secret'));
        if (empty($secret)) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($calculated, $hmacHeader);
    }

    /**
     * Exchange the temporary authorization code for a permanent offline access token.
     *
     * @return array{access_token: string, scope: ?string}
     *
     * @throws RuntimeException
     */
    public function exchangeCodeForAccessToken(string $shop, string $code): array
    {
        $this->validateCredentials();

        $canonicalShop = $this->normalizeShopDomain($shop);

        if (! $canonicalShop) {
            throw new RuntimeException("Invalid Shopify store domain: {$shop}");
        }

        $url = "https://{$canonicalShop}/admin/oauth/access_token";

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($url, [
                    'client_id' => config('shopify.api_key'),
                    'client_secret' => config('shopify.api_secret'),
                    'code' => $code,
                    'expiring' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::error('Shopify OAuth token exchange network error', [
                'shop' => $canonicalShop,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to connect to Shopify for token exchange.');
        }

        if (! $response->successful()) {
            Log::error('Shopify OAuth token exchange rejected', [
                'shop' => $canonicalShop,
                'status' => $response->status(),
                'error' => $response->json('error_description')
                    ?? $response->json('error')
                    ?? 'Unknown error',
            ]);

            throw new RuntimeException(
                "Shopify rejected token exchange with HTTP {$response->status()}"
            );
        }

        $data = $response->json();

        if (
            empty($data['access_token']) ||
            empty($data['refresh_token']) ||
            empty($data['expires_in']) ||
            empty($data['refresh_token_expires_in'])
        ) {
            throw new RuntimeException(
                'Shopify did not return the required expiring token data.'
            );
        }

        return [
            'access_token' => (string) $data['access_token'],
            'refresh_token' => (string) $data['refresh_token'],
            'expires_in' => (int) $data['expires_in'],
            'refresh_token_expires_in' => (int) $data['refresh_token_expires_in'],
            'scope' => isset($data['scope']) ? (string) $data['scope'] : null,
        ];
    }

    /**
     * Persist or update store record with the newly acquired access token idempotently.
     */
    public function persistInstalledStore(string $shop, array $tokenData): ShopifyStore
    {
        $canonicalShop = $this->normalizeShopDomain($shop);

        if (! $canonicalShop) {
            throw new RuntimeException("Cannot persist invalid shop domain: {$shop}");
        }

        foreach (
            [
                'access_token',
                'refresh_token',
                'expires_in',
                'refresh_token_expires_in',
            ] as $key
        ) {
            if (empty($tokenData[$key])) {
                throw new RuntimeException("Missing required Shopify token field: {$key}");
            }
        }

        return ShopifyStore::updateOrCreate(
            ['shop' => $canonicalShop],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'access_token_expires_at' => now()->addSeconds(
                    (int) $tokenData['expires_in']
                ),
                'refresh_token_expires_at' => now()->addSeconds(
                    (int) $tokenData['refresh_token_expires_in']
                ),
                'scope' => $tokenData['scope'] ?? config('shopify.api_scopes'),
                'is_active' => true,
                'installed_at' => now(),
                'uninstalled_at' => null,
            ]
        );
    }

    public function refreshAccessToken(ShopifyStore $store): ShopifyStore
    {
        return DB::transaction(function () use ($store) {
            $lockedStore = ShopifyStore::whereKey($store->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Another request may already have refreshed the token.
            if (
                $lockedStore->access_token_expires_at &&
                $lockedStore->access_token_expires_at->isAfter(now()->addMinutes(5))
            ) {
                return $lockedStore;
            }

            if (
                empty($lockedStore->refresh_token) ||
                ! $lockedStore->refresh_token_expires_at ||
                $lockedStore->refresh_token_expires_at->isPast()
            ) {
                throw new RuntimeException(
                    'Shopify refresh token is missing or expired. Store reauthorization is required.'
                );
            }

            $canonicalShop = $this->normalizeShopDomain($lockedStore->shop);

            if (! $canonicalShop) {
                throw new RuntimeException('Invalid Shopify store domain.');
            }

            $response = Http::asForm()
                ->timeout(15)
                ->post("https://{$canonicalShop}/admin/oauth/access_token", [
                    'client_id' => config('shopify.api_key'),
                    'client_secret' => config('shopify.api_secret'),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $lockedStore->refresh_token,
                ]);

            if (! $response->successful()) {
                Log::error('Shopify token refresh rejected', [
                    'shop' => $canonicalShop,
                    'status' => $response->status(),
                    'error' => $response->json('error_description')
                        ?? $response->json('error')
                        ?? 'Unknown error',
                ]);

                throw new RuntimeException(
                    "Shopify token refresh failed with HTTP {$response->status()}."
                );
            }

            $data = $response->json();

            if (
                empty($data['access_token']) ||
                empty($data['refresh_token']) ||
                empty($data['expires_in']) ||
                empty($data['refresh_token_expires_in'])
            ) {
                throw new RuntimeException(
                    'Shopify refresh response is missing required token fields.'
                );
            }

            $lockedStore->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'access_token_expires_at' => now()->addSeconds(
                    (int) $data['expires_in']
                ),
                'refresh_token_expires_at' => now()->addSeconds(
                    (int) $data['refresh_token_expires_in']
                ),
            ]);

            Log::info('Shopify access token refreshed successfully', [
                'shop' => $canonicalShop,
                'access_token_expires_at' => $lockedStore->access_token_expires_at,
            ]);

            return $lockedStore->fresh();
        });
    }

    /**
     * Exchange a legacy non-expiring offline token for an expiring offline token via Shopify Token Exchange.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_in: ?int}
     *
     * @throws RuntimeException
     */
    public function migrateLegacyTokenToExpiringToken(ShopifyStore $store): array
    {
        $this->validateCredentials();

        $canonicalShop = $this->normalizeShopDomain($store->shop);
        if (! $canonicalShop) {
            throw new RuntimeException("Invalid Shopify store domain: {$store->shop}");
        }

        $url = "https://{$canonicalShop}/admin/oauth/access_token";

        try {
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(15)
                ->post($url, [
                    'client_id' => config('shopify.api_key'),
                    'client_secret' => config('shopify.api_secret'),
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
                    'subject_token' => $store->access_token,
                    'subject_token_type' => 'urn:shopify:params:oauth:token-type:offline-access-token',
                    'requested_token_type' => 'urn:shopify:params:oauth:token-type:offline-access-token',
                    'expiring' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::error('Shopify legacy token exchange network error', [
                'shop' => $canonicalShop,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to exchange legacy token with Shopify: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            Log::error('Shopify token exchange rejected', [
                'shop' => $canonicalShop,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException("Shopify rejected token exchange with HTTP {$response->status()}");
        }

        $data = $response->json();
        $newAccessToken = $data['access_token'] ?? null;
        if (empty($newAccessToken)) {
            throw new RuntimeException('Shopify response did not contain a new access token.');
        }

        $expiresIn = (int) ($data['expires_in'] ?? 0);
        $refreshTokenExpiresIn = (int) ($data['refresh_token_expires_in'] ?? 0);
        $refreshToken = $data['refresh_token'] ?? null;

        if (! $refreshToken || $expiresIn <= 0 || $refreshTokenExpiresIn <= 0) {
            throw new RuntimeException(
                'Shopify migration response is missing required expiring token data.'
            );
        }

        $store->update([
            'access_token' => $newAccessToken,
            'refresh_token' => $refreshToken,
            'access_token_expires_at' => now()->addSeconds($expiresIn),
            'refresh_token_expires_at' => now()->addSeconds($refreshTokenExpiresIn),
        ]);

        Log::info('Successfully migrated store token to expiring token', [
            'shop' => $canonicalShop,
            'expires_in' => $data['expires_in'] ?? null,
        ]);

        return [
            'access_token' => (string) $newAccessToken,
            'refresh_token' => (string) $refreshToken,
            'expires_in' => $expiresIn,
            'refresh_token_expires_in' => $refreshTokenExpiresIn,
        ];
    }

    /**
     * Execute an authenticated GraphQL Admin API query or mutation against a Shopify store.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function executeGraphQL(ShopifyStore $store, string $query, array $variables = []): array
    {
        $canonicalShop = $this->normalizeShopDomain($store->shop);
        if (! $canonicalShop) {
            throw new RuntimeException("Invalid Shopify store domain: {$store->shop}");
        }

        // Refresh expiring offline access token before making Shopify API request.
        if ($store->access_token_expires_at) {
            $store = $this->refreshAccessToken($store);
        }

        if (empty($store->access_token)) {
            throw new RuntimeException("Store {$store->shop} is missing an access token.");
        }

        $apiVersion = config('shopify.api_version', '2026-10');
        $endpoint = "https://{$canonicalShop}/admin/api/{$apiVersion}/graphql.json";

        $payload = ['query' => $query];
        if (! empty($variables)) {
            $payload['variables'] = $variables;
        }

        try {
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(15)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $store->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);
        } catch (\Throwable $e) {
            Log::error('Shopify GraphQL network error', [
                'shop' => $canonicalShop,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to communicate with Shopify GraphQL API: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            $body = $response->body();

            // If Shopify rejects legacy non-expiring token, automatically migrate to expiring token and retry once
            if ($response->status() === 403 && str_contains($body, 'Non-expiring access tokens are no longer accepted')) {
                Log::info('Detected legacy non-expiring token, attempting automatic migration to expiring token', [
                    'shop' => $canonicalShop,
                ]);

                try {
                    $this->migrateLegacyTokenToExpiringToken($store);
                    $store->refresh();

                    $retryResponse = Http::asJson()
                        ->timeout(15)
                        ->withHeaders([
                            'X-Shopify-Access-Token' => $store->access_token,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])
                        ->post($endpoint, [
                            'query' => $query,
                            'variables' => $variables,
                        ]);

                    if ($retryResponse->successful()) {
                        $retryData = $retryResponse->json();
                        if (! isset($retryData['errors']) || empty($retryData['errors'])) {
                            return $retryData;
                        }
                    }

                    $response = $retryResponse;
                    $body = $response->body();
                } catch (\Throwable $migrationEx) {
                    Log::warning('Automatic token migration failed or retry unsuccessful', [
                        'shop' => $canonicalShop,
                        'error' => $migrationEx->getMessage(),
                    ]);
                }
            }

            Log::error('Shopify GraphQL HTTP error', [
                'shop' => $canonicalShop,
                'status' => $response->status(),
                'body' => $body,
            ]);

            throw new RuntimeException("Shopify GraphQL request failed with HTTP {$response->status()}");
        }

        $data = $response->json();

        if (isset($data['errors']) && ! empty($data['errors'])) {
            $errorMessage = is_array($data['errors'])
                ? ($data['errors'][0]['message'] ?? json_encode($data['errors']))
                : (string) $data['errors'];

            Log::error('Shopify GraphQL API error response', [
                'shop' => $canonicalShop,
                'errors' => $data['errors'],
            ]);

            throw new RuntimeException("Shopify GraphQL error: {$errorMessage}");
        }

        return $data;
    }

    /**
     * Find an existing Wishlist page in Shopify by handle.
     *
     * @return array{id: string, title: string, handle: string}|null
     */
    public function getWishlistPage(ShopifyStore $store, string $handle = 'wishlist'): ?array
    {
        $query = <<<'GRAPHQL'
query getPagesByHandle($query: String!) {
    pages(first: 1, query: $query) {
        edges {
            node {
                id
                title
                handle
            }
        }
    }
}
GRAPHQL;

        $response = $this->executeGraphQL($store, $query, [
            'query' => "handle:{$handle}",
        ]);

        $edges = $response['data']['pages']['edges'] ?? [];
        if (! empty($edges) && isset($edges[0]['node'])) {
            $node = $edges[0]['node'];
            if (isset($node['handle']) && strtolower($node['handle']) === strtolower($handle)) {
                return [
                    'id' => (string) $node['id'],
                    'title' => (string) ($node['title'] ?? 'Wishlist'),
                    'handle' => (string) $node['handle'],
                ];
            }
        }

        return null;
    }

    /**
     * Create and publish the /pages/wishlist page using the pageCreate GraphQL mutation.
     * Preserves existing Wishlist page if it already exists without overwriting.
     *
     * @return array{page: array{id: string, title: string, handle: string}, created: bool, message: string}
     */
    public function createWishlistPage(ShopifyStore $store, string $handle = 'wishlist', string $title = 'Wishlist'): array
    {
        // 1. Check if page already exists to preserve existing content
        $existing = $this->getWishlistPage($store, $handle);
        if ($existing !== null) {
            Log::info('Shopify Wishlist page already exists, preserving existing page', [
                'shop' => $store->shop,
                'page' => $existing,
            ]);

            return [
                'page' => $existing,
                'created' => false,
                'message' => 'Existing Wishlist page found and preserved.',
            ];
        }

        // 2. Page does not exist; create it using pageCreate mutation
        $mutation = <<<'GRAPHQL'
mutation pageCreate($page: PageCreateInput!) {
    pageCreate(page: $page) {
        page {
            id
            title
            handle
            isPublished
        }
        userErrors {
            field
            message
        }
    }
}
GRAPHQL;

        $response = $this->executeGraphQL($store, $mutation, [
            'page' => [
                'title' => $title,
                'handle' => $handle,
                'isPublished' => true,
            ],
        ]);

        $userErrors = $response['data']['pageCreate']['userErrors'] ?? [];
        if (! empty($userErrors)) {
            $errorMsg = collect($userErrors)->pluck('message')->implode('; ');
            Log::error('Shopify pageCreate userErrors', [
                'shop' => $store->shop,
                'errors' => $userErrors,
            ]);

            throw new RuntimeException("Shopify could not create page: {$errorMsg}");
        }

        $createdPage = $response['data']['pageCreate']['page'] ?? null;
        if (! $createdPage || empty($createdPage['id'])) {
            throw new RuntimeException('Shopify pageCreate returned an empty page payload.');
        }

        Log::info('Successfully created and published Shopify Wishlist page', [
            'shop' => $store->shop,
            'page' => $createdPage,
        ]);

        return [
            'page' => [
                'id' => (string) $createdPage['id'],
                'title' => (string) $createdPage['title'],
                'handle' => (string) $createdPage['handle'],
            ],
            'created' => true,
            'message' => 'Wishlist page successfully created and published.',
        ];
    }

    /**
     * Generate a Shopify Theme Editor deep link that opens the Wishlist page template
     * and automatically adds the SocialNest Wishlist app block.
     *
     * @param  string  $shop  Shopify store domain
     * @param  string  $target  Target section ("mainSection" or "newAppsSection")
     */
    public function getWishlistThemeEditorDeepLink(
        string $shop,
        string $target = 'mainSection',
        bool $addWishlistBlock = true
    ): string {
        $canonicalShop = $this->normalizeShopDomain($shop);
        if (! $canonicalShop) {
            throw new RuntimeException("Invalid Shopify store domain: {$shop}");
        }

        $apiKey = (string) config('shopify.api_key');
        if (empty($apiKey)) {
            throw new RuntimeException('Shopify API Key (client_id) is not configured.');
        }

        $blockHandle = 'wishlist-page';
        $validTargets = ['mainSection', 'newAppsSection', 'apps'];
        $resolvedTarget = in_array($target, $validTargets, true) ? $target : 'mainSection';

        $query = [
            'template' => 'page',
            'target' => $resolvedTarget,
        ];

        if ($addWishlistBlock) {
            $query['addAppBlockId'] = "{$apiKey}/{$blockHandle}";
        }
        return "https://{$canonicalShop}/admin/themes/current/editor?" . http_build_query($query);
    }
}
