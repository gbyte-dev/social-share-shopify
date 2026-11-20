<?php

return [
    /*
     * |--------------------------------------------------------------------------
     * | Shopify App API Credentials
     * |--------------------------------------------------------------------------
     * |
     * | The Client ID (API Key) and Client Secret provided by the Shopify Partner
     * | / Dev Dashboard for your public application.
     * |
     */
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),

    /*
     * |--------------------------------------------------------------------------
     * | Shopify Application URL & OAuth Redirect URI
     * |--------------------------------------------------------------------------
     * |
     * | The public URL where this application is hosted, and the callback URI
     * | registered in the Shopify Dev Dashboard.
     * |
     */
    'app_url' => env('SHOPIFY_APP_URL', env('APP_URL', 'https://socialvire.webvire.com')),
    'redirect_uri' => env('SHOPIFY_REDIRECT_URI', env('APP_URL') ? rtrim(env('APP_URL'), '/') . '/shopify/callback' : 'https://socialnest.developerbrijesh.in/shopify/callback'),

    /*
     * |--------------------------------------------------------------------------
     * | Shopify Admin API Scopes
     * |--------------------------------------------------------------------------
     * |
     * | Comma-separated list of scopes required by SocialNest.
     * | Only minimal required permissions are requested by default.
     * |
     */
    'api_scopes' => env('SHOPIFY_API_SCOPES')
        ?: 'write_content,write_online_store_pages,read_themes,write_themes',

    /*
     * |--------------------------------------------------------------------------
     * | Shopify API Version
     * |--------------------------------------------------------------------------
     * |
     * | The supported Admin API version for API calls.
     * |
     */
    'api_version' => env('SHOPIFY_API_VERSION', '2026-10'),

    /*
     * |--------------------------------------------------------------------------
     * | Webhook Configuration
     * |--------------------------------------------------------------------------
     * |
     * | Secret used for validating incoming webhook payloads. Defaults to
     * | api_secret if not separately specified.
     * |
     */
    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET', env('SHOPIFY_API_SECRET')),

    /*
     * |--------------------------------------------------------------------------
     * | Merchant Support Configuration
     * |--------------------------------------------------------------------------
     * |
     * | The official support email address displayed to merchants on the
     * | Help & Support page.
     * |
     */
    'support_email' => env('SUPPORT_EMAIL', 'support@socialnest.io'),

    /*
     * |--------------------------------------------------------------------------
     * | Shopify App Pricing & Partner API Configuration
     * |--------------------------------------------------------------------------
     * |
     * | Settings for Shopify App Pricing and Partner GraphQL API queries.
     * |
     */
    'partner_org_id' => env('SHOPIFY_PARTNER_ORG_ID'),
    'partner_api_access_token' => env('SHOPIFY_PARTNER_API_ACCESS_TOKEN'),
    'partner_api_version' => env('SHOPIFY_PARTNER_API_VERSION', '2026-10'),
    'app_gid' => env('SHOPIFY_APP_GID'),
    'app_handle' => env('SHOPIFY_APP_HANDLE', 'socialvire'),
    'pricing_plan_handle' => env('SHOPIFY_PRICING_PLAN_HANDLE', 'free'),

    /*
     * |--------------------------------------------------------------------------
     * | Shopify App Proxy Configuration
     * |--------------------------------------------------------------------------
     * |
     * | Configured subpath and prefix registered with Shopify Partner Dashboard.
     * |
     */
    'app_proxy_prefix' => env('SHOPIFY_APP_PROXY_PREFIX', 'apps'),
    'app_proxy_subpath' => env('SHOPIFY_APP_PROXY_SUBPATH', 'socialvire'),
];
