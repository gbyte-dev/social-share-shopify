<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shopify App API Credentials
    |--------------------------------------------------------------------------
    |
    | The Client ID (API Key) and Client Secret provided by the Shopify Partner
    | / Dev Dashboard for your public application.
    |
    */

    'api_key' => env('SHOPIFY_API_KEY'),

    'api_secret' => env('SHOPIFY_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Shopify Application URL & OAuth Redirect URI
    |--------------------------------------------------------------------------
    |
    | The public URL where this application is hosted, and the callback URI
    | registered in the Shopify Dev Dashboard.
    |
    */

    'app_url' => env('SHOPIFY_APP_URL', 'https://socialnest.test'),

    'redirect_uri' => env('SHOPIFY_REDIRECT_URI', 'https://socialnest.test/shopify/callback'),

    /*
    |--------------------------------------------------------------------------
    | Shopify Admin API Scopes
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of scopes required by SocialNest.
    | Only minimal required permissions are requested by default.
    |
    */

    'api_scopes' => env('SHOPIFY_API_SCOPES') ?: 'write_content,write_online_store_pages',

    /*
    |--------------------------------------------------------------------------
    | Shopify API Version
    |--------------------------------------------------------------------------
    |
    | The supported Admin API version for API calls.
    |
    */

    'api_version' => env('SHOPIFY_API_VERSION', '2025-01'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Secret used for validating incoming webhook payloads. Defaults to
    | api_secret if not separately specified.
    |
    */

    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET', env('SHOPIFY_API_SECRET')),

];
