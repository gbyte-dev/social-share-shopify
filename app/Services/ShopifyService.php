<?php

namespace App\Services;

use App\Models\ShopifyStore;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
                'Shopify API credentials missing in configuration. '.
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

        return "https://{$canonicalShop}/admin/oauth/authorize?".http_build_query($query);
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
                return 'https://'.rtrim($decoded, '/').'/apps/'.$apiKey;
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
            /** @var Response $response */
            $response = Http::asJson()
                ->timeout(15)
                ->post($url, [
                    'client_id' => config('shopify.api_key'),
                    'client_secret' => config('shopify.api_secret'),
                    'code' => $code,
                ]);
        } catch (\Throwable $e) {
            // Note: Never log client_secret or sensitive codes
            Log::error('Shopify OAuth token exchange network error', [
                'shop' => $canonicalShop,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to connect to Shopify for token exchange: '.$e->getMessage());
        }

        if (! $response->successful()) {
            Log::error('Shopify OAuth token exchange rejected by Shopify', [
                'shop' => $canonicalShop,
                'status' => $response->status(),
                'error' => $response->json('error_description') ?? $response->json('error') ?? 'Unknown error',
            ]);

            $errorMsg = $response->json('error_description')
                ?? $response->json('error')
                ?? "Shopify rejected token exchange with HTTP {$response->status()}";

            throw new RuntimeException($errorMsg);
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;

        if (empty($accessToken)) {
            throw new RuntimeException('Shopify response did not contain an access token.');
        }

        return [
            'access_token' => (string) $accessToken,
            'scope' => isset($data['scope']) ? (string) $data['scope'] : null,
        ];
    }

    /**
     * Persist or update store record with the newly acquired access token idempotently.
     */
    public function persistInstalledStore(string $shop, string $accessToken, ?string $scope = null): ShopifyStore
    {
        $canonicalShop = $this->normalizeShopDomain($shop);
        if (! $canonicalShop) {
            throw new RuntimeException("Cannot persist invalid shop domain: {$shop}");
        }

        /** @var ShopifyStore $store */
        $store = ShopifyStore::updateOrCreate(
            ['shop' => $canonicalShop],
            [
                'access_token' => $accessToken,
                'scope' => $scope ?? config('shopify.api_scopes'),
                'is_active' => true,
                'installed_at' => now(),
                'uninstalled_at' => null,
            ]
        );

        return $store;
    }
}
