@extends('layouts.shopify')

@section('title', 'Edit ' . $link->platform_name . ' Link - ' . $shop)
@section('header_title', 'Edit Social Link')
@section('header_subtitle', 'Update channel details, URL, or visibility for ' . $link->platform_name)

@section('content')
    <!-- Scoped Polaris-inspired Styles for 2-Column Full-Cover Edit Social Link -->
    <style>
        .top-actions .badge-live {
            display: none !important;
        }

        .sn-layout-wrap {
            width: 100%;
            max-width: 1080px;
            margin: 0 auto;
        }

        .sn-card {
            background: #ffffff;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
            margin-bottom: 0.875rem;
        }

        .sn-card-header {
            margin-bottom: 0.875rem;
            padding-bottom: 0.625rem;
            border-bottom: 1px solid #f1f2f4;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .sn-card-header-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sn-platform-icon-wrap {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #ffffff;
            flex-shrink: 0;
        }

        .sn-platform-icon-wrap svg {
            width: 14px;
            height: 14px;
        }

        .sn-card-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #202223;
            margin: 0;
        }

        .sn-card-subtitle {
            font-size: 0.72rem;
            color: #6d7175;
            margin: 0.15rem 0 0 0;
        }

        /* 2-Column Balanced Form Grid */
        .sn-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            align-items: start;
        }

        .sn-field-group {
            margin-bottom: 0.875rem;
        }

        .sn-field-group:last-child {
            margin-bottom: 0;
        }

        .sn-subgrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            align-items: start;
        }

        .sn-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #202223;
            margin-bottom: 0.25rem;
        }

        .sn-label-opt {
            font-weight: 400;
            color: #6d7175;
        }

        .sn-input, .sn-select {
            width: 100%;
            padding: 0.45rem 0.65rem;
            font-size: 0.8125rem;
            border: 1px solid #c9cccf;
            border-radius: 6px;
            background: #ffffff;
            color: #202223;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-sizing: border-box;
        }

        .sn-input:focus, .sn-select:focus {
            border-color: var(--primary, #4f46e5);
            box-shadow: 0 0 0 1px var(--primary, #4f46e5);
        }

        .sn-help-text {
            font-size: 0.6875rem;
            color: #6d7175;
            margin-top: 0.2rem;
            line-height: 1.35;
        }

        .sn-field-error {
            font-size: 0.6875rem;
            color: #d72c0d;
            margin-top: 0.2rem;
            font-weight: 500;
        }

        .sn-toggle-panel {
            padding: 0.55rem 0.75rem;
            background: #fafbfb;
            border: 1px solid #e1e3e5;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            margin-top: 1.25rem;
        }

        .sn-custom-box {
            margin-top: 1rem;
            padding: 0.875rem 1rem;
            background: #fafbfb;
            border: 1px solid #e1e3e5;
            border-radius: 6px;
        }

        .sn-custom-box-head {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
        }

        .sn-custom-box-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #202223;
            margin: 0;
        }

        /* Live Preview Strip */
        .sn-preview-container {
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            background: #fafbfb;
            border: 1px solid #e1e3e5;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .sn-preview-dock-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            background: #ffffff;
            border-radius: 9999px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            border: 1px solid #e1e3e5;
        }

        .sn-preview-icon-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            overflow: hidden;
            flex-shrink: 0;
        }

        .sn-preview-icon-circle svg {
            width: 14px;
            height: 14px;
        }

        .sn-preview-label-text {
            font-size: 0.75rem;
            font-weight: 600;
            color: #202223;
            white-space: nowrap;
        }

        .sn-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.42rem 0.95rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            line-height: 1.3;
        }

        .sn-btn-cancel {
            background: #ffffff;
            border: 1px solid #dcdfe3;
            color: #202223;
        }

        .sn-btn-cancel:hover {
            background: #f6f6f7;
            border-color: #c9cccf;
        }

        .sn-btn-submit {
            background: var(--primary, #4f46e5);
            border: 1px solid transparent;
            color: #ffffff;
        }

        .sn-btn-submit:hover {
            background: #4338ca;
        }

        .sn-btn-delete {
            color: #e11d48;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
        }

        .sn-btn-delete:hover {
            background: #ffe4e6;
            border-color: #fda4af;
        }

        .candidate-card {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.5rem;
            border: 1px solid #e1e3e5;
            border-radius: 5px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 0.6875rem;
        }
        .candidate-card:hover {
            border-color: var(--primary, #4f46e5);
            background: #fafbfb;
        }
        .candidate-card.active {
            border-color: var(--primary, #4f46e5);
            background: #eef2ff;
            box-shadow: 0 0 0 1px var(--primary, #4f46e5);
        }
        .spinner-sm {
            width: 11px;
            height: 11px;
            border: 2px solid #e1e3e5;
            border-top-color: var(--primary, #4f46e5);
            border-radius: 50%;
            display: inline-block;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .sn-card-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            border-top: 1px solid #f1f2f4;
            padding-top: 0.875rem;
            margin-top: 1rem;
        }

        @media (max-width: 860px) {
            .sn-form-grid {
                grid-template-columns: 1fr;
            }
            .sn-subgrid {
                grid-template-columns: 1fr;
            }
            .sn-toggle-panel {
                margin-top: 0;
            }
        }
    </style>

    <div class="sn-layout-wrap">
        <div class="sn-card">
            <div class="sn-card-header">
                <div class="sn-card-header-info">
                    <div class="sn-platform-icon-wrap" style="background: {{ $link->brand_color }};">
                        {!! $link->icon_html !!}
                    </div>
                    <div>
                        <h3 class="sn-card-title">{{ $link->platform_name }}</h3>
                        <p class="sn-card-subtitle">Added {{ $link->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                @if($link->isCustom())
                    <span style="font-size: 0.625rem; font-weight: 600; background: #e0e7ff; color: #4338ca; padding: 1px 5px; border-radius: 3px;">Custom App</span>
                @endif
            </div>

            <form action="{{ route('shopify.social-links.update', $link->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- 2-Column Balanced Form Grid -->
                <div class="sn-form-grid">
                    <!-- Column 1: Platform & URL -->
                    <div>
                        @if(! $link->isCustom())
                            <!-- Built-in Platform Selection -->
                            <div class="sn-field-group">
                                <label for="platform" class="sn-label">
                                    Social Media Platform <span style="color: #d72c0d;">*</span>
                                </label>
                                <select id="platform" name="platform" required class="sn-select" onchange="updatePlaceholder(); updateLivePreview();">
                                    @foreach($allPlatforms as $key => $plat)
                                        @if($key !== 'other')
                                            @php
                                                $isCurrent = $link->platform === $key;
                                                $isConfigured = !isset($availablePlatforms[$key]) && !$isCurrent;
                                                $isSelected = old('platform', $link->platform) === $key;
                                            @endphp
                                            <option value="{{ $key }}" {{ $isSelected ? 'selected' : '' }} {{ $isConfigured ? 'disabled style=color:#94a3b8;' : '' }}>
                                                {{ $plat['name'] }} {{ $isConfigured ? '(Already Added)' : '' }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('platform')
                                    <div class="sn-field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <input type="hidden" name="platform" value="other" id="platform">
                        @endif

                        <!-- Destination URL -->
                        <div class="sn-field-group">
                            <label for="url" class="sn-label">
                                Destination or Profile URL <span style="color: #d72c0d;">*</span>
                            </label>
                            <input type="text" id="url" name="url" value="{{ old('url', $link->url) }}" required class="sn-input" oninput="updateLivePreview()">
                            <div id="urlHint" class="sn-help-text">
                                Enter your profile, channel, group, or custom destination link for this platform.
                            </div>
                            @error('url')
                                <div class="sn-field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Column 2: Display Label, Order & Storefront Visibility -->
                    <div>
                        <!-- Custom Display Label -->
                        <div class="sn-field-group">
                            <label for="label" class="sn-label">
                                Custom Display Label <span class="sn-label-opt">(Optional)</span>
                            </label>
                            <input type="text" id="label" name="label" value="{{ old('label', $link->label) }}" maxlength="100" class="sn-input" oninput="updateLivePreview()">
                            <div class="sn-help-text">
                                Tooltip text shown when customers hover over the icon.
                            </div>
                            @error('label')
                                <div class="sn-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subgrid: Display Sort Order + Enable on Storefront Toggle -->
                        <div class="sn-subgrid">
                            <!-- Display Order -->
                            <div class="sn-field-group">
                                <label for="sort_order" class="sn-label">
                                    Display Order
                                </label>
                                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $link->sort_order) }}" min="0" max="9999" class="sn-input">
                                <div class="sn-help-text">
                                    Lower numbers appear first.
                                </div>
                                @error('sort_order')
                                    <div class="sn-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Enable on Storefront Toggle Box -->
                            <div>
                                <label class="sn-toggle-panel">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $link->is_active ? '1' : '0') == '1' ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: var(--primary, #4f46e5); margin: 0;">
                                    <div>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: #202223; display: block;">Enable on Storefront</span>
                                        <span style="font-size: 0.65rem; color: #6d7175; display: block;">Show in floating dock.</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                @if($link->isCustom())
                    <!-- Custom Platform Settings Section -->
                    <div class="sn-custom-box">
                        <div class="sn-custom-box-head">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary, #4f46e5)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                            <h4 class="sn-custom-box-title">Custom App / Platform Details</h4>
                        </div>

                        <div class="sn-subgrid">
                            <!-- App Name -->
                            <div class="sn-field-group">
                                <label for="custom_platform_name" class="sn-label">
                                    App or Platform Name <span style="color: #d72c0d;">*</span>
                                </label>
                                <input type="text" id="custom_platform_name" name="custom_platform_name" value="{{ old('custom_platform_name', $link->custom_platform_name) }}" required maxlength="100" class="sn-input" oninput="updateLivePreview(); debouncedLogoDiscovery();">
                                @error('custom_platform_name')
                                    <div class="sn-field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Official Website URL -->
                            <div class="sn-field-group">
                                <label for="official_website_url" class="sn-label">
                                    Official Website URL <span class="sn-label-opt">(Aids logo find)</span>
                                </label>
                                <input type="text" id="official_website_url" name="official_website_url" value="{{ old('official_website_url', $link->official_website_url) }}" placeholder="e.g. https://discord.com" class="sn-input" oninput="debouncedLogoDiscovery();">
                                @error('official_website_url')
                                    <div class="sn-field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Alt Text -->
                        <div class="sn-field-group">
                            <label for="alt_text" class="sn-label">
                                Accessibility Alt Text
                            </label>
                            <input type="text" id="alt_text" name="alt_text" value="{{ old('alt_text', $link->alt_text) }}" placeholder="e.g. Join our community" maxlength="150" class="sn-input">
                            @error('alt_text')
                                <div class="sn-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Current Logo Preview Box -->
                        <div style="border-top: 1px dashed #e1e3e5; padding-top: 0.6rem; margin-top: 0.6rem;">
                            <label class="sn-label">
                                Current Platform Logo
                            </label>

                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.6rem; padding: 0.4rem 0.6rem; background: #ffffff; border: 1px solid #e1e3e5; border-radius: 6px;">
                                <div style="width: 32px; height: 32px; border-radius: 5px; background: #fafbfb; border: 1px solid #e1e3e5; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                    @if($link->logo_medium_url)
                                        <img src="{{ $link->logo_medium_url }}" alt="{{ $link->platform_name }}" style="width: 22px; height: 22px; object-fit: contain;">
                                    @else
                                        {!! $link->icon_svg !!}
                                    @endif
                                </div>
                                <div style="font-size: 0.72rem; flex: 1;">
                                    <div style="font-weight: 600; color: #202223;">Active Stored Logo</div>
                                    <div style="font-size: 0.65rem; color: #6d7175;">
                                        Variants: 36px, 44px, 56px.
                                        @if($link->logo_source_domain)
                                            Source: {{ $link->logo_source_domain }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Replace Logo Controls -->
                            <label class="sn-label">
                                Replace Logo <span class="sn-label-opt">(Optional - keep current logo if left blank)</span>
                            </label>

                            <div style="display: flex; gap: 0.4rem; margin-bottom: 0.5rem; flex-wrap: wrap; align-items: center;">
                                <button type="button" class="sn-btn sn-btn-cancel" id="btnDiscoverLogo" onclick="triggerLogoDiscovery(false)" style="padding: 0.25rem 0.55rem; font-size: 0.72rem;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 0.3rem;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    <span>Find Updated Logo</span>
                                </button>
                                <span id="discoveryStatus" style="display: none; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: var(--primary, #4f46e5); font-weight: 600;">
                                    <span class="spinner-sm"></span> Searching logos...
                                </span>
                                <span id="discoveryError" style="display: none; font-size: 0.72rem; color: #d72c0d; font-weight: 500;"></span>
                            </div>

                            <div id="candidatesContainer" style="display: none; margin-bottom: 0.5rem; padding: 0.5rem; background: #ffffff; border: 1px solid #e1e3e5; border-radius: 6px;">
                                <div style="font-size: 0.65rem; font-weight: 700; color: #6d7175; text-transform: uppercase; margin-bottom: 0.3rem;">
                                    Discovered Logos (Click to Select)
                                </div>
                                <div id="candidatesGrid" style="display: flex; flex-wrap: wrap; gap: 0.4rem;"></div>
                            </div>

                            <div>
                                <label for="logo_file" style="font-size: 0.6875rem; font-weight: 600; color: #202223; display: block; margin-bottom: 0.2rem;">
                                    Or Upload New Logo File (PNG, JPG, SVG - Max 5MB):
                                </label>
                                <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.gif,.ico,.svg" style="font-size: 0.72rem;" onchange="handleManualFileUpload(this)">
                            </div>

                            <div id="selectedLogoPreviewBox" style="display: none; margin-top: 0.5rem; align-items: center; gap: 0.6rem; padding: 0.4rem 0.6rem; background: #ffffff; border: 1px solid var(--primary, #4f46e5); border-radius: 6px;">
                                <div style="width: 28px; height: 28px; border-radius: 5px; background: #fafbfb; border: 1px solid #e1e3e5; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                    <img id="selectedLogoPreviewImg" src="" alt="Selected logo" style="width: 20px; height: 20px; object-fit: contain;">
                                </div>
                                <div style="flex: 1; font-size: 0.72rem;">
                                    <div style="font-weight: 600; color: #202223;" id="selectedLogoSourceTitle">Replacement Logo Selected</div>
                                </div>
                                <button type="button" onclick="clearSelectedLogo()" style="color: #d72c0d; background: none; border: none; cursor: pointer; font-size: 0.6875rem; font-weight: 600;">✕ Undo</button>
                            </div>

                            <input type="hidden" name="selected_logo_url" id="selected_logo_url" value="{{ old('selected_logo_url') }}">

                            @error('logo_file')
                                <div class="sn-field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif

                <!-- Live Widget Preview Strip -->
                <div class="sn-preview-container">
                    <div>
                        <span style="font-size: 0.75rem; font-weight: 700; color: #202223;">Storefront Dock Preview</span>
                        <div style="font-size: 0.6875rem; color: #6d7175;">Live representation of how this icon appears to customers</div>
                    </div>
                    <div class="sn-preview-dock-item" id="previewDockItem">
                        <div class="sn-preview-icon-circle" id="previewIconWrap" style="background: {{ $link->brand_color }};">
                            {!! $link->icon_html !!}
                        </div>
                        <span class="sn-preview-label-text" id="previewLabelText">{{ $link->label ?: $link->platform_name }}</span>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="sn-card-actions">
                    <button type="button" class="sn-btn-delete" onclick="document.getElementById('deleteLinkForm').submit();">
                        Delete Link
                    </button>

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <a href="{{ route('shopify.social-links.index') }}" class="sn-btn sn-btn-cancel">Cancel</a>
                        <button type="submit" class="sn-btn sn-btn-submit">Update Social Link</button>
                    </div>
                </div>
            </form>

            <form id="deleteLinkForm" action="{{ route('shopify.social-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your {{ $link->platform_name }} link?');" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <script>
        const platformPlaceholders = {
            @foreach($allPlatforms as $k => $p)
                '{{ $k }}': '{{ $p['placeholder'] }}',
            @endforeach
        };

        const platformData = {
            @foreach($allPlatforms as $k => $p)
                '{{ $k }}': {
                    name: @json($p['name']),
                    color: @json($p['brand_color'] ?? '#4f46e5'),
                    placeholder: @json($p['placeholder']),
                    icon: {!! json_encode(\App\Services\SocialPlatformService::iconSvg($k, 15)) !!}
                },
            @endforeach
        };

        function updatePlaceholder() {
            const select = document.getElementById('platform');
            const urlInput = document.getElementById('url');
            if (select && urlInput && platformPlaceholders[select.value]) {
                urlInput.placeholder = platformPlaceholders[select.value];
            }
        }

        function updateLivePreview() {
            const select = document.getElementById('platform');
            const labelInput = document.getElementById('label');
            const customNameInput = document.getElementById('custom_platform_name');
            const iconWrap = document.getElementById('previewIconWrap');
            const labelText = document.getElementById('previewLabelText');
            const selectedLogoUrl = document.getElementById('selected_logo_url') ? document.getElementById('selected_logo_url').value : '';

            const val = select ? select.value : 'other';
            const customLabel = labelInput ? labelInput.value.trim() : '';
            const customName = customNameInput ? customNameInput.value.trim() : '';

            if (val === 'other') {
                const displayName = customLabel || customName || @json($link->platform_name);
                labelText.textContent = displayName;

                if (selectedLogoUrl) {
                    iconWrap.innerHTML = `<img src="${selectedLogoUrl}" alt="logo" style="width: 15px; height: 15px; object-fit: contain;">`;
                }
            } else if (platformData[val]) {
                const p = platformData[val];
                labelText.textContent = customLabel || p.name;
                iconWrap.style.background = p.color;
                iconWrap.innerHTML = p.icon;
            }
        }

        let discoveryDebounceTimer = null;

        function debouncedLogoDiscovery() {
            clearTimeout(discoveryDebounceTimer);
            discoveryDebounceTimer = setTimeout(() => {
                const name = document.getElementById('custom_platform_name') ? document.getElementById('custom_platform_name').value.trim() : '';
                if (name.length >= 2) {
                    triggerLogoDiscovery(true);
                }
            }, 650);
        }

        // Automatic Logo Discovery for Custom Apps
        function triggerLogoDiscovery(silent = false) {
            const name = document.getElementById('custom_platform_name') ? document.getElementById('custom_platform_name').value.trim() : '';
            const destination = document.getElementById('url').value.trim();
            const website = document.getElementById('official_website_url') ? document.getElementById('official_website_url').value.trim() : '';
            const statusEl = document.getElementById('discoveryStatus');
            const errorEl = document.getElementById('discoveryError');
            const containerEl = document.getElementById('candidatesContainer');
            const gridEl = document.getElementById('candidatesGrid');

            if (!name && !destination && !website) {
                if (!silent) {
                    if (errorEl) {
                        errorEl.textContent = 'Please enter an App Name or Website URL first.';
                        errorEl.style.display = 'inline';
                    } else {
                        alert('Please enter an App Name, Destination URL, or Official Website URL to search for updated logos.');
                    }
                }
                return;
            }

            if (errorEl) errorEl.style.display = 'none';
            if (statusEl) statusEl.style.display = 'inline-flex';
            if (!silent) containerEl.style.display = 'none';

            const urlParams = new URLSearchParams(window.location.search);
            const queryParams = new URLSearchParams();
            if (urlParams.get('shop')) queryParams.set('shop', urlParams.get('shop'));
            if (urlParams.get('host')) queryParams.set('host', urlParams.get('host'));
            const endpoint = '{{ route("shopify.social-links.discover-logos") }}' + (queryParams.toString() ? '?' + queryParams.toString() : '');

            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: name,
                    url: destination,
                    website_url: website,
                    shop: urlParams.get('shop') || @json($shop ?? '')
                })
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (statusEl) statusEl.style.display = 'none';

                if (data.success && data.candidates && data.candidates.length > 0) {
                    containerEl.style.display = 'block';
                    gridEl.innerHTML = '';

                    data.candidates.forEach((cand, idx) => {
                        const card = document.createElement('div');
                        card.className = 'candidate-card' + (idx === 0 ? ' active' : '');
                        card.onclick = () => selectCandidate(cand.url, cand.source_name, card);

                        card.innerHTML = `
                            <img src="${cand.url}" alt="${cand.source_name}" style="width: 18px; height: 18px; object-fit: contain;">
                            <div>
                                <div style="font-weight: 600; color: #202223;">${cand.source_name}</div>
                                <div style="color: #6d7175; font-size: 0.65rem;">${cand.source_domain || ''}</div>
                            </div>
                        `;
                        gridEl.appendChild(card);
                    });

                    if (data.preselected) {
                        selectCandidate(data.preselected.url, data.preselected.source_name, gridEl.children[0]);
                    }
                } else {
                    if (!silent) {
                        if (errorEl) {
                            errorEl.textContent = data.message || 'No logos found automatically. You can upload a new logo file directly below.';
                            errorEl.style.display = 'inline';
                        } else {
                            alert(data.message || 'No logos found automatically. You can upload a new logo file directly below.');
                        }
                    }
                }
            })
            .catch(err => {
                if (statusEl) statusEl.style.display = 'none';
                if (!silent) {
                    if (errorEl) {
                        errorEl.textContent = 'Discovery request failed. You can upload a logo file directly.';
                        errorEl.style.display = 'inline';
                    }
                }
            });
        }

        function selectCandidate(url, sourceName, cardEl) {
            document.getElementById('selected_logo_url').value = url;
            document.getElementById('logo_file').value = '';

            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));
            if (cardEl) cardEl.classList.add('active');

            const previewBox = document.getElementById('selectedLogoPreviewBox');
            const previewImg = document.getElementById('selectedLogoPreviewImg');
            const previewTitle = document.getElementById('selectedLogoSourceTitle');

            previewImg.src = url;
            previewTitle.textContent = sourceName || 'Selected Replacement Logo';
            previewBox.style.display = 'flex';

            updateLivePreview();
        }

        function handleManualFileUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                document.getElementById('selected_logo_url').value = '';
                document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));

                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewBox = document.getElementById('selectedLogoPreviewBox');
                    const previewImg = document.getElementById('selectedLogoPreviewImg');
                    const previewTitle = document.getElementById('selectedLogoSourceTitle');

                    previewImg.src = e.target.result;
                    previewTitle.textContent = 'Uploaded: ' + file.name;
                    previewBox.style.display = 'flex';

                    const iconWrap = document.getElementById('previewIconWrap');
                    if (iconWrap) {
                        iconWrap.innerHTML = `<img src="${e.target.result}" alt="logo" style="width: 15px; height: 15px; object-fit: contain;">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function clearSelectedLogo() {
            document.getElementById('selected_logo_url').value = '';
            document.getElementById('logo_file').value = '';
            document.getElementById('selectedLogoPreviewBox').style.display = 'none';
            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));
            updateLivePreview();
        }
    </script>
@endsection
