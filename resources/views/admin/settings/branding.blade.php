@extends('admin.layout')

@section('title', 'Branding Settings')
@section('heading', 'Website Branding')

@section('content')

<style>
    .branding-page {
        max-width: 1000px;
        margin: 0 auto;
        color: #202223;
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

    .branding-card {
        background: #fff;
        border: 1px solid #e3e5e7;
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .card-heading {
        padding: 20px 24px;
        border-bottom: 1px solid #eceeef;
    }

    .card-heading h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 5px;
    }

    .card-heading p {
        color: #73777a;
        font-size: 12px;
        margin: 0;
    }

    .card-body {
        padding: 24px;
    }

    .form-group {
        margin-bottom: 22px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 650;
        margin-bottom: 8px;
        color: #303438;
    }

    .form-control {
        width: 100%;
        min-height: 43px;
        padding: 10px 13px;
        border: 1px solid #c9cccf;
        border-radius: 7px;
        font-size: 13px;
        color: #202223;
        background: #fff;
        box-sizing: border-box;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .form-control:focus {
        border-color: #008060;
        box-shadow: 0 0 0 3px rgba(0, 128, 96, .10);
    }

    .form-help {
        display: block;
        margin-top: 7px;
        color: #8c9196;
        font-size: 11px;
        line-height: 1.5;
    }

    .upload-area {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 18px;
        border: 1px dashed #c9cccf;
        border-radius: 9px;
        background: #fafbfb;
    }

    .preview-box {
        width: 100px;
        height: 100px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #e3e5e7;
        border-radius: 9px;
        padding: 10px;
        box-sizing: border-box;
    }

    .preview-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .favicon-box {
        width: 72px;
        height: 72px;
    }

    .upload-content {
        flex: 1;
        min-width: 0;
    }

    .upload-content strong {
        display: block;
        font-size: 13px;
        margin-bottom: 5px;
    }

    .upload-content p {
        font-size: 11px;
        color: #73777a;
        margin: 0 0 12px;
    }

    .file-input {
        display: block;
        width: 100%;
        font-size: 12px;
        color: #555;
    }

    .file-input::file-selector-button {
        padding: 8px 12px;
        margin-right: 10px;
        border: 1px solid #c9cccf;
        border-radius: 6px;
        background: #fff;
        color: #303438;
        font-weight: 600;
        cursor: pointer;
    }

    .alert-success,
    .alert-error {
        padding: 13px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
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
        font-size: 11px;
    }

    .btn-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 20px;
        border: 0;
        border-radius: 7px;
        background: #008060;
        color: #fff;
        font-size: 13px;
        font-weight: 650;
        cursor: pointer;
        transition: background .2s;
    }

    .btn-save:hover {
        background: #006e52;
    }

    .btn-save svg {
        width: 16px;
        height: 16px;
    }

    @media (max-width: 600px) {
        .branding-header h2 {
            font-size: 19px;
        }

        .card-heading,
        .card-body {
            padding: 17px;
        }

        .upload-area {
            align-items: flex-start;
            flex-direction: column;
        }

        .branding-footer {
            align-items: stretch;
            flex-direction: column;
            padding: 17px;
        }

        .btn-save {
            width: 100%;
        }
    }

    /* Compact Admin Branding Layout */

    .branding-page {
        width: 100%;
        max-width: none;
        margin: 0;
    }

    .branding-header {
        margin-bottom: 16px;
    }

    .branding-header h2 {
        font-size: 22px;
        margin-bottom: 4px;
    }

    .branding-card {
        margin-bottom: 14px;
        border-radius: 9px;
    }

    .card-heading {
        padding: 15px 18px;
    }

    .card-body {
        padding: 16px 18px;
    }

    .form-group {
        margin-bottom: 14px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-control {
        min-height: 40px;
        padding: 9px 12px;
    }

    .upload-area {
        padding: 12px 14px;
        gap: 16px;
    }

    .preview-box {
        width: 76px;
        height: 76px;
        padding: 8px;
    }

    .favicon-box {
        width: 64px;
        height: 64px;
    }

    .upload-content p {
        margin-bottom: 8px;
    }

    .branding-footer {
        padding: 13px 18px;
    }

    .btn-save {
        padding: 10px 18px;
    }

    @media (max-width: 600px) {

        .card-heading,
        .card-body {
            padding: 14px;
        }

        .upload-area {
            padding: 12px;
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
        {{ session('success') }}
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

        {{-- Brand Name --}}
        <div class="branding-card">
            <div class="card-heading">
                <h3>Website Name</h3>
                <p>Your brand name appears across your website.</p>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="brand_name">
                        Brand Name
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
                        This name will be displayed in your website navbar and admin sidebar.
                    </small>
                </div>
            </div>
        </div>

        {{-- Brand Logo --}}
        <div class="branding-card">
            <div class="card-heading">
                <h3>Website Logo</h3>
                <p>Upload the primary logo used throughout your website.</p>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Current Logo</label>

                    <div class="upload-area">
                        <div class="preview-box">
                            @if($brandLogo)
                            <img src="{{ asset('public/' . $brandLogo) }}"
                                alt="{{ $brandName }} Logo">
                            @else
                            <span style="font-size:12px;color:#999;">No Logo</span>
                            @endif
                        </div>

                        <div class="upload-content">
                            <strong>Upload a new logo</strong>
                            <p>Recommended: Transparent PNG or WebP for best results.</p>

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

        {{-- Favicon --}}
        <div class="branding-card">
            <div class="card-heading">
                <h3>Browser Favicon</h3>
                <p>Small icon displayed in browser tabs and bookmarks.</p>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Current Favicon</label>

                    <div class="upload-area">
                        <div class="preview-box favicon-box">
                            @if($brandFavicon)
                            <img src="{{ asset('public/' . $brandFavicon) }}"
                                alt="Browser Favicon">
                            @else
                            <span style="font-size:11px;color:#999;">No Icon</span>
                            @endif
                        </div>

                        <div class="upload-content">
                            <strong>Upload a new favicon</strong>
                            <p>Use a square icon that remains clear at small sizes.</p>

                            <input
                                type="file"
                                id="brand_favicon"
                                name="brand_favicon"
                                class="file-input"
                                accept=".ico,.png,.webp">

                            <small class="form-help">
                                ICO, PNG or WebP · Maximum 512 KB · Recommended 32×32 or 48×48 px
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save --}}
        <div class="branding-card">
            <div class="branding-footer">
                <span class="footer-note">
                    Changes will apply to your website branding after saving.
                </span>

                <button type="submit" class="btn-save">
                    <svg viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <path d="M17 21v-8H7v8" />
                        <path d="M7 3v5h8" />
                    </svg>
                    Save Branding
                </button>
            </div>
        </div>

    </form>
</div>

@endsection