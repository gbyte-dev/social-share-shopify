<?php

namespace App\Http\Controllers;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Services\EntitlementService;
use App\Services\LogoDiscoveryService;
use App\Services\LogoProcessingService;
use App\Services\ShopifyService;
use App\Services\SocialPlatformService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SocialLinkController extends Controller
{
    public function __construct(
        protected LogoProcessingService $logoProcessingService,
        protected LogoDiscoveryService $logoDiscoveryService,
        protected EntitlementService $entitlementService
    ) {}

    public function index(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $settings = $store->getStorefrontSettings();

        $links = $store->socialLinks()->orderBy('sort_order', 'asc')->get();
        $configuredBuiltInPlatforms = $links->where('platform', '!=', 'other')->pluck('platform')->toArray();

        // Track configured custom apps for this store to disable them in the modal picker
        $configuredCustomNames = $links->where('platform', 'other')->pluck('custom_platform_name')->map(fn ($n) => strtolower(trim($n)))->toArray();

        $allPlatforms = SocialPlatformService::all();
        $availablePlatforms = array_diff_key($allPlatforms, array_flip($configuredBuiltInPlatforms));
        $nextOrder = (int) $store->socialLinks()->max('sort_order') + 1;

        // Fetch all globally public custom apps created by any merchant
        $globalCustomApps = DB::table('global_custom_platforms')->orderBy('name', 'asc')->get();

        $creationStatus = $this->entitlementService->getSocialLinkCreationStatus($store);
        $effectivePlan = $this->entitlementService->getEffectivePlan($store);

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
            'creationStatus' => $creationStatus,
            'effectivePlan' => $effectivePlan,
            'isPro' => $creationStatus['is_pro'],
        ]);
    }

    public function create(Request $request): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $configuredBuiltInPlatforms = $store->socialLinks()->where('platform', '!=', 'other')->pluck('platform')->toArray();
        $allPlatforms = SocialPlatformService::all();
        $availablePlatforms = array_diff_key($allPlatforms, array_flip($configuredBuiltInPlatforms));

        $maxOrder = (int) $store->socialLinks()->max('sort_order');
        $nextOrder = $maxOrder + 1;

        $creationStatus = $this->entitlementService->getSocialLinkCreationStatus($store);
        $effectivePlan = $this->entitlementService->getEffectivePlan($store);

        return view('shopify.social-links.create', [
            'store' => $store,
            'shop' => $store->shop,
            'host' => session('shopify_host') ?? $request->query('host'),
            'allPlatforms' => $allPlatforms,
            'availablePlatforms' => $availablePlatforms,
            'nextOrder' => $nextOrder,
            'creationStatus' => $creationStatus,
            'effectivePlan' => $effectivePlan,
            'isPro' => $creationStatus['is_pro'],
        ]);
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

        $status = $this->entitlementService->getSocialLinkCreationStatus($store);
        if (! $status['can_create']) {
            return $this->respondError($request, [
                'limit' => $status['message'] ?? 'Social link limit reached for your current plan.',
            ]);
        }

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
            'platform' => ['required', 'string', Rule::in($allowedPlatforms), Rule::unique('social_links')->where(fn ($q) => $q->where('shopify_store_id', $store->id))],
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

        return DB::transaction(function () use ($request, $store, $validated, $urlValidation) {
            $status = $this->entitlementService->getSocialLinkCreationStatus($store);
            if (! $status['can_create']) {
                return $this->respondError($request, [
                    'limit' => $status['message'] ?? 'Social link limit reached for your current plan.',
                ]);
            }

            $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== '' ? (int) $validated['sort_order'] : ((int) $store->socialLinks()->max('sort_order') + 1);

            $store->socialLinks()->create([
                'platform' => strtolower($validated['platform']),
                'platform_type' => 'builtin',
                'url' => $urlValidation['normalized_url'],
                'label' => ! empty($validated['label']) ? $validated['label'] : SocialPlatformService::name($validated['platform']),
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $sortOrder,
            ]);

            return $this->respondSuccess($request, 'Social link added successfully!');
        });
    }

    protected function storeCustomLink(Request $request, ShopifyStore $store)
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in(['other'])],
            'custom_platform_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('social_links', 'custom_platform_name')->where(fn ($q) => $q->where('shopify_store_id', $store->id)),
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
            if (! $webValidation['is_valid']) {
                return $this->respondError($request, ['official_website_url' => $webValidation['error']]);
            }
            $officialWebsite = $webValidation['normalized_url'];
        }

        try {
            $resolved = $this->resolveCustomLogoData($request, $store, $validated['custom_platform_name'], $validated['selected_logo_url'] ?? null);
            $logoData = $resolved['logoData'];
            $logoSourceUrl = $resolved['sourceUrl'];
            $logoSourceDomain = $resolved['sourceDomain'];
        } catch (\Throwable $e) {
            return $this->respondError($request, ['logo_file' => $e->getMessage()]);
        }

        return DB::transaction(function () use ($request, $store, $validated, $urlValidation, $officialWebsite, $logoData, $logoSourceUrl, $logoSourceDomain) {
            $status = $this->entitlementService->getSocialLinkCreationStatus($store);
            if (! $status['can_create']) {
                return $this->respondError($request, [
                    'limit' => $status['message'] ?? 'Social link limit reached for your current plan.',
                ]);
            }

            $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== '' ? (int) $validated['sort_order'] : ((int) $store->socialLinks()->max('sort_order') + 1);

            $store->socialLinks()->create([
                'platform' => 'other',
                'platform_type' => 'custom',
                'custom_platform_name' => $validated['custom_platform_name'],
                'official_website_url' => $officialWebsite,
                'original_logo_path' => $logoData['original_logo_path'] ?? null,
                'logo_small_path' => $logoData['logo_small_path'] ?? null,
                'logo_medium_path' => $logoData['logo_medium_path'] ?? null,
                'logo_large_path' => $logoData['logo_large_path'] ?? null,
                'logo_source_url' => $logoSourceUrl,
                'logo_source_domain' => $logoSourceDomain,
                'url' => $urlValidation['normalized_url'],
                'label' => ! empty($validated['label']) ? $validated['label'] : $validated['custom_platform_name'],
                'alt_text' => ! empty($validated['alt_text']) ? $validated['alt_text'] : $validated['custom_platform_name'],
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $sortOrder,
            ]);

            $smallPath = $logoData['logo_small_path'] ?? null;
            if ($smallPath) {
                $cleanPath = ltrim(preg_replace('#^/?public/#', '', $smallPath), '/');
                if (str_starts_with($cleanPath, 'logos/')) {
                    $publicLogoUrl = Storage::disk('public')->url($cleanPath);
                } else {
                    $publicLogoUrl = (str_starts_with($cleanPath, 'http://') || str_starts_with($cleanPath, 'https://') || str_starts_with($cleanPath, 'data:image'))
                        ? preg_replace('#^(https?://[^/]+)/public/uploads/#', '$1/uploads/', $cleanPath)
                        : asset($cleanPath);
                }

                DB::table('global_custom_platforms')->updateOrInsert(
                    ['name' => $validated['custom_platform_name']],
                    [
                        'website_url' => $officialWebsite,
                        'logo_url' => $publicLogoUrl,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            return $this->respondSuccess($request, "{$validated['custom_platform_name']} link added successfully!");
        });
    }

    public function storeGlobalCustomApp(Request $request)
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'website_url' => ['nullable', 'string', 'max:1000'],
            'logo_file' => ['nullable', 'file', 'max:5120'],
            'selected_logo_url' => ['nullable', 'string', 'max:1000'],
        ]);

        // If app with this name already exists in registry, reuse it
        $existing = DB::table('global_custom_platforms')
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($validated['name']))])
            ->first();

        if ($existing) {
            $logoUrl = preg_replace('#^(https?://[^/]+)/public/uploads/#', '$1/uploads/', $existing->logo_url);

            return response()->json([
                'success' => true,
                'app' => [
                    'name' => $existing->name,
                    'website' => $existing->website_url,
                    'logo' => $logoUrl,
                ],
            ]);
        }

        try {
            $resolved = $this->resolveCustomLogoData($request, $store, $validated['name'], $validated['selected_logo_url'] ?? null);
            $logoData = $resolved['logoData'];
            $smallPath = $logoData['logo_small_path'] ?? null;

            $cleanPath = ltrim(preg_replace('#^/?public/#', '', (string) $smallPath), '/');
            if (str_starts_with($cleanPath, 'logos/')) {
                $publicLogoUrl = Storage::disk('public')->url($cleanPath);
            } else {
                $publicLogoUrl = (str_starts_with($cleanPath, 'http://') || str_starts_with($cleanPath, 'https://') || str_starts_with($cleanPath, 'data:image'))
                    ? preg_replace('#^(https?://[^/]+)/public/uploads/#', '$1/uploads/', $cleanPath)
                    : asset($cleanPath);
            }

            DB::table('global_custom_platforms')->insert([
                'name' => $validated['name'],
                'website_url' => $validated['website_url'] ?? null,
                'logo_url' => $publicLogoUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'app' => [
                    'name' => $validated['name'],
                    'website' => $validated['website_url'] ?? null,
                    'logo' => $publicLogoUrl,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Global custom app save failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to save custom app.',
            ], 422);
        }
    }

    /**
     * Resolve and process logo data for custom platform links or global custom apps.
     *
     * @return array{logoData: array{original_logo_path: string, logo_small_path: string, logo_medium_path: string, logo_large_path: string}, sourceUrl: ?string, sourceDomain: ?string}
     */
    protected function resolveCustomLogoData(Request $request, ShopifyStore $store, string $customPlatformName, ?string $selectedUrl = null): array
    {
        // 1. Direct file upload
        if ($request->hasFile('logo_file')) {
            $logoData = $this->logoProcessingService->processUploadedFile($request->file('logo_file'), $store->id);

            return ['logoData' => $logoData, 'sourceUrl' => null, 'sourceDomain' => null];
        }

        $selectedUrl = trim((string) ($selectedUrl ?? $request->input('selected_logo_url')));

        // 2. Data URI
        if (! empty($selectedUrl) && str_starts_with($selectedUrl, 'data:image')) {
            $commaPos = strpos($selectedUrl, ',');
            if ($commaPos !== false) {
                $binary = base64_decode(substr($selectedUrl, $commaPos + 1));
                if ($binary) {
                    $logoData = $this->logoProcessingService->processBinaryData($binary, $store->id, 'custom_logo');

                    return ['logoData' => $logoData, 'sourceUrl' => null, 'sourceDomain' => null];
                }
            }
        }

        // 3. Local file candidate (relative path or app URL pointing to uploads/ or storage/)
        if (! empty($selectedUrl)) {
            $parsedPath = parse_url($selectedUrl, PHP_URL_PATH) ?: $selectedUrl;
            $cleanCandidate = ltrim($parsedPath, '/');
            if (str_starts_with($cleanCandidate, 'public/')) {
                $cleanCandidate = substr($cleanCandidate, 7);
            }

            // Check if file exists on disk in public_path or storage disk
            if (file_exists(public_path($cleanCandidate))) {
                $binary = file_get_contents(public_path($cleanCandidate));
                if ($binary) {
                    $logoData = $this->logoProcessingService->processBinaryData($binary, $store->id, 'custom_logo');

                    return ['logoData' => $logoData, 'sourceUrl' => $selectedUrl, 'sourceDomain' => parse_url($selectedUrl, PHP_URL_HOST)];
                }
            }

            if (Storage::disk('public')->exists($cleanCandidate)) {
                $binary = Storage::disk('public')->get($cleanCandidate);
                if ($binary) {
                    $logoData = $this->logoProcessingService->processBinaryData($binary, $store->id, 'custom_logo');

                    return ['logoData' => $logoData, 'sourceUrl' => $selectedUrl, 'sourceDomain' => parse_url($selectedUrl, PHP_URL_HOST)];
                }
            }

            // 4. Remote HTTP/HTTPS URL download
            if (str_starts_with($selectedUrl, 'http://') || str_starts_with($selectedUrl, 'https://')) {
                $binary = $this->logoDiscoveryService->downloadRemoteImage($selectedUrl);
                if ($binary) {
                    $logoData = $this->logoProcessingService->processBinaryData($binary, $store->id, 'discovered_logo');

                    return ['logoData' => $logoData, 'sourceUrl' => $selectedUrl, 'sourceDomain' => parse_url($selectedUrl, PHP_URL_HOST)];
                }
            }
        }

        // 5. Check if platform is already registered in global_custom_platforms
        $existingGlobal = DB::table('global_custom_platforms')
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($customPlatformName))])
            ->first();

        if ($existingGlobal && ! empty($existingGlobal->logo_url)) {
            $parsedPath = parse_url($existingGlobal->logo_url, PHP_URL_PATH) ?: $existingGlobal->logo_url;
            $cleanCandidate = ltrim($parsedPath, '/');
            if (str_starts_with($cleanCandidate, 'public/')) {
                $cleanCandidate = substr($cleanCandidate, 7);
            }
            if (file_exists(public_path($cleanCandidate))) {
                $binary = file_get_contents(public_path($cleanCandidate));
                if ($binary) {
                    $logoData = $this->logoProcessingService->processBinaryData($binary, $store->id, 'custom_logo');

                    return ['logoData' => $logoData, 'sourceUrl' => $existingGlobal->logo_url, 'sourceDomain' => parse_url($existingGlobal->logo_url, PHP_URL_HOST)];
                }
            }
            if (Storage::disk('public')->exists($cleanCandidate)) {
                $binary = Storage::disk('public')->get($cleanCandidate);
                if ($binary) {
                    $logoData = $this->logoProcessingService->processBinaryData($binary, $store->id, 'custom_logo');

                    return ['logoData' => $logoData, 'sourceUrl' => $existingGlobal->logo_url, 'sourceDomain' => parse_url($existingGlobal->logo_url, PHP_URL_HOST)];
                }
            }
        }

        // 6. Graceful Fallback: Generate clean default custom SVG logo
        $logoData = $this->logoProcessingService->createDefaultSvgLogo($store->id, $customPlatformName);

        return ['logoData' => $logoData, 'sourceUrl' => null, 'sourceDomain' => null];
    }

    public function edit(Request $request, int $id): View
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);

        $link = $store->socialLinks()->findOrFail($id);
        $configuredBuiltInPlatforms = $store->socialLinks()
            ->where('platform', '!=', 'other')
            ->where('id', '!=', $link->id)
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

    public function update(Request $request, int $id)
    {
        /** @var ShopifyStore $store */
        $store = $this->resolveStore($request);
        $link = $store->socialLinks()->findOrFail($id);

        if ($link->isCustom()) {
            return $this->updateCustomLink($request, $link);
        }

        return $this->updateBuiltInLink($request, $store, $link);
    }

    protected function updateBuiltInLink(Request $request, ShopifyStore $store, SocialLink $link)
    {
        $allowedPlatforms = SocialPlatformService::builtInKeys();

        $validated = $request->validate([
            'platform' => ['required', 'string', Rule::in($allowedPlatforms), Rule::unique('social_links')->where(fn ($q) => $q->where('shopify_store_id', $store->id))->ignore($link->id)],
            'url' => ['required', 'string', 'max:1000'],
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $urlValidation = SocialPlatformService::validatePlatformUrl($validated['platform'], $validated['url']);
        if (! $urlValidation['is_valid']) {
            return $this->respondError($request, ['url' => $urlValidation['error']]);
        }

        $sortOrder = isset($validated['sort_order']) && $validated['sort_order'] !== '' ? (int) $validated['sort_order'] : $link->sort_order;

        $link->update([
            'platform' => strtolower($validated['platform']),
            'url' => $urlValidation['normalized_url'],
            'label' => ! empty($validated['label']) ? $validated['label'] : SocialPlatformService::name($validated['platform']),
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
        if (! $urlValidation['is_valid']) {
            return $this->respondError($request, ['url' => $urlValidation['error']]);
        }

        $officialWebsite = null;
        if (! empty($validated['official_website_url'])) {
            $webValidation = SocialPlatformService::validatePlatformUrl('other', $validated['official_website_url']);
            if (! $webValidation['is_valid']) {
                return $this->respondError($request, ['official_website_url' => $webValidation['error']]);
            }
            $officialWebsite = $webValidation['normalized_url'];
        }

        $logoUpdates = [];

        try {
            if ($request->hasFile('logo_file') || $request->filled('selected_logo_url')) {
                $store = $link->store ?: ShopifyStore::findOrFail($link->shopify_store_id);
                $resolved = $this->resolveCustomLogoData($request, $store, $validated['custom_platform_name'], $validated['selected_logo_url'] ?? null);
                $this->logoProcessingService->deleteOldLogoFiles($link);
                $logoUpdates = array_merge($resolved['logoData'], [
                    'logo_source_url' => $resolved['sourceUrl'],
                    'logo_source_domain' => $resolved['sourceDomain'],
                ]);
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
        if ($link->isCustom()) {
            $this->logoProcessingService->deleteOldLogoFiles($link);
        }
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
            foreach ($errors as $key => $msg) {
                $formatted[$key] = [$msg];
            }

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
            if ($shop) {
                $params['shop'] = $shop;
            }
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
            $shopifyService = app(ShopifyService::class);

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
