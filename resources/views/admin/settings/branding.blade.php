@extends('admin.layout')

@section('title', 'Branding Settings')
@section('heading', 'Website Branding')

@section('content')

@php
    $logoUrl = $brandLogoUrl ?? \App\Models\SiteSetting::getAssetUrl('brand_logo');
    $faviconUrl = $brandFaviconUrl ?? \App\Models\SiteSetting::getAssetUrl('brand_favicon');
@endphp

<style>
    .branding-page {
        max-width: 1160px;
        margin: 0 auto;
        color: #202223;
        box-sizing: border-box;
    }

    .branding-header {
        margin-bottom: 24px;
    }

    .branding-header h2 {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 6px;
        letter-spacing: -0.5px;
    }

    .branding-header p {
        color: #6d7175;
        font-size: 13px;
        margin: 0;
    }

    /* Alerts */
    .alert-success,
    .alert-error {
        padding: 13px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #eaf6f0;
        color: #087a53;
        border: 1px solid #c6ead8;
    }

    .alert-error {
        background: #fff1f0;
        color: #c0392b;
        border: 1px solid #f4d0cc;
    }

    /* Two-Column Responsive Grid */
    .branding-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        align-items: flex-start;
        margin-bottom: 24px;
    }

    .branding-column {
        display: flex;
        flex-direction: column;
        gap: 24px;
        min-width: 0;
    }

    /* Cards */
    .branding-card {
        background: #ffffff;
        border: 1px solid #e3e5e7;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        box-sizing: border-box;
    }

    .card-heading {
        padding: 18px 22px;
        border-bottom: 1px solid #eceeef;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-heading-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #eaf5f0;
        color: #008060;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .card-heading-icon svg {
        width: 20px;
        height: 20px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .card-heading h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 3px;
        color: #202223;
    }

    .card-heading p {
        color: #73777a;
        font-size: 12px;
        margin: 0;
    }

    .card-body {
        padding: 22px;
    }

    /* Forms */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 650;
        margin-bottom: 7px;
        color: #303438;
    }

    .form-control {
        width: 100%;
        min-height: 42px;
        padding: 10px 13px;
        border: 1px solid #c9cccf;
        border-radius: 7px;
        font-size: 13px;
        color: #202223;
        background: #fff;
        box-sizing: border-box;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        font-family: inherit;
    }

    .form-control:focus {
        border-color: #008060;
        box-shadow: 0 0 0 3px rgba(0, 128, 96, .10);
    }

    .form-help {
        display: block;
        margin-top: 6px;
        color: #8c9196;
        font-size: 11px;
        line-height: 1.45;
    }

    /* Upload Area */
    .upload-area {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 16px;
        border: 1px dashed #c9cccf;
        border-radius: 9px;
        background: #fafbfb;
        box-sizing: border-box;
    }

    .preview-box {
        width: 82px;
        height: 82px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #e3e5e7;
        border-radius: 9px;
        padding: 8px;
        box-sizing: border-box;
    }

    .preview-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .favicon-box {
        width: 64px;
        height: 64px;
    }

    .upload-content {
        flex: 1;
        min-width: 0;
    }

    .upload-content strong {
        display: block;
        font-size: 13px;
        margin-bottom: 4px;
        color: #202223;
    }

    .upload-content p {
        font-size: 11px;
        color: #73777a;
        margin: 0 0 10px;
    }

    .file-input {
        display: block;
        width: 100%;
        font-size: 12px;
        color: #555;
    }

    .file-input::file-selector-button {
        padding: 7px 12px;
        margin-right: 10px;
        border: 1px solid #c9cccf;
        border-radius: 6px;
        background: #fff;
        color: #303438;
        font-weight: 600;
        font-size: 11px;
        cursor: pointer;
        transition: all .2s;
    }

    .file-input::file-selector-button:hover {
        background: #f6f6f7;
        border-color: #008060;
        color: #008060;
    }

    /* Live Branding Preview Component */
    .mock-browser {
        border: 1px solid #d2d5d8;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    .mock-browser-topbar {
        background: #eef0f2;
        padding: 10px 14px 0;
        border-bottom: 1px solid #d8dbdf;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mock-browser-dots {
        display: flex;
        gap: 6px;
        padding-bottom: 10px;
    }

    .mock-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    .mock-dot.red { background: #ff5f56; }
    .mock-dot.yellow { background: #ffbd2e; }
    .mock-dot.green { background: #27c93f; }

    .mock-tab {
        background: #ffffff;
        border-radius: 8px 8px 0 0;
        padding: 7px 12px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        font-weight: 600;
        color: #202223;
        max-width: 240px;
        border: 1px solid #d8dbdf;
        border-bottom: none;
        box-shadow: 0 -1px 2px rgba(0,0,0,0.02);
    }

    .mock-tab-favicon {
        width: 15px;
        height: 15px;
        object-fit: contain;
        border-radius: 2px;
        flex-shrink: 0;
    }

    .mock-tab-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mock-tab-close {
        color: #8c9196;
        font-size: 13px;
        margin-left: 4px;
        cursor: default;
    }

    .mock-navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        background: #ffffff;
        border-bottom: 1px solid #f1f2f3;
        gap: 12px;
    }

    .mock-brand-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .mock-logo {
        max-height: 28px;
        max-width: 90px;
        object-fit: contain;
    }

    .mock-brand-text {
        font-size: 14px;
        font-weight: 700;
        color: #202223;
        letter-spacing: -0.3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mock-nav-links {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 11px;
        color: #6d7175;
        font-weight: 500;
        flex-shrink: 0;
    }

    .mock-nav-cta {
        background: #008060;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        padding: 5px 11px;
        border-radius: 5px;
        display: inline-block;
    }

    .preview-legend {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 10px;
        font-size: 11px;
        color: #8c9196;
    }

    .preview-legend span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Save Footer Card */
    .branding-footer-card {
        margin-top: 0;
    }

    .branding-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 18px 24px;
        background: #fafbfb;
        border-top: 1px solid #eceeef;
    }

    .footer-note {
        color: #73777a;
        font-size: 12px;
        line-height: 1.4;
    }

    .btn-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 22px;
        border: 0;
        border-radius: 7px;
        background: #008060;
        color: #fff;
        font-size: 13px;
        font-weight: 650;
        cursor: pointer;
        transition: background .2s, box-shadow .2s;
        font-family: inherit;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-save:hover {
        background: #006e52;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }

    .btn-save:active {
        transform: translateY(1px);
    }

    .btn-save svg {
        width: 15px;
        height: 15px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    /* Responsive Breakpoints */
    @media (max-width: 960px) {
        .branding-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    @media (max-width: 600px) {
        .branding-header h2 {
            font-size: 19px;
        }

        .card-heading,
        .card-body,
        .branding-footer {
            padding: 16px;
        }

        .upload-area {
            flex-direction: column;
            align-items: flex-start;
        }

        .branding-footer {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .btn-save {
            width: 100%;
        }

        .mock-nav-links {
            display: none;
        }
    }
</style>

<div class="branding-page">

    <div class="branding-header">
        <h2>Brand Identity</h2>
        <p>Customize your website's name, logo and browser tab icon.</p>
    </div>

    @if(session('success'))
    <div class="alert-success">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="alert-error">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form action="{{ route('admin.settings.branding.update') }}"
        method="POST"
        enctype="multipart/form-data">

        @csrf
        @method('PUT')

        <div class="branding-grid">

            {{-- LEFT COLUMN: Website Identity (Name & Logo) --}}
            <div class="branding-column">
                <div class="branding-card" id="website-identity-card">
                    <div class="card-heading">
                        <div class="card-heading-icon">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3>Website Identity</h3>
                            <p>Primary brand name and storefront logo used across your application.</p>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- Website / Brand Name --}}
                        <div class="form-group">
                            <label class="form-label" for="brand_name">
                                Website Name
                            </label>

                            <input
                                type="text"
                                id="brand_name"
                                name="brand_name"
                                class="form-control"
                                value="{{ old('brand_name', $brandName) }}"
                                maxlength="100"
                                placeholder="Enter your brand name"
                                required>

                            <small class="form-help">
                                Displayed in your website navbar, admin sidebar, and browser titles.
                            </small>
                        </div>

                        {{-- Website Logo --}}
                        <div class="form-group">
                            <label class="form-label" for="brand_logo">
                                Website Logo
                            </label>

                            <div class="upload-area">
                                <div class="preview-box" id="logo-upload-preview">
                                    @if($logoUrl)
                                    <img src="{{ $logoUrl }}"
                                        alt="{{ $brandName }} Logo"
                                        id="current-logo-img">
                                    @else
                                    <span style="font-size:12px;color:#999;" id="current-logo-placeholder">No Logo</span>
                                    @endif
                                </div>

                                <div class="upload-content">
                                    <strong>Upload a new logo</strong>
                                    <p>Recommended: Transparent PNG, SVG, or WebP for best results.</p>

                                    <input
                                        type="file"
                                        id="brand_logo"
                                        name="brand_logo"
                                        class="file-input"
                                        accept=".png,.jpg,.jpeg,.webp">

                                    <small class="form-help">
                                        PNG, JPG, JPEG or WebP · Maximum 2 MB
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Browser Identity & Live Preview --}}
            <div class="branding-column">
                {{-- Browser Favicon Card --}}
                <div class="branding-card" id="browser-identity-card">
                    <div class="card-heading">
                        <div class="card-heading-icon" style="background: #eef2ff; color: #4f46e5;">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                        </div>
                        <div>
                            <h3>Browser Identity</h3>
                            <p>Small icon displayed in browser tabs and bookmarks.</p>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="brand_favicon">
                                Browser Favicon
                            </label>

                            <div class="upload-area">
                                <div class="preview-box favicon-box" id="favicon-upload-preview">
                                    @if($faviconUrl)
                                    <img src="{{ $faviconUrl }}"
                                        alt="Browser Favicon"
                                        id="current-favicon-img">
                                    @else
                                    <span style="font-size:11px;color:#999;" id="current-favicon-placeholder">No Icon</span>
                                    @endif
                                </div>

                                <div class="upload-content">
                                    <strong>Upload a new favicon</strong>
                                    <p>Use a square icon that remains sharp at 32×32 or 48×48 px.</p>

                                    <input
                                        type="file"
                                        id="brand_favicon"
                                        name="brand_favicon"
                                        class="file-input"
                                        accept=".ico,.png,.webp">

                                    <small class="form-help">
                                        ICO, PNG or WebP · Maximum 512 KB
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Branding Preview Card --}}
                <div class="branding-card" id="branding-preview-card">
                    <div class="card-heading">
                        <div class="card-heading-icon" style="background: #fdf4ff; color: #a855f7;">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </div>
                        <div>
                            <h3>Branding Preview</h3>
                            <p>Live visualization of how your branding appears to store visitors.</p>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="mock-browser">
                            {{-- Browser Tab Bar --}}
                            <div class="mock-browser-topbar">
                                <div class="mock-browser-dots">
                                    <span class="mock-dot red"></span>
                                    <span class="mock-dot yellow"></span>
                                    <span class="mock-dot green"></span>
                                </div>
                                <div class="mock-tab">
                                    @if($faviconUrl)
                                    <img src="{{ $faviconUrl }}"
                                        alt="Tab Favicon"
                                        id="preview-tab-favicon"
                                        class="mock-tab-favicon">
                                    @else
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23008060'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3C/svg%3E"
                                        alt="Tab Favicon"
                                        id="preview-tab-favicon"
                                        class="mock-tab-favicon">
                                    @endif
                                    <span id="preview-tab-title" class="mock-tab-title">{{ $brandName }}</span>
                                    <span class="mock-tab-close">&times;</span>
                                </div>
                            </div>

                            {{-- Website Navbar Mockup --}}
                            <div class="mock-navbar">
                                <div class="mock-brand-badge">
                                    @if($logoUrl)
                                    <img src="{{ $logoUrl }}"
                                        alt="{{ $brandName }}"
                                        id="preview-navbar-logo"
                                        class="mock-logo">
                                    @else
                                    <img src=""
                                        alt=""
                                        id="preview-navbar-logo"
                                        class="mock-logo"
                                        style="display: none;">
                                    @endif
                                    <span id="preview-navbar-name" class="mock-brand-text">{{ $brandName }}</span>
                                </div>

                                <div class="mock-nav-links">
                                    <span>Home</span>
                                    <span>Features</span>
                                    <span class="mock-nav-cta">Get Started</span>
                                </div>
                            </div>
                        </div>

                        <div class="preview-legend">
                            <span><span style="color:#008060;">&#9679;</span> Live browser mockup</span>
                            <span>Auto-updates as you type or select files</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Single Save Changes Footer Card --}}
        <div class="branding-card branding-footer-card">
            <div class="branding-footer">
                <span class="footer-note">
                    Changes apply across your website navbar, admin sidebar, and browser tabs after saving.
                </span>

                <button type="submit" class="btn-save">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <path d="M17 21v-8H7v8" />
                        <path d="M7 3v5h8" />
                    </svg>
                    <span>Save Branding</span>
                </button>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const brandNameInput = document.getElementById('brand_name');
        const brandLogoInput = document.getElementById('brand_logo');
        const brandFaviconInput = document.getElementById('brand_favicon');

        const previewTabTitle = document.getElementById('preview-tab-title');
        const previewNavbarName = document.getElementById('preview-navbar-name');
        const previewTabFavicon = document.getElementById('preview-tab-favicon');
        const previewNavbarLogo = document.getElementById('preview-navbar-logo');
        const logoUploadPreview = document.getElementById('logo-upload-preview');
        const faviconUploadPreview = document.getElementById('favicon-upload-preview');

        // Real-time live update for Brand Name
        if (brandNameInput) {
            brandNameInput.addEventListener('input', function () {
                const text = this.value.trim() || 'Brand Name';
                if (previewTabTitle) previewTabTitle.textContent = text;
                if (previewNavbarName) previewNavbarName.textContent = text;
            });
        }

        // Live preview on file selection for Logo
        if (brandLogoInput) {
            brandLogoInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const result = e.target.result;
                        if (previewNavbarLogo) {
                            previewNavbarLogo.src = result;
                            previewNavbarLogo.style.display = 'block';
                        }
                        if (logoUploadPreview) {
                            logoUploadPreview.innerHTML = '<img src="' + result + '" alt="Logo Preview" style="max-width:100%;max-height:100%;object-fit:contain;">';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Live preview on file selection for Favicon
        if (brandFaviconInput) {
            brandFaviconInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const result = e.target.result;
                        if (previewTabFavicon) {
                            previewTabFavicon.src = result;
                            previewTabFavicon.style.display = 'inline-block';
                        }
                        if (faviconUploadPreview) {
                            faviconUploadPreview.innerHTML = '<img src="' + result + '" alt="Favicon Preview" style="max-width:100%;max-height:100%;object-fit:contain;">';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
@endpush

@endsection
