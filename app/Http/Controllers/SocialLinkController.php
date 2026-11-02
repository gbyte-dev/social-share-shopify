<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Services\LogoDiscoveryService;
use App\Services\LogoProcessingService;
use App\Services\SocialPlatformService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SocialLinkController extends Controller
{
    public function __construct(
        protected LogoProcessingService $logoProcessingService,
        protected LogoDiscoveryService $logoDiscoveryService
    ) {}

    /**
     * Display a listing of social links for the authenticated store.
     */
    public function index(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $links = $store->socialLinks()->orderBy('sort_order', 'asc')->get();
        $configuredBuiltInPlatforms = $links->where('platform', '!=', 'other')->pluck('platform')->toArray();
        $allPlatforms = SocialPlatformService::all();

        // Built-ins can only be added once, but "other" (custom platform) is always available
        $availablePlatforms = array_diff_key($allPlatforms, array_flip($configuredBuiltInPlatforms));

        return view('shopify.social-links.index', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'links' => $links,
            'allPlatforms' => $allPlatforms,
            'availablePlatforms' => $availablePlatforms,
        ]);
    }

    /**
     * Show the form for creating a new social link.
     */
    public function create(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $configuredBuiltInPlatforms = $store->socialLinks()->where('platform', '!=', 'other')->pluck('platform')->toArray();
        $allPlatforms = SocialPlatformService::all();
        $availablePlatforms = array_diff_key($allPlatforms, array_flip($configuredBuiltInPlatforms));

        $maxOrder = (int) $store->socialLinks()->max('sort_order');
        $nextOrder = $maxOrder + 1;

        return view('shopify.social-links.create', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'allPlatforms' => $allPlatforms,
            'availablePlatforms' => $availablePlatforms,
            'nextOrder' => $nextOrder,
        ]);
    }

    /**
     * AJAX endpoint: automatic logo discovery for a platform name, destination, or official website.
     */
    public function discoverLogos(Request $request): JsonResponse
    {
        // Enforce store authentication
        $this->resolveStore($request);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->logoDiscoveryService->discover(
            $validated['name'] ?? null,
            $validated['url'] ?? null,
            $validated['website_url'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Store a newly created social link in database.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $platform = strtolower(trim((string) $request->input('platform')));

        if ($platform === 'other') {
            return $this->storeCustomLink($request, $store);
        }

        return $this->storeBuiltInLink($request, $store, $platform);
    }

    /**
     * Store a built-in platform link.
     */
    protected function storeBuiltInLink(Request $request, ShopifyStore $store, string $platform): RedirectResponse
    {
        $allowedPlatforms = SocialPlatformService::builtInKeys();

        $validated = $request->validate([
            'platform' => [
                'required',
                'string',
                Rule::in($allowedPlatforms),
                Rule::unique('social_links')->where(fn ($q) => $q->where('shopify_store_id', $store->id)),
            ],
            'url' => ['required', 'string', 'max:1000'],
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'platform.unique' => 'You already have a social link configured for this platform. Please edit the existing link instead.',
            'platform.in' => 'Selected social media platform is not supported.',
        ]);

        $urlValidation = SocialPlatformService::validatePlatformUrl($validated['platform'], $validated['url']);
        if (! $urlValidation['is_valid']) {
            return back()->withInput()->withErrors(['url' => $urlValidation['error']]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== ''
            ? (int) $validated['sort_order']
            : ((int) $store->socialLinks()->max('sort_order') + 1);

        $store->socialLinks()->create([
            'platform' => strtolower($validated['platform']),
            'platform_type' => 'builtin',
            'url' => $urlValidation['normalized_url'],
            'label' => $validated['label'] ?: SocialPlatformService::name($validated['platform']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $sortOrder,
        ]);

        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))
            ->with('success', 'Social link added successfully!');
    }

    /**
     * Store a custom platform link with automatic or manual logo.
     */
    protected function storeCustomLink(Request $request, ShopifyStore $store): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in(['other'])],
            'custom_platform_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('social_links')->where(fn ($q) => $q->where('shopify_store_id', $store->id)->where('custom_platform_name', $request->input('custom_platform_name'))),
            ],
            'url' => ['required', 'string', 'max:1000'],
            'official_website_url' => ['nullable', 'string', 'max:1000'],
            'label' => ['nullable', 'string', 'max:100'],
            'alt_text' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'logo_file' => ['nullable', 'file', 'max:5120'],
            'selected_logo_url' => ['nullable', 'string', 'max:1000'],
        ], [
            'custom_platform_name.required' => 'Please enter the app or platform name (e.g. Discord, Telegram).',
            'custom_platform_name.unique' => 'A custom platform with this name already exists in your store.',
        ]);

        // Validate destination URL
        $urlValidation = SocialPlatformService::validatePlatformUrl('other', $validated['url']);
        if (! $urlValidation['is_valid']) {
            return back()->withInput()->withErrors(['url' => $urlValidation['error']]);
        }

        // Validate official website URL if provided
        $officialWebsite = null;
        if (! empty($validated['official_website_url'])) {
            $webValidation = SocialPlatformService::validatePlatformUrl('other', $validated['official_website_url']);
            if (! $webValidation['is_valid']) {
                return back()->withInput()->withErrors(['official_website_url' => $webValidation['error']]);
            }
            $officialWebsite = $webValidation['normalized_url'];
        }

        // Process Logo: Manual upload takes precedence, followed by discovered logo URL
        $logoData = null;
        $logoSourceUrl = null;
        $logoSourceDomain = null;

        try {
            if ($request->hasFile('logo_file')) {
                $logoData = $this->logoProcessingService->processUploadedFile($request->file('logo_file'), $store->id);
            } elseif ($request->filled('selected_logo_url')) {
                $selectedUrl = $request->input('selected_logo_url');
                $binary = $this->logoDiscoveryService->downloadRemoteImage($selectedUrl);

                if (! $binary) {
                    return back()->withInput()->withErrors(['logo_file' => 'Failed to download the selected logo. Please upload an image file directly.']);
                }

                $logoData = $this->logoProcessingService->processBinaryData($binary, $store->id, 'discovered_logo');
                $logoSourceUrl = $selectedUrl;
                $logoSourceDomain = parse_url($selectedUrl, PHP_URL_HOST);
            } else {
                return back()->withInput()->withErrors(['logo_file' => 'A logo is required for custom platforms. Please upload a logo file or choose an automatically discovered candidate.']);
            }
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['logo_file' => $e->getMessage()]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== ''
            ? (int) $validated['sort_order']
            : ((int) $store->socialLinks()->max('sort_order') + 1);

        $store->socialLinks()->create([
            'platform' => 'other',
            'platform_type' => 'custom',
            'custom_platform_name' => $validated['custom_platform_name'],
            'official_website_url' => $officialWebsite,
            'original_logo_path' => $logoData['original_logo_path'],
            'logo_small_path' => $logoData['logo_small_path'],
            'logo_medium_path' => $logoData['logo_medium_path'],
            'logo_large_path' => $logoData['logo_large_path'],
            'logo_source_url' => $logoSourceUrl,
            'logo_source_domain' => $logoSourceDomain,
            'url' => $urlValidation['normalized_url'],
            'label' => ! empty($validated['label']) ? $validated['label'] : $validated['custom_platform_name'],
            'alt_text' => ! empty($validated['alt_text']) ? $validated['alt_text'] : $validated['custom_platform_name'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $sortOrder,
        ]);

        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))
            ->with('success', "{$validated['custom_platform_name']} custom link added successfully!");
    }

    /**
     * Show the form for editing an existing social link.
     */
    public function edit(Request $request, int $id): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $link = $store->socialLinks()->findOrFail($id);

        $configuredBuiltInPlatforms = $store->socialLinks()
            ->where('id', '!=', $link->id)
            ->where('platform', '!=', 'other')
            ->pluck('platform')
            ->toArray();

        $allPlatforms = SocialPlatformService::all();
        $availablePlatforms = array_diff_key($allPlatforms, array_flip($configuredBuiltInPlatforms));

        return view('shopify.social-links.edit', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'link' => $link,
            'allPlatforms' => $allPlatforms,
            'availablePlatforms' => $availablePlatforms,
        ]);
    }

    /**
     * Update an existing social link in database.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $link = $store->socialLinks()->findOrFail($id);

        if ($link->isCustom()) {
            return $this->updateCustomLink($request, $link);
        }

        return $this->updateBuiltInLink($request, $store, $link);
    }

    /**
     * Update a built-in platform link.
     */
    protected function updateBuiltInLink(Request $request, ShopifyStore $store, SocialLink $link): RedirectResponse
    {
        $allowedPlatforms = SocialPlatformService::builtInKeys();

        $validated = $request->validate([
            'platform' => [
                'required',
                'string',
                Rule::in($allowedPlatforms),
                Rule::unique('social_links')
                    ->where(fn ($q) => $q->where('shopify_store_id', $store->id))
                    ->ignore($link->id),
            ],
            'url' => ['required', 'string', 'max:1000'],
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'platform.unique' => 'A link for this platform already exists for your store.',
            'platform.in' => 'Selected social media platform is not supported.',
        ]);

        $urlValidation = SocialPlatformService::validatePlatformUrl($validated['platform'], $validated['url']);
        if (! $urlValidation['is_valid']) {
            return back()->withInput()->withErrors(['url' => $urlValidation['error']]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== ''
            ? (int) $validated['sort_order']
            : $link->sort_order;

        $link->update([
            'platform' => strtolower($validated['platform']),
            'url' => $urlValidation['normalized_url'],
            'label' => $validated['label'] ?: SocialPlatformService::name($validated['platform']),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $sortOrder,
        ]);

        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))
            ->with('success', 'Social link updated successfully!');
    }

    /**
     * Update a custom platform link.
     */
    protected function updateCustomLink(Request $request, SocialLink $link): RedirectResponse
    {
        $validated = $request->validate([
            'custom_platform_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('social_links')
                    ->where(fn ($q) => $q->where('shopify_store_id', $link->shopify_store_id)->where('custom_platform_name', $request->input('custom_platform_name')))
                    ->ignore($link->id),
            ],
            'url' => ['required', 'string', 'max:1000'],
            'official_website_url' => ['nullable', 'string', 'max:1000'],
            'label' => ['nullable', 'string', 'max:100'],
            'alt_text' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'logo_file' => ['nullable', 'file', 'max:5120'],
            'selected_logo_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $urlValidation = SocialPlatformService::validatePlatformUrl('other', $validated['url']);
        if (! $urlValidation['is_valid']) {
            return back()->withInput()->withErrors(['url' => $urlValidation['error']]);
        }

        $officialWebsite = null;
        if (! empty($validated['official_website_url'])) {
            $webValidation = SocialPlatformService::validatePlatformUrl('other', $validated['official_website_url']);
            if (! $webValidation['is_valid']) {
                return back()->withInput()->withErrors(['official_website_url' => $webValidation['error']]);
            }
            $officialWebsite = $webValidation['normalized_url'];
        }

        $logoUpdates = [];

        try {
            if ($request->hasFile('logo_file')) {
                $newLogoData = $this->logoProcessingService->processUploadedFile($request->file('logo_file'), $link->shopify_store_id);
                $this->logoProcessingService->deleteOldLogoFiles($link);

                $logoUpdates = [
                    'original_logo_path' => $newLogoData['original_logo_path'],
                    'logo_small_path' => $newLogoData['logo_small_path'],
                    'logo_medium_path' => $newLogoData['logo_medium_path'],
                    'logo_large_path' => $newLogoData['logo_large_path'],
                    'logo_source_url' => null,
                    'logo_source_domain' => null,
                ];
            } elseif ($request->filled('selected_logo_url')) {
                $selectedUrl = $request->input('selected_logo_url');
                $binary = $this->logoDiscoveryService->downloadRemoteImage($selectedUrl);

                if (! $binary) {
                    return back()->withInput()->withErrors(['logo_file' => 'Failed to download selected logo.']);
                }

                $newLogoData = $this->logoProcessingService->processBinaryData($binary, $link->shopify_store_id, 'discovered_logo');
                $this->logoProcessingService->deleteOldLogoFiles($link);

                $logoUpdates = [
                    'original_logo_path' => $newLogoData['original_logo_path'],
                    'logo_small_path' => $newLogoData['logo_small_path'],
                    'logo_medium_path' => $newLogoData['logo_medium_path'],
                    'logo_large_path' => $newLogoData['logo_large_path'],
                    'logo_source_url' => $selectedUrl,
                    'logo_source_domain' => parse_url($selectedUrl, PHP_URL_HOST),
                ];
            }
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['logo_file' => $e->getMessage()]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== ''
            ? (int) $validated['sort_order']
            : $link->sort_order;

        $link->update(array_merge([
            'custom_platform_name' => $validated['custom_platform_name'],
            'official_website_url' => $officialWebsite,
            'url' => $urlValidation['normalized_url'],
            'label' => ! empty($validated['label']) ? $validated['label'] : ($link->label ?: $validated['custom_platform_name']),
            'alt_text' => ! empty($validated['alt_text']) ? $validated['alt_text'] : ($link->alt_text ?: $validated['custom_platform_name']),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $link->is_active,
            'sort_order' => $sortOrder,
        ], $logoUpdates));

        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))
            ->with('success', "{$validated['custom_platform_name']} link updated successfully!");
    }

    /**
     * Toggle active/inactive status of a social link.
     */
    public function toggle(Request $request, int $id): RedirectResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $link = $store->socialLinks()->findOrFail($id);

        $link->update([
            'is_active' => ! $link->is_active,
        ]);

        $statusText = $link->is_active ? 'activated' : 'deactivated';

        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))
            ->with('success', "{$link->platform_name} link {$statusText} successfully.");
    }

    /**
     * Delete an existing social link.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $link = $store->socialLinks()->findOrFail($id);
        $platformName = $link->platform_name;

        // Clean up logo files if custom
        if ($link->isCustom()) {
            $this->logoProcessingService->deleteOldLogoFiles($link);
        }

        $link->delete();

        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))
            ->with('success', "{$platformName} link deleted successfully.");
    }

    /**
     * Reorder social links within the authenticated store.
     */
    public function reorder(Request $request): RedirectResponse
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        DB::transaction(function () use ($store, $request): void {
            $order = $request->input('order');
            foreach ($order as $key => $val) {
                if ($store->socialLinks()->where('id', $key)->exists()) {
                    $store->socialLinks()->where('id', $key)->update(['sort_order' => (int) $val]);
                } else {
                    $store->socialLinks()->where('id', $val)->update(['sort_order' => (int) $key + 1]);
                }
            }
        });

        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))
            ->with('success', 'Display order updated successfully.');
    }

    /**
     * Build parameters to preserve Shopify embedded launch context during redirects.
     *
     * @return array<string, string>
     */
    protected function redirectParams(Request $request): array
    {
        $params = [];
        $host = $request->query('host') ?? $request->input('host');
        if ($host) {
            $params['host'] = $host;
            $shop = $request->query('shop') ?? $request->input('shop');
            if ($shop) {
                $params['shop'] = $shop;
            }
        }

        return $params;
    }

    /**
     * Resolve and strictly verify the authenticated Shopify store.
     */
    protected function resolveStore(Request $request): ShopifyStore
    {
        /** @var ShopifyStore|null $store */
        $store = $request->attributes->get('shopify_store');

        if (! $store || ! $store->isInstalled()) {
            $sessionShop = session('shopify_shop');
            $sessionStoreId = session('shopify_store_id');

            if ($sessionShop && $sessionStoreId) {
                $store = ShopifyStore::where('shop', $sessionShop)
                    ->where('id', $sessionStoreId)
                    ->where('is_active', true)
                    ->whereNotNull('access_token')
                    ->first();
            }

            if (! $store || ! $store->isInstalled()) {
                throw new HttpResponseException(
                    redirect()->route('shopify.install')->with('error', 'Authentication required.')
                );
            }
        }

        return $store;
    }
}
