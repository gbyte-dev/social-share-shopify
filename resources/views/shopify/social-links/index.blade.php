@extends('layouts.shopify')

@section('title', 'My Social Links - ' . $shop)
@section('header_title', 'My Social Links')
@section('header_subtitle', 'Configure and organize social media channels shown on your storefront')

@section('header_actions')
    <a href="{{ route('shopify.social-links.create') }}" class="btn btn-primary btn-sm" style="padding: 0.4rem 0.75rem; font-size: 0.8125rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Add Social Link</span>
    </a>
@endsection

@section('content')
    <!-- Scoped Polaris-inspired Styles for My Social Links -->
    <style>
        /* Hide developer-facing Live & Authenticated badge on Social Links */
        .top-actions .badge-live {
            display: none !important;
        }

        .sn-links-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }

        .sn-card {
            background: #ffffff;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .sn-available-card {
            padding: 0.75rem 1rem;
        }

        .sn-available-title {
            font-size: 0.6875rem;
            font-weight: 700;
            color: #6d7175;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .sn-platform-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            align-items: center;
        }

        .sn-chip-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #202223;
            background: #ffffff;
            border: 1px solid #dcdfe3;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
            line-height: 1.3;
        }

        .sn-chip-btn:hover {
            background: #f6f6f7;
            border-color: #c9cccf;
            color: #202223;
        }

        .sn-chip-custom {
            color: var(--primary, #4f46e5);
            background: #f5f7ff;
            border: 1px dashed #c7d2fe;
        }

        .sn-chip-custom:hover {
            background: #eef2ff;
            border-color: #a5b4fc;
            color: #3730a3;
        }

        .sn-channels-card {
            padding: 0.875rem 1rem;
        }

        .sn-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.625rem;
            border-bottom: 1px solid #f1f2f4;
            flex-wrap: wrap;
            gap: 0.5rem;
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

        .sn-table-responsive {
            overflow-x: auto;
        }

        .sn-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }

        .sn-table th {
            padding: 0.45rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6d7175;
            border-bottom: 1px solid #e1e3e5;
            white-space: nowrap;
        }

        .sn-table td {
            padding: 0.55rem 0.5rem;
            border-bottom: 1px solid #f1f2f4;
            vertical-align: middle;
            color: #202223;
        }

        .sn-table tr:last-child td {
            border-bottom: none;
        }

        .sn-table tr:hover td {
            background: #fafbfb;
        }

        .sn-order-cell {
            font-weight: 600;
            color: #6d7175;
            font-size: 0.75rem;
            width: 50px;
        }

        .sn-platform-cell {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .sn-platform-icon-wrap {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #ffffff;
            overflow: hidden;
        }

        .sn-platform-icon-wrap svg {
            width: 15px;
            height: 15px;
        }

        .sn-platform-name-title {
            font-weight: 600;
            font-size: 0.8125rem;
            color: #202223;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .sn-custom-badge {
            font-size: 0.625rem;
            font-weight: 600;
            background: #e0e7ff;
            color: #4338ca;
            padding: 1px 5px;
            border-radius: 3px;
        }

        .sn-platform-subtext {
            font-size: 0.6875rem;
            color: #6d7175;
            margin-top: 1px;
        }

        .sn-url-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 0.75rem;
            max-width: 240px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
        }

        .sn-url-link:hover {
            text-decoration: underline;
        }

        .sn-badge-active {
            display: inline-block;
            font-size: 0.6875rem;
            font-weight: 600;
            color: #007f5f;
            background: #e4f8ed;
            border: 1px solid #b7ebd0;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            transition: opacity 0.15s ease;
        }

        .sn-badge-inactive {
            display: inline-block;
            font-size: 0.6875rem;
            font-weight: 600;
            color: #5c5f62;
            background: #f1f2f4;
            border: 1px solid #e1e3e5;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            transition: opacity 0.15s ease;
        }

        .sn-actions-cell {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.35rem;
        }

        .sn-btn-action {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            line-height: 1.25;
        }

        .sn-btn-edit {
            background: #ffffff;
            border: 1px solid #dcdfe3;
            color: #202223;
        }

        .sn-btn-edit:hover {
            background: #f6f6f7;
            border-color: #c9cccf;
        }

        .sn-btn-delete {
            background: #ffffff;
            border: 1px solid #fecdd3;
            color: #e11d48;
        }

        .sn-btn-delete:hover {
            background: #fff1f2;
            border-color: #fda4af;
        }

        .sn-empty-wrap {
            text-align: center;
            padding: 2.25rem 1rem;
        }

        .sn-empty-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #eef2ff;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .sn-empty-title {
            font-size: 0.9375rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
            color: #202223;
        }

        .sn-empty-text {
            font-size: 0.8125rem;
            color: #6d7175;
            max-width: 440px;
            margin: 0 auto 1.25rem;
            line-height: 1.45;
        }
    </style>

    <div class="sn-links-wrap">
        <!-- Available Platforms Quick Strip -->
        @if(count($availablePlatforms) > 0)
            <div class="sn-card sn-available-card">
                <div class="sn-available-title">
                    Available Platforms Ready to Connect ({{ count($availablePlatforms) }} remaining)
                </div>
                <div class="sn-platform-chips">
                    @foreach($availablePlatforms as $platKey => $plat)
                        @if($platKey !== 'other')
                            <a href="{{ route('shopify.social-links.create', ['platform' => $platKey]) }}" class="sn-chip-btn">
                                <span style="color: {{ $plat['brand_color'] }}; display: flex; align-items: center;">
                                    {!! \App\Services\SocialPlatformService::iconSvg($platKey, 14) !!}
                                </span>
                                <span>+ Add {{ $plat['name'] }}</span>
                            </a>
                        @endif
                    @endforeach
                    <a href="{{ route('shopify.social-links.create', ['platform' => 'other']) }}" class="sn-chip-btn sn-chip-custom">
                        <span style="display: flex; align-items: center;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                        </span>
                        <span>+ Add Custom Platform</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Social Links Management Card -->
        <div class="sn-card sn-channels-card">
            <div class="sn-card-head">
                <div>
                    <h3 class="sn-card-title">Configured Channels ({{ $links->count() }})</h3>
                    <p class="sn-card-subtitle">Active links appear in the floating widget and storefront extensions.</p>
                </div>
            </div>

            @if($links->isEmpty())
                <div class="sn-empty-wrap">
                    <div class="sn-empty-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    </div>
                    <h4 class="sn-empty-title">No social links configured yet</h4>
                    <p class="sn-empty-text">
                        Add standard platforms or connect custom apps (Discord, Telegram, Reddit, Threads, Twitch, etc.) to enable customers to connect and share your products with ease.
                    </p>
                    <a href="{{ route('shopify.social-links.create') }}" class="btn btn-primary btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Add First Social Link</span>
                    </a>
                </div>
            @else
                <div class="sn-table-responsive">
                    <table class="sn-table">
                        <thead>
                            <tr>
                                <th style="width: 48px; padding-left: 0.25rem;">Order</th>
                                <th>Platform</th>
                                <th>Display Label</th>
                                <th>Profile / Share URL</th>
                                <th style="text-align: center; width: 85px;">Status</th>
                                <th style="text-align: right; width: 110px; padding-right: 0.25rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($links as $link)
                                <tr>
                                    <td class="sn-order-cell" style="padding-left: 0.25rem;">
                                        #{{ $link->sort_order }}
                                    </td>
                                    <td>
                                        <div class="sn-platform-cell">
                                            <div class="sn-platform-icon-wrap" style="background: {{ $link->brand_color }};">
                                                {!! $link->icon_html !!}
                                            </div>
                                            <div>
                                                <div class="sn-platform-name-title">
                                                    <span>{{ $link->platform_name }}</span>
                                                    @if($link->isCustom())
                                                        <span class="sn-custom-badge">Custom</span>
                                                    @endif
                                                </div>
                                                @if($link->isCustom() && $link->official_website_url)
                                                    <div class="sn-platform-subtext">
                                                        <a href="{{ $link->official_website_url }}" target="_blank" rel="noopener noreferrer" style="color: #6d7175; text-decoration: underline;">Website</a>
                                                    </div>
                                                @elseif(strtolower($link->platform) !== strtolower($link->platform_name))
                                                    <div class="sn-platform-subtext">{{ ucfirst($link->platform) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight: 500;">
                                        {{ $link->label ?: $link->platform_name }}
                                    </td>
                                    <td>
                                        <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="sn-url-link" title="{{ $link->url }}">
                                            {{ $link->url }}
                                        </a>
                                    </td>
                                    <td style="text-align: center;">
                                        <form action="{{ route('shopify.social-links.toggle', $link->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;" title="{{ $link->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                                                @if($link->is_active)
                                                    <span class="sn-badge-active">Active</span>
                                                @else
                                                    <span class="sn-badge-inactive">Inactive</span>
                                                @endif
                                            </button>
                                        </form>
                                    </td>
                                    <td style="text-align: right; padding-right: 0.25rem;">
                                        <div class="sn-actions-cell">
                                            <a href="{{ route('shopify.social-links.edit', $link->id) }}" class="sn-btn-action sn-btn-edit" title="Edit Link">
                                                Edit
                                            </a>

                                            <form action="{{ route('shopify.social-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your {{ $link->platform_name }} link?');" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sn-btn-action sn-btn-delete" title="Delete Link">
                                                    Delete
                                                </button>
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
@endsection
