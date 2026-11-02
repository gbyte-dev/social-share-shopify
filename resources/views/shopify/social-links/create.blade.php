@extends('layouts.shopify')

@section('title', 'Add Social Link - ' . $shop)
@section('header_title', 'Add Social Link')
@section('header_subtitle', 'Connect a built-in or custom social platform to your storefront sharing dock')

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <div class="card">
            <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border);">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">Social Channel Details</h3>
                <p style="font-size: 0.8125rem; color: var(--text-subtle);">Connect standard channels or configure any custom app with automatic logo discovery.</p>
            </div>

            <form action="{{ route('shopify.social-links.store') }}" method="POST" enctype="multipart/form-data" id="socialLinkForm">
                @csrf

                <!-- Platform Selection -->
                <div style="margin-bottom: 1.25rem;">
                    <label for="platform" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Social Media Platform <span style="color: var(--danger);">*</span>
                    </label>

                    <select id="platform" name="platform" required style="width: 100%; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);" onchange="handlePlatformChange()">
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
                                Other / Custom Platform (Discord, Telegram, Reddit, Threads, etc.)
                            </option>
                        </optgroup>
                    </select>
                    @error('platform')
                        <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Custom Platform Details (Shown only when 'other' is selected) -->
                <div id="customPlatformFields" style="display: {{ old('platform', request('platform')) === 'other' ? 'block' : 'none' }}; margin-bottom: 1.5rem; padding: 1.25rem; background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                        <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin: 0;">Custom App / Platform Settings</h4>
                    </div>

                    <!-- Custom App Name -->
                    <div style="margin-bottom: 1rem;">
                        <label for="custom_platform_name" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                            App or Platform Name <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="text" id="custom_platform_name" name="custom_platform_name" value="{{ old('custom_platform_name') }}" placeholder="e.g. Discord, Telegram, Reddit, Threads, Twitch" maxlength="100" style="width: 100%; padding: 0.6rem 0.8rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
                        @error('custom_platform_name')
                            <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Official Website URL (Optional, aids logo discovery) -->
                    <div style="margin-bottom: 1rem;">
                        <label for="official_website_url" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                            Official Website URL <span style="font-weight: 400; color: var(--text-subtle);">(Optional, helps discover high-res logos)</span>
                        </label>
                        <input type="text" id="official_website_url" name="official_website_url" value="{{ old('official_website_url') }}" placeholder="e.g. https://discord.com" style="width: 100%; padding: 0.6rem 0.8rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
                        @error('official_website_url')
                            <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Alt Text -->
                    <div style="margin-bottom: 1.25rem;">
                        <label for="alt_text" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                            Accessibility Alt Text <span style="font-weight: 400; color: var(--text-subtle);">(Optional)</span>
                        </label>
                        <input type="text" id="alt_text" name="alt_text" value="{{ old('alt_text') }}" placeholder="e.g. Join our Discord Community" maxlength="150" style="width: 100%; padding: 0.6rem 0.8rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
                        @error('alt_text')
                            <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Logo Section: Automatic Discovery + Manual Upload -->
                    <div style="border-top: 1px dashed var(--card-border); padding-top: 1rem; margin-top: 1rem;">
                        <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                            Platform Logo <span style="color: var(--danger);">*</span>
                        </label>
                        <p style="font-size: 0.75rem; color: var(--text-subtle); margin-bottom: 0.75rem;">
                            Automatically search for official brand assets, or upload a custom image. We'll automatically generate optimized 36px, 44px, and 56px variants.
                        </p>

                        <!-- Automatic Discovery Action Box -->
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary btn-sm" id="btnDiscoverLogo" onclick="triggerLogoDiscovery()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <span>Find Logo Automatically</span>
                            </button>
                            <span id="discoveryStatus" style="display: none; align-items: center; gap: 0.4rem; font-size: 0.8125rem; color: var(--primary); font-weight: 600;">
                                <span class="spinner-sm"></span> Searching website & brand directories...
                            </span>
                        </div>

                        <!-- Discovered Candidates Grid -->
                        <div id="candidatesContainer" style="display: none; margin-bottom: 1rem; padding: 0.75rem; background: #ffffff; border: 1px solid var(--card-border); border-radius: var(--radius-md);">
                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                                Discovered Logos (Click to Select)
                            </div>
                            <div id="candidatesGrid" style="display: flex; flex-wrap: wrap; gap: 0.6rem;"></div>
                        </div>

                        <!-- Manual Upload Option -->
                        <div style="margin-top: 0.75rem;">
                            <label for="logo_file" style="font-size: 0.75rem; font-weight: 600; color: var(--text-main); display: block; margin-bottom: 0.25rem;">
                                Or Upload Logo File (PNG, JPG, WebP, GIF, ICO, SVG - Max 5MB):
                            </label>
                            <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.gif,.ico,.svg" style="font-size: 0.8125rem;" onchange="handleManualFileUpload(this)">
                        </div>

                        <!-- Active Selected Logo Preview -->
                        <div id="selectedLogoPreviewBox" style="display: none; margin-top: 0.75rem; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; background: #ffffff; border: 1px solid var(--primary); border-radius: var(--radius-md);">
                            <div style="width: 38px; height: 38px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <img id="selectedLogoPreviewImg" src="" alt="Selected logo" style="width: 28px; height: 28px; object-fit: contain;">
                            </div>
                            <div style="flex: 1; font-size: 0.8125rem;">
                                <div style="font-weight: 700; color: var(--text-main);" id="selectedLogoSourceTitle">Logo Selected</div>
                                <div style="font-size: 0.72rem; color: var(--text-subtle);" id="selectedLogoSourceSubtitle">Ready to optimize into 36px, 44px, and 56px variants</div>
                            </div>
                            <button type="button" class="btn btn-sm" onclick="clearSelectedLogo()" style="color: var(--danger); background: none; border: none; cursor: pointer; font-size: 0.75rem;">✕ Remove</button>
                        </div>

                        <input type="hidden" name="selected_logo_url" id="selected_logo_url" value="{{ old('selected_logo_url') }}">

                        @error('logo_file')
                            <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.35rem;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Destination URL -->
                <div style="margin-bottom: 1.25rem;">
                    <label for="url" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Destination or Profile URL <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" id="url" name="url" value="{{ old('url') }}" required placeholder="https://www.instagram.com/yourprofile" style="width: 100%; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);">
                    <div id="urlHint" style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.35rem;">
                        Enter your profile, community invite, server, or custom destination link for this platform.
                    </div>
                    @error('url')
                        <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Custom Display Label -->
                <div style="margin-bottom: 1.25rem;">
                    <label for="label" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Custom Display Label <span style="font-weight: 400; color: var(--text-subtle);">(Optional)</span>
                    </label>
                    <input type="text" id="label" name="label" value="{{ old('label') }}" placeholder="e.g. Follow us on Instagram" maxlength="100" style="width: 100%; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);">
                    <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.25rem;">
                        Tooltip text shown when customers hover over the icon. Defaults to the platform name.
                    </div>
                    @error('label')
                        <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Display Sort Order -->
                <div style="margin-bottom: 1.25rem;">
                    <label for="sort_order" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Display Order
                    </label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $nextOrder) }}" min="0" max="9999" style="width: 140px; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);">
                    <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.25rem;">
                        Lower numbers appear first in the floating dock (e.g. 1, 2, 3).
                    </div>
                    @error('sort_order')
                        <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Active Status Toggle -->
                <div style="margin-bottom: 2rem; padding: 1rem; background: var(--surface-bg); border-radius: var(--radius-md); border: 1px solid var(--card-border);">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 700; color: var(--text-main);">Enable on Storefront</span>
                            <div style="font-size: 0.75rem; color: var(--text-subtle);">Display this social icon in your floating widget immediately.</div>
                        </div>
                    </label>
                </div>

                <!-- Form Actions -->
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--card-border); padding-top: 1.25rem;">
                    <a href="{{ route('shopify.social-links.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Social Link</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .candidate-card {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.6rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 0.75rem;
        }
        .candidate-card:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }
        .candidate-card.active {
            border-color: var(--primary);
            background: #eef2ff;
            box-shadow: 0 0 0 1px var(--primary);
        }
        .spinner-sm {
            width: 12px;
            height: 12px;
            border: 2px solid #cbd5e1;
            border-top-color: var(--primary);
            border-radius: 50%;
            display: inline-block;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <script>
        const platformPlaceholders = {
            @foreach($allPlatforms as $k => $p)
                '{{ $k }}': '{{ $p['placeholder'] }}',
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
                if (platformPlaceholders[val]) {
                    urlInput.placeholder = platformPlaceholders[val];
                }
            }
        }

        // Automatic Logo Discovery
        function triggerLogoDiscovery() {
            const name = document.getElementById('custom_platform_name').value.trim();
            const destination = document.getElementById('url').value.trim();
            const website = document.getElementById('official_website_url').value.trim();
            const statusEl = document.getElementById('discoveryStatus');
            const containerEl = document.getElementById('candidatesContainer');
            const gridEl = document.getElementById('candidatesGrid');

            if (!name && !destination && !website) {
                alert('Please enter an App Name, Destination URL, or Official Website URL to search for logos.');
                return;
            }

            statusEl.style.display = 'inline-flex';
            containerEl.style.display = 'none';
            gridEl.innerHTML = '';

            fetch('{{ route("shopify.social-links.discover-logos") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: name,
                    url: destination,
                    website_url: website
                })
            })
            .then(res => res.json())
            .then(data => {
                statusEl.style.display = 'none';

                if (data.success && data.candidates && data.candidates.length > 0) {
                    containerEl.style.display = 'block';
                    gridEl.innerHTML = '';

                    data.candidates.forEach((cand, idx) => {
                        const card = document.createElement('div');
                        card.className = 'candidate-card' + (idx === 0 ? ' active' : '');
                        card.onclick = () => selectCandidate(cand.url, cand.source_name, card);

                        card.innerHTML = `
                            <img src="${cand.url}" alt="${cand.source_name}" style="width: 22px; height: 22px; object-fit: contain;">
                            <div>
                                <div style="font-weight: 700; color: #1e293b;">${cand.source_name}</div>
                                <div style="color: #64748b; font-size: 0.7rem;">${cand.source_domain || ''}</div>
                            </div>
                        `;
                        gridEl.appendChild(card);
                    });

                    // Preselect first candidate
                    if (data.preselected) {
                        selectCandidate(data.preselected.url, data.preselected.source_name, gridEl.children[0]);
                    }
                } else {
                    alert(data.message || 'No logos found automatically. Please upload a logo file manually below.');
                }
            })
            .catch(err => {
                statusEl.style.display = 'none';
                alert('Logo discovery request failed. You can still upload a logo file directly.');
            });
        }

        function selectCandidate(url, sourceName, cardEl) {
            document.getElementById('selected_logo_url').value = url;
            document.getElementById('logo_file').value = ''; // clear file input if previously set

            // Mark active card
            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));
            if (cardEl) cardEl.classList.add('active');

            // Show active preview
            const previewBox = document.getElementById('selectedLogoPreviewBox');
            const previewImg = document.getElementById('selectedLogoPreviewImg');
            const previewTitle = document.getElementById('selectedLogoSourceTitle');

            previewImg.src = url;
            previewTitle.textContent = sourceName || 'Selected Remote Logo';
            previewBox.style.display = 'flex';
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
                };
                reader.readAsDataURL(file);
            }
        }

        function clearSelectedLogo() {
            document.getElementById('selected_logo_url').value = '';
            document.getElementById('logo_file').value = '';
            document.getElementById('selectedLogoPreviewBox').style.display = 'none';
            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', handlePlatformChange);
    </script>
@endsection
