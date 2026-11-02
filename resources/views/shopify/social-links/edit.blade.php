@extends('layouts.shopify')

@section('title', 'Edit ' . $link->platform_name . ' Link - ' . $shop)
@section('header_title', 'Edit Social Link')
@section('header_subtitle', 'Update channel details, URL, or visibility for ' . $link->platform_name)

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--card-border); flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: {{ $link->brand_color }}; color: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                        {!! $link->icon_html !!}
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">{{ $link->platform_name }}</h3>
                            @if($link->isCustom())
                                <span style="font-size: 0.7rem; font-weight: 700; background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 9999px;">Custom App</span>
                            @endif
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-subtle); margin: 0.2rem 0 0;">Added {{ $link->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <form action="{{ route('shopify.social-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your {{ $link->platform_name }} link?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm" style="color: var(--danger); background: transparent; border: 1px solid #fca5a5;">
                        Delete Link
                    </button>
                </form>
            </div>

            <form action="{{ route('shopify.social-links.update', $link->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @if(! $link->isCustom())
                    <!-- Built-in Platform Selection -->
                    <div style="margin-bottom: 1.25rem;">
                        <label for="platform" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                            Social Media Platform <span style="color: var(--danger);">*</span>
                        </label>
                        <select id="platform" name="platform" required style="width: 100%; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);" onchange="updatePlaceholder()">
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
                            <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                        @enderror
                    </div>
                @else
                    <input type="hidden" name="platform" value="other">

                    <!-- Custom Platform Settings Section -->
                    <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md);">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                            <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin: 0;">Custom App / Platform Details</h4>
                        </div>

                        <!-- App Name -->
                        <div style="margin-bottom: 1rem;">
                            <label for="custom_platform_name" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                                App or Platform Name <span style="color: var(--danger);">*</span>
                            </label>
                            <input type="text" id="custom_platform_name" name="custom_platform_name" value="{{ old('custom_platform_name', $link->custom_platform_name) }}" required maxlength="100" style="width: 100%; padding: 0.6rem 0.8rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
                            @error('custom_platform_name')
                                <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Official Website URL -->
                        <div style="margin-bottom: 1rem;">
                            <label for="official_website_url" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                                Official Website URL <span style="font-weight: 400; color: var(--text-subtle);">(Optional, aids logo discovery)</span>
                            </label>
                            <input type="text" id="official_website_url" name="official_website_url" value="{{ old('official_website_url', $link->official_website_url) }}" placeholder="e.g. https://discord.com" style="width: 100%; padding: 0.6rem 0.8rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
                            @error('official_website_url')
                                <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Alt Text -->
                        <div style="margin-bottom: 1.25rem;">
                            <label for="alt_text" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                                Accessibility Alt Text
                            </label>
                            <input type="text" id="alt_text" name="alt_text" value="{{ old('alt_text', $link->alt_text) }}" placeholder="e.g. Join our community" maxlength="150" style="width: 100%; padding: 0.6rem 0.8rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
                            @error('alt_text')
                                <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Current Logo Preview Box -->
                        <div style="border-top: 1px dashed var(--card-border); padding-top: 1rem; margin-top: 1rem;">
                            <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                                Current Platform Logo
                            </label>

                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; padding: 0.75rem; background: #ffffff; border: 1px solid var(--card-border); border-radius: var(--radius-md);">
                                <div style="width: 48px; height: 48px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    @if($link->logo_medium_url)
                                        <img src="{{ $link->logo_medium_url }}" alt="{{ $link->platform_name }}" style="width: 32px; height: 32px; object-fit: contain;">
                                    @else
                                        {!! $link->icon_svg !!}
                                    @endif
                                </div>
                                <div style="font-size: 0.8125rem; flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-main);">Active Stored Logo</div>
                                    <div style="font-size: 0.72rem; color: var(--text-subtle);">
                                        Variants generated: Small (36px), Medium (44px), Large (56px).
                                        @if($link->logo_source_domain)
                                            Source: {{ $link->logo_source_domain }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Replace Logo Controls -->
                            <label style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                                Replace Logo <span style="font-weight: 400; color: var(--text-subtle);">(Optional - keep current logo if left blank)</span>
                            </label>

                            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                                <button type="button" class="btn btn-secondary btn-sm" id="btnDiscoverLogo" onclick="triggerLogoDiscovery()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    <span>Find Updated Logo</span>
                                </button>
                                <span id="discoveryStatus" style="display: none; align-items: center; gap: 0.4rem; font-size: 0.8125rem; color: var(--primary); font-weight: 600;">
                                    <span class="spinner-sm"></span> Searching website & brand directories...
                                </span>
                            </div>

                            <div id="candidatesContainer" style="display: none; margin-bottom: 1rem; padding: 0.75rem; background: #ffffff; border: 1px solid var(--card-border); border-radius: var(--radius-md);">
                                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                                    Discovered Logos (Click to Select)
                                </div>
                                <div id="candidatesGrid" style="display: flex; flex-wrap: wrap; gap: 0.6rem;"></div>
                            </div>

                            <div style="margin-top: 0.75rem;">
                                <label for="logo_file" style="font-size: 0.75rem; font-weight: 600; color: var(--text-main); display: block; margin-bottom: 0.25rem;">
                                    Or Upload New Logo File (PNG, JPG, WebP, GIF, ICO, SVG - Max 5MB):
                                </label>
                                <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.gif,.ico,.svg" style="font-size: 0.8125rem;" onchange="handleManualFileUpload(this)">
                            </div>

                            <div id="selectedLogoPreviewBox" style="display: none; margin-top: 0.75rem; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; background: #ffffff; border: 1px solid var(--primary); border-radius: var(--radius-md);">
                                <div style="width: 38px; height: 38px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <img id="selectedLogoPreviewImg" src="" alt="Selected logo" style="width: 28px; height: 28px; object-fit: contain;">
                                </div>
                                <div style="flex: 1; font-size: 0.8125rem;">
                                    <div style="font-weight: 700; color: var(--text-main);" id="selectedLogoSourceTitle">Replacement Logo Selected</div>
                                    <div style="font-size: 0.72rem; color: var(--text-subtle);" id="selectedLogoSourceSubtitle">Will be optimized into all 3 sizes upon saving</div>
                                </div>
                                <button type="button" class="btn btn-sm" onclick="clearSelectedLogo()" style="color: var(--danger); background: none; border: none; cursor: pointer; font-size: 0.75rem;">✕ Undo</button>
                            </div>

                            <input type="hidden" name="selected_logo_url" id="selected_logo_url" value="{{ old('selected_logo_url') }}">

                            @error('logo_file')
                                <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.35rem;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif

                <!-- Destination URL -->
                <div style="margin-bottom: 1.25rem;">
                    <label for="url" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Destination or Profile URL <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" id="url" name="url" value="{{ old('url', $link->url) }}" required style="width: 100%; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);">
                    <div id="urlHint" style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.35rem;">
                        Enter your profile, channel, group, or custom destination link for this platform.
                    </div>
                    @error('url')
                        <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Custom Display Label -->
                <div style="margin-bottom: 1.25rem;">
                    <label for="label" style="display: block; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                        Custom Display Label
                    </label>
                    <input type="text" id="label" name="label" value="{{ old('label', $link->label) }}" maxlength="100" style="width: 100%; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);">
                    <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.25rem;">
                        Label or tooltip shown on customer hover.
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
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $link->sort_order) }}" min="0" max="9999" style="width: 140px; padding: 0.65rem 0.85rem; font-size: 0.875rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff; color: var(--text-main);">
                    <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.25rem;">
                        Placement sequence in the floating dock (e.g. 1, 2, 3).
                    </div>
                    @error('sort_order')
                        <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Active Status Toggle -->
                <div style="margin-bottom: 2rem; padding: 1rem; background: var(--surface-bg); border-radius: var(--radius-md); border: 1px solid var(--card-border);">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $link->is_active ? '1' : '0') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 700; color: var(--text-main);">Enable on Storefront</span>
                            <div style="font-size: 0.75rem; color: var(--text-subtle);">Check this box to keep the icon visible in the live storefront widget.</div>
                        </div>
                    </label>
                </div>

                <!-- Form Actions -->
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--card-border); padding-top: 1.25rem;">
                    <a href="{{ route('shopify.social-links.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Social Link</button>
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

        function updatePlaceholder() {
            const select = document.getElementById('platform');
            const urlInput = document.getElementById('url');
            if (select && urlInput && platformPlaceholders[select.value]) {
                urlInput.placeholder = platformPlaceholders[select.value];
            }
        }

        // Automatic Logo Discovery for Custom Platform
        function triggerLogoDiscovery() {
            const nameEl = document.getElementById('custom_platform_name');
            const name = nameEl ? nameEl.value.trim() : '';
            const destination = document.getElementById('url').value.trim();
            const websiteEl = document.getElementById('official_website_url');
            const website = websiteEl ? websiteEl.value.trim() : '';
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
            const fileInput = document.getElementById('logo_file');
            if (fileInput) fileInput.value = '';

            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));
            if (cardEl) cardEl.classList.add('active');

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
                    previewTitle.textContent = 'Replacement: ' + file.name;
                    previewBox.style.display = 'flex';
                };
                reader.readAsDataURL(file);
            }
        }

        function clearSelectedLogo() {
            document.getElementById('selected_logo_url').value = '';
            const fileInput = document.getElementById('logo_file');
            if (fileInput) fileInput.value = '';
            document.getElementById('selectedLogoPreviewBox').style.display = 'none';
            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));
        }
    </script>
@endsection
