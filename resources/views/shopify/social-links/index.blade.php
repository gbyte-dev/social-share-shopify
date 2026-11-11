@extends('layouts.shopify')

@section('title', 'My Social Links - ' . $shop)
@section('header_title', 'My Social Links')
@section('header_subtitle', 'Configure and organize social media channels shown on your storefront')

@section('header_actions')
    <button type="button" onclick="openModal('create')" class="btn btn-primary btn-sm" style="padding: 0.4rem 0.75rem; font-size: 0.8125rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Add Social Link</span>
    </button>
@endsection

@section('content')
    <style>
        .top-actions .badge-live { display: none !important; }
        .sn-links-wrap { display: flex; flex-direction: column; gap: 0.875rem; }
        .sn-card { background: #ffffff; border: 1px solid #e1e3e5; border-radius: 8px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); }
        .sn-available-card { padding: 0.75rem 1rem; }
        .sn-available-title { font-size: 0.6875rem; font-weight: 700; color: #6d7175; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .sn-platform-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }
        .sn-chip-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.55rem; font-size: 0.75rem; font-weight: 600; color: #202223; background: #ffffff; border: 1px solid #dcdfe3; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; }
        .sn-chip-btn:hover { background: #f6f6f7; border-color: #c9cccf; color: #202223; }
        .sn-chip-custom { color: var(--primary, #4f46e5); background: #f5f7ff; border: 1px dashed #c7d2fe; }
        .sn-chip-custom:hover { background: #eef2ff; border-color: #a5b4fc; color: #3730a3; }
        .sn-channels-card { padding: 0.875rem 1rem; }
        .sn-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.625rem; border-bottom: 1px solid #f1f2f4; flex-wrap: wrap; gap: 0.5rem; }
        .sn-card-title { font-size: 0.875rem; font-weight: 700; color: #202223; margin: 0; }
        .sn-card-subtitle { font-size: 0.75rem; color: #6d7175; margin: 0.15rem 0 0 0; }
        .sn-table-responsive { overflow-x: auto; }
        .sn-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; text-align: left; }
        .sn-table th { padding: 0.45rem 0.5rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6d7175; border-bottom: 1px solid #e1e3e5; white-space: nowrap; }
        .sn-table td { padding: 0.55rem 0.5rem; border-bottom: 1px solid #f1f2f4; vertical-align: middle; color: #202223; }
        .sn-table tr:hover td { background: #fafbfb; }
        .sn-order-cell { font-weight: 600; color: #6d7175; font-size: 0.75rem; width: 50px; }
        .sn-platform-cell { display: flex; align-items: center; gap: 0.6rem; }
        .sn-platform-icon-wrap { width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; color: #ffffff; overflow: hidden; }
        .sn-platform-name-title { font-weight: 600; font-size: 0.8125rem; color: #202223; line-height: 1.2; display: flex; align-items: center; gap: 0.35rem; }
        .sn-custom-badge { font-size: 0.625rem; font-weight: 600; background: #e0e7ff; color: #4338ca; padding: 1px 5px; border-radius: 3px; }
        .sn-url-link { color: #2563eb; text-decoration: none; font-size: 0.75rem; max-width: 240px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle; }
        .sn-url-link:hover { text-decoration: underline; }
        .sn-badge-active { display: inline-block; font-size: 0.6875rem; font-weight: 600; color: #007f5f; background: #e4f8ed; border: 1px solid #b7ebd0; padding: 0.15rem 0.5rem; border-radius: 4px; }
        .sn-badge-inactive { display: inline-block; font-size: 0.6875rem; font-weight: 600; color: #5c5f62; background: #f1f2f4; border: 1px solid #e1e3e5; padding: 0.15rem 0.5rem; border-radius: 4px; }
        .sn-actions-cell { display: flex; align-items: center; justify-content: flex-end; gap: 0.35rem; }
        .sn-btn-action { font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; }
        .sn-btn-edit { background: #ffffff; border: 1px solid #dcdfe3; color: #202223; }
        .sn-btn-delete { background: #ffffff; border: 1px solid #fecdd3; color: #e11d48; }
        .sn-empty-wrap { text-align: center; padding: 2.25rem 1rem; }
        .sn-empty-icon { width: 44px; height: 44px; border-radius: 50%; background: #eef2ff; color: #4f46e5; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
        .sn-empty-title { font-size: 0.9375rem; font-weight: 700; margin-bottom: 0.35rem; color: #202223; }

        /* Unified Form Modal CSS */
        .sn-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; backdrop-filter: blur(2px); }
        .sn-modal-overlay.open { display: flex; opacity: 1; }
        .sn-modal-content { background: #fff; border-radius: 8px; width: 100%; max-width: 950px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 25px rgba(0,0,0,0.15); transform: translateY(20px); transition: transform 0.2s; }
        .sn-modal-overlay.open .sn-modal-content { transform: translateY(0); }
        
        .sn-modal-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e1e3e5; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .sn-modal-header h3 { font-size: 1rem; font-weight: 700; color: #202223; margin: 0; }
        .sn-modal-close { background: none; border: none; cursor: pointer; color: #6d7175; }
        
        #socialLinkForm { display: flex; flex-direction: column; flex: 1; min-height: 0; }
        .sn-modal-body { overflow-y: auto; flex: 1; display: grid; grid-template-columns: 1fr 340px; min-height: 0; }
        
        .sn-modal-left { padding: 1.25rem; border-right: 1px solid #e1e3e5; }
        .sn-modal-right { padding: 1.25rem; background: #fafbfb; position: sticky; top: 0; height: max-content; }
        .sn-modal-footer { padding: 1rem 1.25rem; border-top: 1px solid #e1e3e5; display: flex; justify-content: flex-end; gap: 0.5rem; background: #fff; border-radius: 0 0 8px 8px; flex-shrink: 0; }

        .sn-label { display: block; font-size: 0.75rem; font-weight: 600; color: #202223; margin-bottom: 0.35rem; margin-top: 0.8rem; }
        .sn-input { width: 100%; padding: 0.45rem 0.65rem; font-size: 0.8125rem; border: 1px solid #c9cccf; border-radius: 6px; box-sizing: border-box; }
        .sn-input:focus { border-color: var(--primary, #4f46e5); box-shadow: 0 0 0 1px var(--primary, #4f46e5); outline: none; }
        .sn-field-error { font-size: 0.6875rem; color: #d72c0d; margin-top: 0.25rem; font-weight: 500; display: none; }

        .sn-selected-platform-box { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.8rem; border: 1px solid #c9cccf; border-radius: 6px; background: #ffffff; cursor: pointer; transition: all 0.15s ease; margin-bottom: 0.25rem; }
        .sn-selected-platform-box:hover { border-color: var(--primary, #4f46e5); box-shadow: 0 0 0 1px var(--primary, #4f46e5); }
        #selectedPlatformVisual { display: flex; align-items: center; gap: 0.6rem; }

        .sn-platform-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem; margin-top: 0.5rem; }
        .sn-plat-card { border: 1px solid #e1e3e5; border-radius: 8px; padding: 0.75rem 0.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.4rem; cursor: pointer; background: #ffffff; transition: all 0.2s ease; text-align: center; }
        .sn-plat-card:hover:not(.disabled) { border-color: var(--primary, #4f46e5); box-shadow: 0 2px 6px rgba(79, 70, 229, 0.15); transform: translateY(-2px); }
        .sn-plat-card.disabled { opacity: 0.45; cursor: not-allowed; background: #fafbfb; border-color: #e1e3e5; }
        .sn-plat-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; color: #fff; overflow: hidden; }
        .sn-plat-icon svg { width: 18px; height: 18px; }
        .sn-plat-name { font-size: 0.6875rem; font-weight: 600; color: #202223; line-height: 1.2; word-break: break-word; }

        .candidate-card { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.5rem; border: 1px solid #e1e3e5; border-radius: 5px; background: #ffffff; cursor: pointer; font-size: 0.6875rem; }
        .candidate-card.active { border-color: var(--primary, #4f46e5); background: #eef2ff; box-shadow: 0 0 0 1px var(--primary, #4f46e5); }

        .spinner-sm { width: 11px; height: 11px; border: 2px solid #e1e3e5; border-top-color: var(--primary, #4f46e5); border-radius: 50%; display: inline-block; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .sn-preview-dock-item { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.8rem; background: #ffffff; border-radius: 9999px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); border: 1px solid #e1e3e5; }
        .sn-preview-icon-circle { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff; overflow: hidden; flex-shrink: 0; }
        .sn-preview-icon-circle svg { width: 14px; height: 14px; }
        .sn-preview-label-text { font-size: 0.75rem; font-weight: 600; color: #202223; white-space: nowrap; }

        @media (max-width: 768px) {
            .sn-modal-body { grid-template-columns: 1fr; }
            .sn-modal-right { border-top: 1px solid #e1e3e5; position: static; }
        }
    </style>

    <div class="sn-links-wrap">
        @php
            $configuredBuiltInPlatforms = $links->where('platform', '!=', 'other')->pluck('platform')->toArray();
        @endphp
        @if(count($availablePlatforms) > 0)
            <div class="sn-card sn-available-card">
                <div class="sn-available-title">Available Platforms Ready to Connect</div>
                <div class="sn-platform-chips">
                    @foreach($availablePlatforms as $platKey => $plat)
                        @if($platKey !== 'other' && !in_array($platKey, $configuredBuiltInPlatforms))
                            <button type="button" onclick="openModal('create', '{{ $platKey }}')" class="sn-chip-btn">
                                <span style="color: {{ $plat['brand_color'] }}; display: flex; align-items: center;">
                                    {!! \App\Services\SocialPlatformService::iconSvg($platKey, 14) !!}
                                </span>
                                <span>+ Add {{ $plat['name'] }}</span>
                            </button>
                        @endif
                    @endforeach
                    <button type="button" onclick="openCustomAppModal()" class="sn-chip-btn sn-chip-custom">
                        <span style="display: flex; align-items: center;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg></span>
                        <span>+ Create Custom App</span>
                    </button>
                </div>
            </div>
        @endif

        <div class="sn-card sn-channels-card">
            <div class="sn-card-head">
                <div>
                    <h3 class="sn-card-title">Configured Channels ({{ $links->count() }})</h3>
                    <p class="sn-card-subtitle">Active links appear in the floating widget and storefront extensions.</p>
                </div>
            </div>

            @if($links->isEmpty())
                <div class="sn-empty-wrap">
                    <div class="sn-empty-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div>
                    <h4 class="sn-empty-title">No social links configured yet</h4>
                    <button type="button" onclick="openModal('create')" class="btn btn-primary btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Add First Social Link</span>
                    </button>
                </div>
            @else
                <div class="sn-table-responsive">
                    <table class="sn-table">
                        <thead>
                            <tr>
                                <th style="width: 48px;">Order</th>
                                <th>Platform</th>
                                <th>Display Label</th>
                                <th>Profile / Share URL</th>
                                <th style="text-align: center; width: 85px;">Status</th>
                                <th style="text-align: right; width: 110px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($links as $link)
                                <tr>
                                    <td class="sn-order-cell">#{{ $link->sort_order }}</td>
                                    <td>
                                        <div class="sn-platform-cell">
                                            <div class="sn-platform-icon-wrap" style="background: {{ $link->brand_color }};">{!! $link->icon_html !!}</div>
                                            <div>
                                                <div class="sn-platform-name-title">
                                                    <span>{{ $link->platform_name }}</span>
                                                    @if($link->isCustom()) <span class="sn-custom-badge">Custom</span> @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight: 500;">{{ $link->label ?: $link->platform_name }}</td>
                                    <td><a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="sn-url-link">{{ $link->url }}</a></td>
                                    <td style="text-align: center;">
                                        <form action="{{ route('shopify.social-links.toggle', $link->id) }}" method="POST" style="display: inline;">
                                            @csrf @method('PATCH')
                                            <button type="submit" style="background: none; border: none; cursor: pointer;" title="Toggle">
                                                @if($link->is_active) <span class="sn-badge-active">Active</span> @else <span class="sn-badge-inactive">Inactive</span> @endif
                                            </button>
                                        </form>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="sn-actions-cell">
                                            @php
                                                $editData = json_encode([
                                                    'id' => $link->id,
                                                    'platform' => $link->platform,
                                                    'url' => $link->url,
                                                    'label' => $link->label,
                                                    'sort_order' => $link->sort_order,
                                                    'is_active' => $link->is_active,
                                                    'custom_platform_name' => $link->custom_platform_name,
                                                    'official_website_url' => $link->official_website_url,
                                                    'alt_text' => $link->alt_text,
                                                    'logo_small_url' => $link->logo_small_url,
                                                    'brand_color' => $link->brand_color,
                                                ]);
                                            @endphp
                                            <button type="button" class="sn-btn-action sn-btn-edit" onclick="openModal('edit', {{ $editData }})">Edit</button>
                                            <form action="{{ route('shopify.social-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this link?');" style="display: inline;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="sn-btn-action sn-btn-delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Unified Create/Edit Modal -->
    <div class="sn-modal-overlay" id="socialLinkModal">
        <div class="sn-modal-content">
            <div class="sn-modal-header">
                <h3 id="modalTitle">Add Social Link</h3>
                <button type="button" class="sn-modal-close" onclick="closeModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            
            <form id="socialLinkForm" onsubmit="submitForm(event)" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" id="linkId" value="">

                <div class="sn-modal-body">
                    <div class="sn-modal-left">
                        <label class="sn-label" style="margin-top: 0;">Social Media Platform <span style="color: #d72c0d;">*</span></label>
                        <div id="selectedPlatformBox" class="sn-selected-platform-box" onclick="openPlatformPicker()">
                            <div id="selectedPlatformVisual"></div>
                            <button type="button" class="btn btn-secondary btn-sm" style="pointer-events: none; font-size: 0.72rem; padding: 0.3rem 0.6rem;">Change</button>
                        </div>
                        <div class="sn-field-error" id="err_platform"></div>

                        <select id="platform" name="platform" style="display: none;" required>
                            <option value="">-- Choose --</option>
                            @foreach($allPlatforms as $key => $plat)
                                @if($key !== 'other')
                                    <option value="{{ $key }}">{{ $plat['name'] }}</option>
                                @endif
                            @endforeach
                            <option value="other">Other / Custom</option>
                        </select>

                        <div id="customAppHiddenFields" style="display: none;">
                            <input type="text" id="custom_platform_name" name="custom_platform_name">
                            <input type="text" id="official_website_url" name="official_website_url">
                            <input type="hidden" name="selected_logo_url" id="selected_logo_url">
                        </div>

                        <label class="sn-label">Destination or Profile URL <span style="color: #d72c0d;">*</span></label>
                        <input type="text" id="url" name="url" placeholder="https://..." class="sn-input" oninput="updatePreview()" required>
                        <div class="sn-field-error" id="err_url"></div>

                        <label class="sn-label">Custom Display Label <span class="sn-label-opt">(Optional)</span></label>
                        <input type="text" id="label" name="label" class="sn-input" oninput="updatePreview()">
                        <div class="sn-field-error" id="err_label"></div>

                        <div style="display: flex; gap: 1rem;">
                            <div style="flex: 1;">
                                <label class="sn-label">Display Order</label>
                                <input type="number" id="sort_order" name="sort_order" class="sn-input" value="{{ $nextOrder ?? 0 }}">
                                <div class="sn-field-error" id="err_sort_order"></div>
                            </div>
                            <div style="flex: 1;">
                                <label class="sn-label">Status</label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" checked style="accent-color: var(--primary, #4f46e5); width: 16px; height: 16px;">
                                    <span style="font-size: 0.8125rem; font-weight: 500;">Enable on Storefront</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="sn-modal-right">
                        <label class="sn-label" style="margin-top: 0;">Storefront Live Preview</label>
                        <p style="font-size: 0.75rem; color: #6d7175; margin-bottom: 1.25rem;">This represents exactly how the link will appear.</p>
                        
                        <div style="display: flex; justify-content: center; padding: 2rem 0;">
                            <div class="sn-preview-dock-item">
                                <div class="sn-preview-icon-circle" id="prev_icon" style="background: #e1e3e5;"></div>
                                <span class="sn-preview-label-text" id="prev_label">Select a platform</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sn-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Save Social Link</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Step 1: Platform Selection Modal -->
    <div class="sn-modal-overlay" id="platformPickerModal" style="z-index: 1050;">
        <div class="sn-modal-content" style="max-width: 650px;">
            <div class="sn-modal-header">
                <h3>Select a Platform</h3>
                <button type="button" class="sn-modal-close" onclick="closePlatformPicker()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="sn-modal-body" style="display: block; padding: 1.25rem; overflow-y: auto;">
                <input type="text" id="platformSearch" placeholder="Search platforms..." class="sn-input" style="margin-bottom: 1rem; padding: 0.6rem 0.8rem;" onkeyup="filterPlatforms()">
                
                <div class="sn-platform-grid" id="platformVisualGrid">
                    @foreach($allPlatforms as $key => $plat)
                        @if($key !== 'other')
                            <div class="sn-plat-card {{ in_array($key, $configuredBuiltInPlatforms) ? 'disabled' : '' }}" data-platform="{{ $key }}" data-name="{{ strtolower($plat['name']) }}" onclick="selectPlatformCard('{{ $key }}', '{{ $plat['name'] }}', '{{ $plat['brand_color'] ?? '#4f46e5' }}', {{ in_array($key, $configuredBuiltInPlatforms) ? 'true' : 'false' }})">
                                <div class="sn-plat-icon" style="background: {{ $plat['brand_color'] ?? '#4f46e5' }}">
                                    {!! \App\Services\SocialPlatformService::iconSvg($key, 18) !!}
                                </div>
                                <span class="sn-plat-name">{{ $plat['name'] }}</span>
                            </div>
                        @endif
                    @endforeach
                    <div class="sn-plat-card" data-platform="other" data-name="custom platform app" onclick="openCustomAppModal()">
                        <div class="sn-plat-icon" style="background: #fafbfb; color: #4f46e5; border: 1px dashed #c7d2fe;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                        </div>
                        <span class="sn-plat-name">+ Create Custom App</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Dedicated Custom App Registration Modal -->
    <div class="sn-modal-overlay" id="customAppModal" style="z-index: 1100;">
        <div class="sn-modal-content" style="max-width: 500px;">
            <div class="sn-modal-header">
                <h3>Create Custom App</h3>
                <button type="button" class="sn-modal-close" onclick="closeCustomAppModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="customAppForm" onsubmit="saveCustomApp(event)">
                <div style="padding: 1.25rem; overflow-y: auto;">
                    <label class="sn-label" style="margin-top: 0;">App / Platform Name <span style="color: #d72c0d;">*</span></label>
                    <input type="text" id="reg_custom_name" placeholder="e.g. Kick, Rumble, Telegram" class="sn-input" oninput="checkDuplicateCustomApp()" required>
                    <div class="sn-field-error" id="err_reg_custom_platform_name"></div>

                    <label class="sn-label">Website URL <span class="sn-label-opt">(Aids logo find)</span></label>
                    <input type="text" id="reg_custom_website" placeholder="e.g. https://kick.com" class="sn-input" oninput="debouncedLogoDiscovery()">

                    <label class="sn-label" style="margin-top: 1rem;">Platform Logo <span style="color: #d72c0d;">*</span></label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <button type="button" onclick="triggerLogoDiscovery(false)" class="btn btn-secondary btn-sm" style="font-size: 0.6875rem; padding: 0.2rem 0.5rem;">Find Logo Auto</button>
                        <span id="discoveryStatus" style="display: none; align-items: center; gap: 0.35rem; font-size: 0.65rem; color: #4f46e5;"><span class="spinner-sm"></span> Searching...</span>
                    </div>

                    <div id="candidatesContainer" style="display: none; margin-bottom: 0.5rem; padding: 0.5rem; background: #fff; border: 1px solid #e1e3e5; border-radius: 6px;">
                        <div style="font-size: 0.65rem; font-weight: 700; color: #6d7175; margin-bottom: 0.3rem;">Discovered (Click to Select)</div>
                        <div id="candidatesGrid" style="display: flex; flex-wrap: wrap; gap: 0.4rem;"></div>
                    </div>

                    <input type="file" id="reg_logo_file" accept=".png,.jpg,.jpeg,.svg" style="font-size: 0.72rem; margin-bottom: 0.5rem;" onchange="handleRegFileUpload(this)">
                    
                    <div id="regLogoPreviewBox" style="display: none; align-items: center; gap: 0.6rem; padding: 0.4rem; border: 1px solid #e1e3e5; border-radius: 6px; background: #fff;">
                        <img id="regLogoPreviewImg" src="" style="width: 20px; height: 20px; object-fit: contain;">
                        <span style="font-size: 0.72rem; flex:1;" id="regLogoSourceTitle"></span>
                        <button type="button" onclick="clearRegLogo()" style="color: #d72c0d; background: none; border: none; font-size: 0.6875rem; cursor: pointer;">✕</button>
                    </div>
                    <div class="sn-field-error" id="err_reg_logo_file"></div>
                </div>
                <div class="sn-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCustomAppModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="customAppSubmitBtn">Done & Select</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Client-side Logic -->
    <script>
        const builtinPlatforms = {
            @foreach($allPlatforms as $k => $p)
                '{{ $k }}': { name: @json($p['name']), color: @json($p['brand_color'] ?? '#4f46e5'), placeholder: @json($p['placeholder']), icon: {!! json_encode(\App\Services\SocialPlatformService::iconSvg($k, 16)) !!} },
            @endforeach
        };

        // Load Global Custom Apps from Database via PHP
        let customAppsRegistry = [
            @foreach($globalCustomApps ?? [] as $gApp)
                { name: @json($gApp->name), website: @json($gApp->website_url), logo: @json($gApp->logo_url) },
            @endforeach
        ];

        const modal = document.getElementById('socialLinkModal');
        const pickerModal = document.getElementById('platformPickerModal');
        const customAppModal = document.getElementById('customAppModal');
        const form = document.getElementById('socialLinkForm');
        const selPlatform = document.getElementById('platform');

        function renderCustomAppsInGrid() {
            document.querySelectorAll('.sn-plat-card.dynamic-custom').forEach(el => el.remove());
            const grid = document.getElementById('platformVisualGrid');
            const customCreateCard = grid.querySelector('[data-platform="other"]');

            const configuredCustoms = @json($configuredCustomNames ?? []);
            const seenNames = new Set();

            customAppsRegistry.forEach(app => {
                const lowerName = app.name.toLowerCase();
                if (seenNames.has(lowerName)) return;
                seenNames.add(lowerName);

                const isAlreadyAdded = configuredCustoms.includes(lowerName);

                const card = document.createElement('div');
                card.className = 'sn-plat-card dynamic-custom' + (isAlreadyAdded ? ' disabled' : '');
                card.setAttribute('data-platform', 'other');
                card.setAttribute('data-name', lowerName);
                
                if (!isAlreadyAdded) {
                    card.onclick = () => selectCustomAppCard(app);
                }

                card.innerHTML = `
                    <div class="sn-plat-icon" style="background: #4f46e5;">
                        <img src="${app.logo}" style="width:16px;height:16px;object-fit:contain;">
                    </div>
                    <span class="sn-plat-name">${app.name}</span>
                `;
                grid.insertBefore(card, customCreateCard);
            });
        }

        function openPlatformPicker() {
            renderCustomAppsInGrid();
            document.getElementById('platformSearch').value = '';
            filterPlatforms();
            pickerModal.classList.add('open');
        }

        function closePlatformPicker() { pickerModal.classList.remove('open'); }
        
        function openCustomAppModal() { 
            closePlatformPicker(); 
            document.getElementById('customAppForm').reset();
            document.getElementById('regLogoPreviewBox').style.display = 'none';
            document.getElementById('candidatesContainer').style.display = 'none';
            document.getElementById('err_reg_custom_platform_name').style.display = 'none';
            const submitBtn = document.getElementById('customAppSubmitBtn');
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
            customAppModal.classList.add('open'); 
        }

        function closeCustomAppModal() { customAppModal.classList.remove('open'); }

        function filterPlatforms() {
            let input = document.getElementById('platformSearch').value.toLowerCase();
            document.querySelectorAll('#platformVisualGrid .sn-plat-card').forEach(card => {
                let name = card.getAttribute('data-name');
                card.style.display = name.includes(input) ? 'flex' : 'none';
            });
        }

        // Real-time duplicate check on input
       function checkDuplicateCustomApp() {
            const nameInput = document.getElementById('reg_custom_name');
            const name = nameInput.value.trim().toLowerCase();
            const errDiv = document.getElementById('err_reg_custom_platform_name');
            const submitBtn = document.getElementById('customAppSubmitBtn');

            if (!name) {
                if (errDiv) { errDiv.style.display = 'none'; }
                nameInput.style.borderColor = '#c9cccf';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = '1'; submitBtn.style.cursor = 'pointer'; }
                return;
            }

            // 1. Check if name exists in custom apps registry
            const existsInCustom = customAppsRegistry.some(app => app.name.toLowerCase() === name);

            // 2. Check if name matches any built-in platform name or key (e.g. youtube, instagram)
            let existsInBuiltin = false;
            for (const key in builtinPlatforms) {
                if (key === name || builtinPlatforms[key].name.toLowerCase() === name) {
                    existsInBuiltin = true;
                    break;
                }
            }

            if (existsInCustom || existsInBuiltin) {
                if (errDiv) {
                    errDiv.textContent = 'This application is already available. Please use the existing App';
                    errDiv.style.display = 'block';
                }
                nameInput.style.borderColor = '#d72c0d';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';
                }
            } else {
                if (errDiv) { errDiv.style.display = 'none'; }
                nameInput.style.borderColor = '#c9cccf';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                }
            }
        }

        function selectPlatformCard(key, name, color, isDisabled = false) {
            if (isDisabled) return;
            
            // Fix: Hidden select element ki value set karein taaki backend par platform name jaye
            const selectEl = document.getElementById('platform');
            if (selectEl) {
                selectEl.value = key;
            }

            document.getElementById('customAppHiddenFields').innerHTML = `
                <input type="text" id="custom_platform_name" name="custom_platform_name" value="">
                <input type="text" id="official_website_url" name="official_website_url" value="">
                <input type="hidden" name="selected_logo_url" id="selected_logo_url" value="">
            `;

            const visualBox = document.getElementById('selectedPlatformVisual');
            visualBox.innerHTML = `
                <div class="sn-plat-icon" style="background: ${color}; color: #fff; width: 28px; height: 28px;">
                    ${builtinPlatforms[key].icon}
                </div>
                <span style="font-weight: 600; font-size: 0.8125rem; color: #202223;">${name}</span>
            `;

            closePlatformPicker();
            updatePreview();
        }

        function selectCustomAppCard(app) {
            // Main platform select ko 'other' set karein
            const selectEl = document.getElementById('platform');
            if (selectEl) {
                selectEl.value = 'other';
            }
            
            // Hidden fields mein custom app ki sari details properly assign karein
            document.getElementById('customAppHiddenFields').innerHTML = `
                <input type="text" id="custom_platform_name" name="custom_platform_name" value="${app.name}">
                <input type="text" id="official_website_url" name="official_website_url" value="${app.website || ''}">
                <input type="hidden" name="selected_logo_url" id="selected_logo_url" value="${app.logo || ''}">
            `;

            const visualBox = document.getElementById('selectedPlatformVisual');
            visualBox.innerHTML = `
                <div class="sn-plat-icon" style="background: #4f46e5; width: 28px; height: 28px;">
                    <img src="${app.logo}" style="width:16px;height:16px;object-fit:contain;">
                </div>
                <div>
                    <span style="font-weight: 600; font-size: 0.8125rem; color: #202223; display: block;">${app.name}</span>
                    <span style="font-size: 0.65rem; color: #4338ca; background: #e0e7ff; padding: 1px 4px; border-radius: 3px;">Custom App</span>
                </div>
            `;

            closePlatformPicker();
            updatePreview(app.logo, app.name);
        }


        let regUploadedFile = null;
        let regSelectedLogoUrl = '';
        function handleRegFileUpload(input) {
            if (input.files && input.files[0]) {
                regUploadedFile = input.files[0];
                regSelectedLogoUrl = '';
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('regLogoPreviewImg').src = e.target.result;
                    document.getElementById('regLogoSourceTitle').textContent = regUploadedFile.name;
                    document.getElementById('regLogoPreviewBox').style.display = 'flex';
                };
                reader.readAsDataURL(regUploadedFile);
            }
        }

        function clearRegLogo() {
            regUploadedFile = null;
            regSelectedLogoUrl = '';
            document.getElementById('reg_logo_file').value = '';
            document.getElementById('regLogoPreviewBox').style.display = 'none';
        }

        async function saveCustomApp(e) {
            e.preventDefault();
            const nameInput = document.getElementById('reg_custom_name');
            const name = nameInput.value.trim();
            const website = document.getElementById('reg_custom_website').value.trim();
            const previewImg = document.getElementById('regLogoPreviewImg').src;
            const errDiv = document.getElementById('err_reg_custom_platform_name');

            // Final double check on submit
            if (customAppsRegistry.some(app => app.name.toLowerCase() === name.toLowerCase())) {
                if (errDiv) {
                    errDiv.textContent = 'This application already exists. Kindly manage it from Add Social Link button.';
                    errDiv.style.display = 'block';
                }
                return;
            }

            let finalLogo = regSelectedLogoUrl || (document.getElementById('regLogoPreviewBox').style.display === 'flex' ? previewImg : null);

            if (!name) { alert('Please enter App Name'); return; }
            if (!finalLogo) { alert('Please select or upload a logo for this custom app.'); return; }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('website_url', website);
            if (regUploadedFile) {
                formData.append('logo_file', regUploadedFile);
            } else if (finalLogo) {
                formData.append('selected_logo_url', finalLogo);
            }

            const queryParams = new URLSearchParams(window.location.search);
            if (queryParams.has('host')) formData.append('host', queryParams.get('host'));
            if (queryParams.has('shop')) formData.append('shop', queryParams.get('shop'));

            try {
                const res = await fetch('{{ route("shopify.social-links.store-global-custom-app") }}?' + queryParams.toString(), {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok) {
                    if (data.errors && data.errors.name) {
                        if(errDiv) { errDiv.textContent = data.errors.name[0]; errDiv.style.display = 'block'; nameInput.style.borderColor = '#d72c0d'; }
                    } else {
                        alert(data.message || 'Failed to save custom app.');
                    }
                    return;
                }

                if (data.success && data.app) {
                    if (!customAppsRegistry.some(a => a.name.toLowerCase() === data.app.name.toLowerCase())) {
                        customAppsRegistry.push(data.app);
                    }
                    closeCustomAppModal();
                    selectCustomAppCard(data.app);
                }
            } catch (err) {
                alert('A network error occurred while saving the custom app.');
            }
        }

        function resetPlatformVisual() {
            const visualBox = document.getElementById('selectedPlatformVisual');
            visualBox.innerHTML = `
                <div class="sn-plat-icon" style="background: #f1f2f4; color: #6d7175; width: 28px; height: 28px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                </div>
                <span style="font-weight: 500; font-size: 0.8125rem; color: #6d7175;">Click to select a platform...</span>
            `;
            if (selPlatform) selPlatform.value = "";
            updatePreview();
        }

        function openModal(mode, data = null) {
            clearErrors();
            form.reset();
            resetPlatformVisual();
            
            if (mode === 'create') {
                document.getElementById('modalTitle').textContent = 'Add Social Link';
                document.getElementById('formMethod').value = 'POST';
                form.action = '{{ route("shopify.social-links.store") }}';
                
                if (typeof data === 'string') {
                    selectPlatformCard(data, builtinPlatforms[data].name, builtinPlatforms[data].color);
                }
            } else {
                document.getElementById('modalTitle').textContent = 'Edit Social Link';
                document.getElementById('formMethod').value = 'PUT';
                
                const updateUrl = '{{ route("shopify.social-links.update", ["id" => "REPLACE_ID"]) }}';
                form.action = updateUrl.replace('REPLACE_ID', data.id);
                
                if (data.platform === 'other') {
                    selectCustomAppCard({ name: data.custom_platform_name, website: data.official_website_url, logo: data.logo_small_url });
                } else {
                    selectPlatformCard(data.platform, builtinPlatforms[data.platform].name, builtinPlatforms[data.platform].color);
                }
                
                document.getElementById('url').value = data.url;
                document.getElementById('label').value = data.label || '';
                document.getElementById('sort_order').value = data.sort_order;
                document.getElementById('is_active').checked = data.is_active;
            }

            modal.classList.add('open');
        }

        function closeModal() {
            modal.classList.remove('open');
            closePlatformPicker();
            closeCustomAppModal();
        }

        function clearErrors() {
            document.querySelectorAll('.sn-field-error').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
            document.querySelectorAll('.sn-input, .sn-selected-platform-box').forEach(el => el.style.borderColor = '#c9cccf');
        }

        function showErrors(errors) {
            clearErrors();
            for (const [key, msgs] of Object.entries(errors)) {
                const errDiv = document.getElementById('err_' + key);
                const input = document.getElementById(key);
                if (errDiv) { errDiv.textContent = msgs[0]; errDiv.style.display = 'block'; }
                if (input) { input.style.borderColor = '#d72c0d'; }
                if (key === 'platform') { document.getElementById('selectedPlatformBox').style.borderColor = '#d72c0d'; }
            }
        }

        function updatePreview(customLogo = null, customName = null) {
            const val = selPlatform.value;
            const label = document.getElementById('label').value.trim();
            const iconWrap = document.getElementById('prev_icon');
            const labelTxt = document.getElementById('prev_label');
            
            if (val === 'other') {
                const hiddenName = document.querySelector('input[name="custom_platform_name"]');
                const displayName = label || customName || (hiddenName ? hiddenName.value : 'Custom Platform');
                labelTxt.textContent = displayName;
                
                const activeLogo = customLogo || (hiddenName ? document.querySelector('input[name="selected_logo_url"]').value : null);

                if (activeLogo) {
                    iconWrap.style.background = '#4f46e5';
                    iconWrap.innerHTML = `<img src="${activeLogo}" style="width:16px;height:16px;object-fit:contain;">`;
                } else {
                    iconWrap.style.background = '#4f46e5';
                    iconWrap.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>';
                }
            } else if (builtinPlatforms[val]) {
                labelTxt.textContent = label || builtinPlatforms[val].name;
                iconWrap.style.background = builtinPlatforms[val].color;
                iconWrap.innerHTML = builtinPlatforms[val].icon;
            } else {
                labelTxt.textContent = 'Select a platform';
                iconWrap.style.background = '#e1e3e5';
                iconWrap.innerHTML = '';
            }
        }

        async function submitForm(e) {
            e.preventDefault();
            if (!selPlatform.value) {
                document.getElementById('selectedPlatformBox').style.borderColor = '#d72c0d';
                document.getElementById('err_platform').textContent = "Please select a platform.";
                document.getElementById('err_platform').style.display = "block";
                return;
            }

            const btn = document.getElementById('btnSubmit');
            const originalText = btn.textContent;
            btn.textContent = 'Saving...';
            btn.disabled = true;
            clearErrors();

            const formData = new FormData(form);
            if (regUploadedFile && selPlatform.value === 'other') {
                formData.append('logo_file', regUploadedFile);
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('host')) formData.append('host', urlParams.get('host'));
            if (urlParams.has('shop')) formData.append('shop', urlParams.get('shop'));

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                
                if (!res.ok) {
                    if (res.status === 422 && data.errors) {
                        showErrors(data.errors);
                    } else {
                        alert(data.message || 'An error occurred while saving.');
                    }
                } else if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                alert('A network error occurred.');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }

        let discTimer;
        function debouncedLogoDiscovery() { clearTimeout(discTimer); discTimer = setTimeout(() => { if(document.getElementById('reg_custom_name').value.trim().length >= 2) triggerLogoDiscovery(true); }, 650); }
        
        async function triggerLogoDiscovery(silent = false) {
            const name = document.getElementById('reg_custom_name').value.trim();
            const web = document.getElementById('reg_custom_website').value.trim();
            if (!name && !web) { if(!silent) alert('Please enter App Name or Website URL.'); return; }

            document.getElementById('discoveryStatus').style.display = 'inline-flex';
            document.getElementById('candidatesContainer').style.display = 'none';

            const queryParams = new URLSearchParams(window.location.search);
            try {
                const res = await fetch('{{ route("shopify.social-links.discover-logos") }}?' + queryParams.toString(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ name, website_url: web, shop: queryParams.get('shop') })
                });
                const data = await res.json();
                document.getElementById('discoveryStatus').style.display = 'none';

                if (data.success && data.candidates && data.candidates.length > 0) {
                    document.getElementById('candidatesContainer').style.display = 'block';
                    const grid = document.getElementById('candidatesGrid');
                    grid.innerHTML = '';
                    data.candidates.forEach((cand, idx) => {
                        const card = document.createElement('div');
                        card.className = 'candidate-card' + (idx === 0 ? ' active' : '');
                        card.innerHTML = `<img src="${cand.url}" style="width:16px;height:16px;object-fit:contain;"><div><div style="font-weight:600;color:#202223;">${cand.source_name}</div></div>`;
                        card.onclick = () => {
                            regUploadedFile = null;
                            document.getElementById('reg_logo_file').value = '';
                            regSelectedLogoUrl = cand.url;
                            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('active'));
                            card.classList.add('active');
                            
                            document.getElementById('regLogoPreviewImg').src = cand.url;
                            document.getElementById('regLogoSourceTitle').textContent = cand.source_name;
                            document.getElementById('regLogoPreviewBox').style.display = 'flex';
                        };
                        grid.appendChild(card);
                    });
                    if (data.preselected) grid.children[0].click();
                } else if (!silent) alert(data.message || 'No logos found automatically.');
            } catch (err) {
                document.getElementById('discoveryStatus').style.display = 'none';
                if (!silent) alert('Discovery request failed. Upload a logo manually.');
            }
        }
    </script>
@endsection