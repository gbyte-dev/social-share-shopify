@extends('layouts.shopify')

@section('title', 'My Social Links - ' . $shop)
@section('header_title', 'My Social Links')
@section('header_subtitle', 'Configure and organize social media channels shown on your storefront')

@section('header_actions')
    <a href="{{ route('shopify.social-links.create') }}" class="btn btn-primary btn-sm">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Add Social Link</span>
    </a>
@endsection

@section('content')
    <!-- Available Platforms Quick Strip -->
    @if(count($availablePlatforms) > 0)
        <div class="card" style="margin-bottom: 1.5rem; background: #ffffff; padding: 1.25rem;">
            <div style="font-size: 0.8125rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                Available Platforms Ready to Connect ({{ count($availablePlatforms) }} remaining)
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                @foreach($availablePlatforms as $platKey => $plat)
                    @if($platKey !== 'other')
                        <a href="{{ route('shopify.social-links.create', ['platform' => $platKey]) }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8125rem;">
                            <span style="color: {{ $plat['brand_color'] }}; display: flex; align-items: center;">
                                {!! \App\Services\SocialPlatformService::iconSvg($platKey, 16) !!}
                            </span>
                            <span>+ Add {{ $plat['name'] }}</span>
                        </a>
                    @endif
                @endforeach
                <a href="{{ route('shopify.social-links.create', ['platform' => 'other']) }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8125rem; border-color: var(--primary); color: var(--primary);">
                    <span style="display: flex; align-items: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    </span>
                    <span>+ Add Custom Platform</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Social Links Management Card -->
    <div class="card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">Configured Channels ({{ $links->count() }})</h3>
                <p style="font-size: 0.8125rem; color: var(--text-subtle);">Active links appear in the floating widget and storefront extensions.</p>
            </div>
        </div>

        @if($links->isEmpty())
            <div style="text-align: center; padding: 3.5rem 1.5rem;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                </div>
                <h4 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-main);">No social links configured yet</h4>
                <p style="font-size: 0.875rem; color: var(--text-muted); max-width: 480px; margin: 0 auto 1.5rem; line-height: 1.5;">
                    Add standard platforms or connect custom apps (Discord, Telegram, Reddit, Threads, Twitch, etc.) to enable customers to connect and share your products with ease.
                </p>
                <a href="{{ route('shopify.social-links.create') }}" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add First Social Link</span>
                </a>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--card-border); color: var(--text-subtle); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 0.75rem 0.75rem 0.75rem 0.25rem;">Order</th>
                            <th style="padding: 0.75rem;">Platform</th>
                            <th style="padding: 0.75rem;">Display Label</th>
                            <th style="padding: 0.75rem;">Profile / Share URL</th>
                            <th style="padding: 0.75rem; text-align: center;">Status</th>
                            <th style="padding: 0.75rem 0.25rem 0.75rem 0.75rem; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($links as $link)
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                                <td style="padding: 1rem 0.75rem 1rem 0.25rem; font-weight: 700; color: var(--text-subtle); width: 60px;">
                                    #{{ $link->sort_order }}
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 36px; height: 36px; border-radius: 9px; background: {{ $link->brand_color }}; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow: hidden;">
                                            {!! $link->icon_html !!}
                                        </div>
                                        <div>
                                            <div style="font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.4rem;">
                                                <span>{{ $link->platform_name }}</span>
                                                @if($link->isCustom())
                                                    <span style="font-size: 0.65rem; font-weight: 700; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px;">Custom</span>
                                                @endif
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--text-subtle);">
                                                @if($link->isCustom() && $link->official_website_url)
                                                    <a href="{{ $link->official_website_url }}" target="_blank" rel="noopener noreferrer" style="color: var(--text-subtle); text-decoration: underline;">Website</a>
                                                @else
                                                    {{ ucfirst($link->platform) }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1rem 0.75rem; font-weight: 600; color: var(--text-main);">
                                    {{ $link->label ?: $link->platform_name }}
                                </td>
                                <td style="padding: 1rem 0.75rem; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: none; font-size: 0.8125rem;" title="{{ $link->url }}">
                                        {{ $link->url }}
                                    </a>
                                </td>
                                <td style="padding: 1rem 0.75rem; text-align: center;">
                                    <form action="{{ route('shopify.social-links.toggle', $link->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                                            @if($link->is_active)
                                                <span style="display: inline-block; font-size: 0.75rem; font-weight: 700; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.25rem 0.6rem; border-radius: 9999px;" title="Click to deactivate">Active</span>
                                            @else
                                                <span style="display: inline-block; font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 0.25rem 0.6rem; border-radius: 9999px;" title="Click to activate">Inactive</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td style="padding: 1rem 0.25rem 1rem 0.75rem; text-align: right;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.4rem;">
                                        <a href="{{ route('shopify.social-links.edit', $link->id) }}" class="btn btn-secondary btn-sm" title="Edit Link">
                                            Edit
                                        </a>

                                        <form action="{{ route('shopify.social-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your {{ $link->platform_name }} link?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm" style="color: var(--danger); background: transparent; border: 1px solid #fca5a5;" title="Delete Link">
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
@endsection
