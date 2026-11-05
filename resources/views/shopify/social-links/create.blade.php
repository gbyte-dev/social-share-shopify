@extends('layouts.shopify')

@section('title', 'Add Social Link - ' . $shop)
@section('header_title', 'Add Social Link')
@section('header_subtitle', 'Connect a built-in or custom social platform to your storefront sharing dock')

@section('content')
    <!-- Scoped Polaris-inspired Styles for 2-Column Full-Cover Add Social Link -->
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
        }

        .sn-card-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #202223;
            margin: 0;
        }

        .sn-card-subtitle {
            font-size: 0.75rem;
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
            margin-top: 0.25rem;
            line-height: 1.35;
        }

        .sn-field-error {
            font-size: 0.6875rem;
            color: #d72c0d;
            margin-top: 0.25rem;
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
            justify-content: flex-end;
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
                <div>
                    <h3 class="sn-card-title">Social Channel Details</h3>
                    <p class="sn-card-subtitle">Connect standard channels or configure custom platforms with automatic logo discovery.</p>
                </div>
            </div>

            <form action="{{ route('shopify.social-links.store') }}" method="POST" enctype="multipart/form-data" id="socialLinkForm">
                @csrf

                <!-- 2-Column Balanced Form Grid (Full Space Cover) -->
                <div class="sn-form-grid">
                    <!-- Column 1: Primary Platform & URL -->
                    <div>
                        <!-- Social Media Platform -->
                        <div class="sn-field-group">
                            <label for="platform" class="sn-label">
                                Social Media Platform <span style="color: #d72c0d;">*</span>
                            </label>

                            <select id="platform" name="platform" required class="sn-select" onchange="handlePlatformChange()">
                                <option value="">-- Choose a Platform --</option>
                                <optgroup label="Standard Platforms">
                                    @foreach($allPlatforms as $key => $plat)
                                        @if($key !== 'other')
                                            @php
                                                $isConfigured = !isset($availablePlatforms[$key]);
                                                $isSelected = old('platform', request('platform')) === $key;
                                            @endphp
                                            <option value="{{ $key }}" {{ $isSelected ? 'selected' : '' }} {{ $isConfigured ? 'disabled style=color:#94a3b8;' : '' }}>
                                                {{ $plat['name'] }} {{ $isConfigured ? '(Already Added)' : '' }}
                                            </option>
                                        @endif
                                    @endforeach
                                </optgroup>
                                <optgroup label="Custom Platforms">
                                    <option value="other" {{ old('platform', request('platform')) === 'other' ? 'selected' : '' }}>
                                        Other / Custom Platform (Discord, Telegram, Reddit, Threads, Twitch, etc.)
                                    </option>
                                </optgroup>
                            </select>
                            @error('platform')
                                <div class="sn-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Destination URL -->
                        <div class="sn-field-group">
                            <label for="url" class="sn-label">
                                Destination or Profile URL <span style="color: #d72c0d;">*</span>
                            </label>
                            <input type="text" id="url" name="url" value="{{ old('url') }}" required placeholder="https://www.instagram.com/yourprofile" class="sn-input" oninput="updateLivePreview()">
                            <div id="urlHint" class="sn-help-text">
                                Enter your profile, community invite, server, or custom destination link for this platform.
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
                            <input type="text" id="label" name="label" value="{{ old('label') }}" placeholder="e.g. Follow us on Instagram" maxlength="100" class="sn-input" oninput="updateLivePreview()">
                            <div class="sn-help-text">
                                Tooltip text shown when customers hover over the icon. Defaults to platform name.
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
                                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $nextOrder) }}" min="0" max="9999" class="sn-input">
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
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: var(--primary, #4f46e5); margin: 0;">
                                    <div>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: #202223; display: block;">Enable on Storefront</span>
                                        <span style="font-size: 0.65rem; color: #6d7175; display: block;">Show immediately in dock.</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Platform Details (Shown only when 'other' is selected) -->
                <div id="customPlatformFields" class="sn-custom-box" style="display: {{ old('platform', request('platform')) === 'other' ? 'block' : 'none' }};">
                    <div class="sn-custom-box-head">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary, #4f46e5)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                        <h4 class="sn-custom-box-title">Custom App / Platform Settings</h4>
                    </div>

                    <div class="sn-subgrid">
                        <!-- Custom App Name -->
                        <div class="sn-field-group">
                            <label for="custom_platform_name" class="sn-label">
                                App or Platform Name <span style="color: #d72c0d;">*</span>
                            </label>
                            <input type="text" id="custom_platform_name" name="custom_platform_name" value="{{ old('custom_platform_name') }}" placeholder="e.g. Discord, Telegram, Reddit, Threads, Twitch" maxlength="100" class="sn-input" oninput="updateLivePreview(); debouncedLogoDiscovery();">
                            @error('custom_platform_name')
                                <div class="sn-field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Official Website URL -->
                        <div class="sn-field-group">
                            <label for="official_website_url" class="sn-label">
                                Official Website URL <span class="sn-label-opt">(Aids logo discovery)</span>
                            </label>
                            <input type="text" id="official_website_url" name="official_website_url" value="{{ old('official_website_url') }}" placeholder="e.g. https://discord.com" class="sn-input" oninput="debouncedLogoDiscovery();">
                            @error('official_website_url')
                                <div class="sn-field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Alt Text -->
                    <div class="sn-field-group">
                        <label for="alt_text" class="sn-label">
                            Accessibility Alt Text <span class="sn-label-opt">(Optional)</span>
                        </label>
                        <input type="text" id="alt_text" name="alt_text" value="{{ old('alt_text') }}" placeholder="e.g. Join our Discord Community" maxlength="150" class="sn-input">
                        @error('alt_text')
                            <div class="sn-field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Logo Section: Automatic Discovery + Manual Upload -->
                    <div style="border-top: 1px dashed #e1e3e5; padding-top: 0.75rem; margin-top: 0.75rem;">
                        <label class="sn-label">
                            Platform Logo <span style="color: #d72c0d;">*</span>
                        </label>
                        <p class="sn-help-text" style="margin-bottom: 0.5rem;">
                            Search for official brand assets or upload a custom image. Optimized 36px, 44px, and 56px variants will be generated.
                        </p>

                        <div style="display: flex; gap: 0.4rem; margin-bottom: 0.5rem; flex-wrap: wrap; align-items: center;">
                            <button type="button" class="sn-btn sn-btn-cancel" id="btnDiscoverLogo" onclick="triggerLogoDiscovery(false)" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 0.35rem;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <span>Find Logo Automatically</span>
                            </button>
                            <span id="discoveryStatus" style="display: none; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: var(--primary, #4f46e5); font-weight: 600;">
                                <span class="spinner-sm"></span> Searching website & brand directories...
                            </span>
                            <span id="discoveryError" style="display: none; font-size: 0.72rem; color: #d72c0d; font-weight: 500;"></span>
                        </div>

                        <div id="candidatesContainer" style="display: none; margin-bottom: 0.75rem; padding: 0.5rem 0.75rem; background: #ffffff; border: 1px solid #e1e3e5; border-radius: 6px;">
                            <div style="font-size: 0.6875rem; font-weight: 700; color: #6d7175; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                                Discovered Logos (Click to Select)
                            </div>
                            <div id="candidatesGrid" style="display: flex; flex-wrap: wrap; gap: 0.4rem;"></div>
                        </div>

                        <div>
                            <label for="logo_file" style="font-size: 0.6875rem; font-weight: 600; color: #202223; display: block; margin-bottom: 0.2rem;">
                                Or Upload Logo File (PNG, JPG, WebP, GIF, ICO, SVG - Max 5MB):
                            </label>
                            <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.gif,.ico,.svg" style="font-size: 0.75rem;" onchange="handleManualFileUpload(this)">
                        </div>

                        <div id="selectedLogoPreviewBox" style="display: none; margin-top: 0.5rem; align-items: center; gap: 0.6rem; padding: 0.4rem 0.6rem; background: #ffffff; border: 1px solid var(--primary, #4f46e5); border-radius: 6px;">
                            <div style="width: 30px; height: 30px; border-radius: 6px; background: #fafbfb; border: 1px solid #e1e3e5; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                <img id="selectedLogoPreviewImg" src="" alt="Selected logo" style="width: 20px; height: 20px; object-fit: contain;">
                            </div>
                            <div style="flex: 1; font-size: 0.75rem;">
                                <div style="font-weight: 600; color: #202223;" id="selectedLogoSourceTitle">Logo Selected</div>
                            </div>
                            <button type="button" onclick="clearSelectedLogo()" style="color: #d72c0d; background: none; border: none; cursor: pointer; font-size: 0.6875rem; font-weight: 600;">✕ Remove</button>
                        </div>

                        <input type="hidden" name="selected_logo_url" id="selected_logo_url" value="{{ old('selected_logo_url') }}">

                        @error('logo_file')
                            <div class="sn-field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Live Widget Preview Strip -->
                <div class="sn-preview-container">
                    <div>
                        <span style="font-size: 0.75rem; font-weight: 700; color: #202223;">Storefront Dock Preview</span>
                        <div style="font-size: 0.6875rem; color: #6d7175;">Live representation of how this icon appears to customers</div>
                    </div>
                    <div class="sn-preview-dock-item" id="previewDockItem">
                        <div class="sn-preview-icon-circle" id="previewIconWrap" style="background: #4f46e5;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        </div>
                        <span class="sn-preview-label-text" id="previewLabelText">Select a platform</span>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="sn-card-actions">
                    <a href="{{ route('shopify.social-links.index') }}" class="sn-btn sn-btn-cancel">Cancel</a>
                    <button type="submit" class="sn-btn sn-btn-submit">Save Social Link</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        function handlePlatformChange() {
            const select = document.getElementById('platform');
            const customBox = document.getElementById('customPlatformFields');
            const urlInput = document.getElementById('url');
            const customNameInput = document.getElementById('custom_platform_name');

            if (!select) return;

            const val = select.value;

            if (val === 'other') {
                customBox.style.display = 'block';
                customNameInput.setAttribute('required', 'required');
                urlInput.placeholder = 'https://discord.gg/yourcommunity or https://t.me/yourchannel';
            } else {
                customBox.style.display = 'none';
                customNameInput.removeAttribute('required');
                if (platformData[val] && platformData[val].placeholder) {
                    urlInput.placeholder = platformData[val].placeholder;
                }
            }

            updateLivePreview();
        }

        function updateLivePreview() {
            const select = document.getElementById('platform');
            const labelInput = document.getElementById('label');
            const customNameInput = document.getElementById('custom_platform_name');
            const iconWrap = document.getElementById('previewIconWrap');
            const labelText = document.getElementById('previewLabelText');
            const selectedLogoUrl = document.getElementById('selected_logo_url').value;

            if (!select) return;

            const val = select.value;
            const customLabel = labelInput ? labelInput.value.trim() : '';
            const customName = customNameInput ? customNameInput.value.trim() : '';

            if (val === 'other') {
                const displayName = customLabel || customName || 'Custom Platform';
                labelText.textContent = displayName;
                iconWrap.style.background = '#4f46e5';

                if (selectedLogoUrl) {
                    iconWrap.innerHTML = `<img src="${selectedLogoUrl}" alt="logo" style="width: 15px; height: 15px; object-fit: contain;">`;
                } else {
                    iconWrap.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>`;
                }
            } else if (platformData[val]) {
                const p = platformData[val];
                labelText.textContent = customLabel || p.name;
                iconWrap.style.background = p.color;
                iconWrap.innerHTML = p.icon;
            } else {
                labelText.textContent = customLabel || 'Select a platform';
                iconWrap.style.background = '#6d7175';
                iconWrap.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>`;
            }
        }

        let discoveryDebounceTimer = null;

        function debouncedLogoDiscovery() {
            clearTimeout(discoveryDebounceTimer);
            discoveryDebounceTimer = setTimeout(() => {
                const select = document.getElementById('platform');
                if (select && select.value === 'other') {
                    const name = document.getElementById('custom_platform_name') ? document.getElementById('custom_platform_name').value.trim() : '';
                    if (name.length >= 2) {
                        triggerLogoDiscovery(true);
                    }
                }
            }, 650);
        }

        // Automatic Logo Discovery
        function triggerLogoDiscovery(silent = false) {
            const name = document.getElementById('custom_platform_name') ? document.getElementById('custom_platform_name').value.trim() : '';
            const destination = document.getElementById('url') ? document.getElementById('url').value.trim() : '';
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
                        alert('Please enter an App Name, Destination URL, or Official Website URL to search for logos.');
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
                            errorEl.textContent = data.message || 'No logos found automatically. Please upload a logo file below.';
                            errorEl.style.display = 'inline';
                        } else {
                            alert(data.message || 'No logos found automatically. Please upload a logo file manually below.');
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
            previewTitle.textContent = sourceName || 'Selected Remote Logo';
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', handlePlatformChange);
    </script>
@endsection
