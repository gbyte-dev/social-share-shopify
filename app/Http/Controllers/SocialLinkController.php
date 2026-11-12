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

    public function index(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $settings = $store->getStorefrontSettings();

        $links = $store->socialLinks()->orderBy('sort_order', 'asc')->get();
        $configuredBuiltInPlatforms = $links->where('platform', '!=', 'other')->pluck('platform')->toArray();

        // Track configured custom apps for this store to disable them in the modal picker
        $configuredCustomNames = $links->where('platform', 'other')->pluck('custom_platform_name')->map(fn($n) => strtolower(trim($n)))->toArray();

        $allPlatforms = SocialPlatformService::all();
        $availablePlatforms = array_diff_key($allPlatforms, array_flip($configuredBuiltInPlatforms));
        $nextOrder = (int) $store->socialLinks()->max('sort_order') + 1;

        // Fetch all globally public custom apps created by any merchant
        $globalCustomApps = DB::table('global_custom_platforms')->orderBy('name', 'asc')->get();

        return view('shopify.social-links.index', [
            'store' => $store,
            'settings' => $settings,
            'activeLinks' => $links->where('is_active', true)->values(),
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'links' => $links,
            'allPlatforms' => $allPlatforms,
            'availablePlatforms' => $availablePlatforms,
            'nextOrder' => $nextOrder,
            'globalCustomApps' => $globalCustomApps,
            'configuredCustomNames' => $configuredCustomNames,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('shopify.social-links.index', $this->redirectParams($request));
    }

    public function discoverLogos(Request $request): JsonResponse
    {
        $this->resolveStore($request);
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'string', 'max:1000'],
        ]);
        $result = $this->logoDiscoveryService->discover($validated['name'] ?? null, $validated['url'] ?? null, $validated['website_url'] ?? null);
        return response()->json($result);
    }

    public function store(Request $request)
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $platform = strtolower(trim((string) $request->input('platform')));

        if ($platform === 'other') {
            return $this->storeCustomLink($request, $store);
        }
        return $this->storeBuiltInLink($request, $store, $platform);
    }

    protected function storeBuiltInLink(Request $request, ShopifyStore $store, string $platform)
    {
        $allowedPlatforms = SocialPlatformService::builtInKeys();

        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in($allowedPlatforms), Rule::unique('social_links')->where(fn($q) => $q->where('shopify_store_id', $store->id))],
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
            return $this->respondError($request, ['url' => $urlValidation['error']]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== '' ? (int) $validated['sort_order'] : ((int) $store->socialLinks()->max('sort_order') + 1);

        $store->socialLinks()->create([
            'platform' => strtolower($validated['platform']),
            'platform_type' => 'builtin',
            'url' => $urlValidation['normalized_url'],
            'label' => $validated['label'] ?: SocialPlatformService::name($validated['platform']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $sortOrder,
        ]);

        return $this->respondSuccess($request, 'Social link added successfully!');
    }

    protected function storeCustomLink(Request $request, ShopifyStore $store)
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in(['other'])],
            'custom_platform_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('social_links', 'custom_platform_name')->where(fn($q) => $q->where('shopify_store_id', $store->id)),
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
            'custom_platform_name.unique' => 'You already have this custom app configured in your channels. Please edit the existing link instead.',
        ]);

        $urlValidation = SocialPlatformService::validatePlatformUrl('other', $validated['url']);
        if (! $urlValidation['is_valid']) {
            return $this->respondError($request, ['url' => $urlValidation['error']]);
        }

        $officialWebsite = null;
        if (! empty($validated['official_website_url'])) {
            $webValidation = SocialPlatformService::validatePlatformUrl('other', $validated['official_website_url']);
            if (! $webValidation['is_valid']) return $this->respondError($request, ['official_website_url' => $webValidation['error']]);
            $officialWebsite = $webValidation['normalized_url'];
        }

        $logoData = ['original_logo_path' => null, 'logo_small_path' => null, 'logo_medium_path' => null, 'logo_large_path' => null];
        $logoSourceUrl = null;
        $logoSourceDomain = null;

        try {
            if ($request->hasFile('logo_file')) {
                $logoData = $this->logoProcessingService->processUploadedFile($request->file('logo_file'), $store->id);
            } elseif ($request->filled('selected_logo_url')) {

                $selectedUrl = $request->input('selected_logo_url');

                $binary = $this->logoDiscoveryService->downloadRemoteImage($selectedUrl);

                if (! $binary) {
                    return $this->respondError($request, [
                        'logo_file' => 'Failed to download selected logo.'
                    ]);
                }

                $logoData = $this->logoProcessingService->processBinaryData(
                    $binary,
                    $store->id,
                    'discovered_logo'
                );

                $logoSourceUrl = $selectedUrl;
                $logoSourceDomain = parse_url($selectedUrl, PHP_URL_HOST);
            } else {
                return $this->respondError($request, ['logo_file' => 'A logo is required for custom platforms.']);
            }
        } catch (\Throwable $e) {
            return $this->respondError($request, ['logo_file' => $e->getMessage()]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== '' ? (int) $validated['sort_order'] : ((int) $store->socialLinks()->max('sort_order') + 1);

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

        // Global custom platforms table mein URL check karke save karein taaki asset() ka panga na ho
        $smallPath = $logoData['logo_small_path'];
        $publicLogoUrl = (str_starts_with($smallPath, 'http://') || str_starts_with($smallPath, 'https://'))
            ? $smallPath
            : asset('public/' . $smallPath);

        DB::table('global_custom_platforms')->updateOrInsert(
            ['name' => $validated['custom_platform_name']],
            [
                'website_url' => $officialWebsite,
                'logo_url' => $publicLogoUrl,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $this->respondSuccess($request, "{$validated['custom_platform_name']} link added successfully!");
    }


    public function storeGlobalCustomApp(Request $request)
    {
        $this->resolveStore($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:global_custom_platforms,name'],
            'website_url' => ['nullable', 'string', 'max:1000'],
            'logo_file' => ['nullable', 'file', 'image', 'max:5120'],
            'selected_logo_url' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!$request->hasFile('logo_file') && !$request->filled('selected_logo_url')) {
            return response()->json([
                'success' => false,
                'message' => 'A logo is required.',
            ], 422);
        }

        try {
            if ($request->hasFile('logo_file')) {
                $logoData = $this->logoProcessingService->processUploadedFile(
                    $request->file('logo_file'),
                    $request->attributes->get('shopify_store')->id
                );
            } else {
                $selectedUrl = $validated['selected_logo_url'];
                $binary = $this->logoDiscoveryService->downloadRemoteImage($selectedUrl);

                if (!$binary) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to download selected logo.',
                    ], 422);
                }

                $logoData = $this->logoProcessingService->processBinaryData(
                    $binary,
                    $request->attributes->get('shopify_store')->id,
                    'discovered_logo'
                );
            }

            $smallPath = $logoData['logo_small_path'];

            $logoUrl = str_starts_with($smallPath, 'http://') ||
                str_starts_with($smallPath, 'https://')
                ? $smallPath
                : asset('public/' . $smallPath);

            DB::table('global_custom_platforms')->insert([
                'name' => $validated['name'],
                'website_url' => $validated['website_url'] ?? null,
                'logo_url' => $logoUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'app' => [
                    'name' => $validated['name'],
                    'website' => $validated['website_url'] ?? null,
                    'logo' => $logoUrl,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Global custom app save failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save custom app.',
            ], 500);
        }
    }


    public function edit(Request $request, int $id): RedirectResponse
    {
        return redirect()->route('shopify.social-links.index', $this->redirectParams($request));
    }

    public function update(Request $request, int $id)
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $link = $store->socialLinks()->findOrFail($id);

        if ($link->isCustom()) return $this->updateCustomLink($request, $link);
        return $this->updateBuiltInLink($request, $store, $link);
    }

    protected function updateBuiltInLink(Request $request, ShopifyStore $store, SocialLink $link)
    {
        $allowedPlatforms = SocialPlatformService::builtInKeys();

        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in($allowedPlatforms), Rule::unique('social_links')->where(fn($q) => $q->where('shopify_store_id', $store->id))->ignore($link->id)],
            'url' => ['required', 'string', 'max:1000'],
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $urlValidation = SocialPlatformService::validatePlatformUrl($validated['platform'], $validated['url']);
        if (! $urlValidation['is_valid']) return $this->respondError($request, ['url' => $urlValidation['error']]);

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== '' ? (int) $validated['sort_order'] : $link->sort_order;

        $link->update([
            'platform' => strtolower($validated['platform']),
            'url' => $urlValidation['normalized_url'],
            'label' => $validated['label'] ?: SocialPlatformService::name($validated['platform']),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $sortOrder,
        ]);

        return $this->respondSuccess($request, 'Social link updated successfully!');
    }

    protected function updateCustomLink(Request $request, SocialLink $link)
    {
        $validated = $request->validate([
            'custom_platform_name' => ['required', 'string', 'max:100'],
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
        if (! $urlValidation['is_valid']) return $this->respondError($request, ['url' => $urlValidation['error']]);

        $officialWebsite = null;
        if (! empty($validated['official_website_url'])) {
            $webValidation = SocialPlatformService::validatePlatformUrl('other', $validated['official_website_url']);
            if (! $webValidation['is_valid']) return $this->respondError($request, ['official_website_url' => $webValidation['error']]);
            $officialWebsite = $webValidation['normalized_url'];
        }

        $logoUpdates = [];

        try {
            if ($request->hasFile('logo_file')) {
                $newLogoData = $this->logoProcessingService->processUploadedFile($request->file('logo_file'), $link->shopify_store_id);
                $this->logoProcessingService->deleteOldLogoFiles($link);
                $logoUpdates = array_merge($newLogoData, ['logo_source_url' => null, 'logo_source_domain' => null]);
            } elseif ($request->filled('selected_logo_url')) {
                $selectedUrl = $request->input('selected_logo_url');
                $binary = $this->logoDiscoveryService->downloadRemoteImage($selectedUrl);
                if (! $binary) return $this->respondError($request, ['logo_file' => 'Failed to download selected logo.']);
                $newLogoData = $this->logoProcessingService->processBinaryData($binary, $link->shopify_store_id, 'discovered_logo');
                $this->logoProcessingService->deleteOldLogoFiles($link);
                $logoUpdates = array_merge($newLogoData, ['logo_source_url' => $selectedUrl, 'logo_source_domain' => parse_url($selectedUrl, PHP_URL_HOST)]);
            }
        } catch (\Throwable $e) {
            return $this->respondError($request, ['logo_file' => $e->getMessage()]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== '' ? (int) $validated['sort_order'] : $link->sort_order;

        $link->update(array_merge([
            'custom_platform_name' => $validated['custom_platform_name'],
            'official_website_url' => $officialWebsite,
            'url' => $urlValidation['normalized_url'],
            'label' => ! empty($validated['label']) ? $validated['label'] : ($link->label ?: $validated['custom_platform_name']),
            'alt_text' => ! empty($validated['alt_text']) ? $validated['alt_text'] : ($link->alt_text ?: $validated['custom_platform_name']),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $sortOrder,
        ], $logoUpdates));

        return $this->respondSuccess($request, "{$validated['custom_platform_name']} link updated successfully!");
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $store = $this->resolveStore($request);
        $link = $store->socialLinks()->findOrFail($id);
        $link->update(['is_active' => ! $link->is_active]);
        $statusText = $link->is_active ? 'activated' : 'deactivated';
        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))->with('success', "{$link->platform_name} link {$statusText} successfully.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $store = $this->resolveStore($request);
        $link = $store->socialLinks()->findOrFail($id);
        $platformName = $link->platform_name;
        if ($link->isCustom()) $this->logoProcessingService->deleteOldLogoFiles($link);
        $link->delete();
        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))->with('success', "{$platformName} link deleted successfully.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $store = $this->resolveStore($request);
        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);
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
        return redirect()->route('shopify.social-links.index', $this->redirectParams($request))->with('success', 'Display order updated successfully.');
    }

    protected function respondError(Request $request, array $errors)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $formatted = [];
            foreach ($errors as $key => $msg) $formatted[$key] = [$msg];
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $formatted], 422);
        }
        return back()->withInput()->withErrors($errors);
    }

    protected function respondSuccess(Request $request, string $message)
    {
        session()->flash('success', $message);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'redirect' => route('shopify.social-links.index', $this->redirectParams($request))]);
        }
        return redirect()->route('shopify.social-links.index', $this->redirectParams($request));
    }

    protected function redirectParams(Request $request): array
    {
        $params = [];
        $host = $request->query('host') ?? $request->input('host');
        if ($host) {
            $params['host'] = $host;
            $shop = $request->query('shop') ?? $request->input('shop');
            if ($shop) $params['shop'] = $shop;
        }
        return $params;
    }

    protected function resolveStore(Request $request): ShopifyStore
    {
        $store = $request->attributes->get('shopify_store');
        if (! $store || ! $store->isInstalled()) {
            $sessionShop = session('shopify_shop');
            $sessionStoreId = session('shopify_store_id');
            if ($sessionShop && $sessionStoreId) {
                $store = ShopifyStore::where('shop', $sessionShop)->where('id', $sessionStoreId)->where('is_active', true)->whereNotNull('access_token')->first();
            }
            if (! $store || ! $store->isInstalled()) {
                throw new HttpResponseException(redirect()->route('shopify.install')->with('error', 'Authentication required.'));
            }
        }
        return $store;
    }

    public function activateStorefrontEmbed(Request $request): JsonResponse
    {
        $store = $this->resolveStore($request);

        try {
            $shopifyService = app(\App\Services\ShopifyService::class);

            $activationUrl = $shopifyService->getSocialNestEmbedActivationUrl(
                $store->shop
            );

            $store->getStorefrontSettings()->update([
                'is_enabled' => true,
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => $activationUrl,
            ]);
        } catch (\Throwable $e) {
            \Log::error('SocialNest embed activation failed', [
                'shop' => $store->shop,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to open Shopify Theme Editor.',
            ], 500);
        }
    }
}
