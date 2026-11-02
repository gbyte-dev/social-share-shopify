<?php

namespace App\Services;

class SocialPlatformService
{
    /**
     * Supported platforms and their configuration metadata.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $platforms = [
        'instagram' => [
            'key' => 'instagram',
            'name' => 'Instagram',
            'brand_color' => '#E4405F',
            'placeholder' => 'https://www.instagram.com/yourprofile',
            'description' => 'Link to your Instagram profile, post, or destination URL.',
        ],
        'facebook' => [
            'key' => 'facebook',
            'name' => 'Facebook',
            'brand_color' => '#1877F2',
            'placeholder' => 'https://www.facebook.com/yourpage',
            'description' => 'Link to your Facebook page, group, or destination URL.',
        ],
        'youtube' => [
            'key' => 'youtube',
            'name' => 'YouTube',
            'brand_color' => '#FF0000',
            'placeholder' => 'https://www.youtube.com/@yourchannel',
            'description' => 'Link to your YouTube channel, video, or destination URL.',
        ],
        'pinterest' => [
            'key' => 'pinterest',
            'name' => 'Pinterest',
            'brand_color' => '#BD081C',
            'placeholder' => 'https://www.pinterest.com/yourprofile',
            'description' => 'Link to your Pinterest profile, board, pin, or destination URL.',
        ],
        'tiktok' => [
            'key' => 'tiktok',
            'name' => 'TikTok',
            'brand_color' => '#000000',
            'placeholder' => 'https://www.tiktok.com/@yourprofile',
            'description' => 'Link to your TikTok profile, video, or destination URL.',
        ],
        'x' => [
            'key' => 'x',
            'name' => 'X',
            'brand_color' => '#000000',
            'placeholder' => 'https://x.com/yourprofile',
            'description' => 'Link to your X (Twitter) profile, post, or destination URL.',
        ],
        'whatsapp' => [
            'key' => 'whatsapp',
            'name' => 'WhatsApp',
            'brand_color' => '#25D366',
            'placeholder' => 'https://wa.me/15551234567',
            'description' => 'Link to your WhatsApp chat, group, or destination URL.',
        ],
        'reddit' => [
            'key' => 'reddit',
            'name' => 'Reddit',
            'brand_color' => '#FF4500',
            'placeholder' => 'https://www.reddit.com/r/yourcommunity',
            'description' => 'Link to your Reddit community, profile, or post.',
        ],
        'other' => [
            'key' => 'other',
            'name' => 'Other / Custom Platform',
            'brand_color' => '#4f46e5',
            'placeholder' => 'https://example.com/yourprofile',
            'description' => 'Connect any custom social platform, community, or messaging app (e.g. Discord, Telegram, Snapchat, Reddit, Threads, Twitch).',
        ],
    ];

    /**
     * Get all supported platforms.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$platforms;
    }

    /**
     * Get keys of all supported platforms.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::$platforms);
    }

    /**
     * Get keys of only built-in supported platforms.
     *
     * @return list<string>
     */
    public static function builtInKeys(): array
    {
        return array_values(array_filter(array_keys(self::$platforms), fn ($k) => $k !== 'other'));
    }

    /**
     * Determine whether a platform key represents a custom/other platform.
     */
    public static function isCustom(string $platform): bool
    {
        return in_array(strtolower(trim($platform)), ['other', 'custom'], true);
    }

    /**
     * Determine whether a platform key represents a built-in platform.
     */
    public static function isBuiltIn(string $platform): bool
    {
        return ! self::isCustom($platform) && self::exists($platform);
    }

    /**
     * Check if a platform key is valid.
     */
    public static function exists(string $platform): bool
    {
        return isset(self::$platforms[strtolower(trim($platform))]);
    }

    /**
     * Get details for a specific platform.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $platform): ?array
    {
        return self::$platforms[strtolower(trim($platform))] ?? null;
    }

    /**
     * Get user-friendly name for a platform.
     */
    public static function name(string $platform): string
    {
        return self::$platforms[strtolower(trim($platform))]['name'] ?? ucfirst($platform);
    }

    /**
     * Normalize URL (trim whitespace, prepend https:// if scheme is missing).
     */
    public static function normalizeUrl(string $url): string
    {
        $clean = trim($url);

        if (! preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $clean)) {
            $clean = 'https://'.$clean;
        }

        return $clean;
    }

    /**
     * Validate whether a destination URL is valid.
     * Allows any valid HTTP and HTTPS destination URL while rejecting dangerous schemes or malformed syntax.
     *
     * @return array{is_valid: bool, error: ?string, normalized_url: ?string}
     */
    public static function validatePlatformUrl(string $platform, string $url): array
    {
        $platformKey = strtolower(trim($platform));
        $config = self::get($platformKey);

        if (! $config) {
            return [
                'is_valid' => false,
                'error' => "Unsupported social platform: {$platform}",
                'normalized_url' => null,
            ];
        }

        $trimmed = trim($url);
        if ($trimmed === '') {
            return [
                'is_valid' => false,
                'error' => 'Destination URL cannot be empty.',
                'normalized_url' => null,
            ];
        }

        // Explicitly reject dangerous URL schemes (e.g. javascript:, data:, file:, vbscript:)
        if (preg_match('~^\s*(?:javascript|data|file|vbscript):~i', $trimmed)) {
            return [
                'is_valid' => false,
                'error' => 'Dangerous URL schemes are not permitted.',
                'normalized_url' => null,
            ];
        }

        $normalized = self::normalizeUrl($trimmed);

        // Security check: only allow standard web schemes (http or https)
        $scheme = parse_url($normalized, PHP_URL_SCHEME);
        if (! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return [
                'is_valid' => false,
                'error' => 'Only HTTP and HTTPS URLs are permitted.',
                'normalized_url' => null,
            ];
        }

        // Generic URL syntax validation
        if (filter_var($normalized, FILTER_VALIDATE_URL) === false) {
            return [
                'is_valid' => false,
                'error' => 'Please provide a valid, well-formed URL.',
                'normalized_url' => null,
            ];
        }

        // Ensure host is present and syntactically valid
        $host = parse_url($normalized, PHP_URL_HOST);
        if (empty($host) || ! str_contains($host, '.')) {
            return [
                'is_valid' => false,
                'error' => 'Please provide a valid URL with a domain name.',
                'normalized_url' => null,
            ];
        }

        return [
            'is_valid' => true,
            'error' => null,
            'normalized_url' => $normalized,
        ];
    }

    /**
     * Get clean SVG icon markup for a platform.
     */
    public static function iconSvg(string $platform, int $size = 20): string
    {
        $platformKey = strtolower(trim($platform));

        return match ($platformKey) {
            'instagram' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
            'facebook' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            'youtube' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
            'pinterest' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.535 3.554.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>',
            'tiktok' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
            'x', 'twitter' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            'whatsapp' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>',
            'reddit' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.703zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>',
            'other', 'custom' => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>',
            default => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M10 15l5-3-5-3v6z"/></svg>',
        };
    }
}
