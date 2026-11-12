@extends('layouts.shopify')

@section('title', 'My Social Links - ' . $shop)
@section('header_title', 'My Social Links')
@section('header_subtitle', 'Configure and organize social media channels shown on your storefront')

@section('header_actions')
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <button
            type="button"
            class="btn btn-secondary btn-sm"
            data-embed-enable
            data-url="{{ route('shopify.social-links.activate-embed', array_filter([
                'shop' => $shop,
                'host' => $host ?? session('shopify_host'),
            ])) }}"
            hidden>
            Enable Storefront Widget
        </button>
        <span data-embed-status-badge class="sn-embed-status" aria-live="polite">
            Checking storefront status...
        </span>

        <button type="button"
                onclick="openModal('create')"
                class="btn btn-primary btn-sm"
                style="padding: 0.4rem 0.75rem; font-size: 0.8125rem;">
            Add Social Link
        </button>
    </div>
@endsection

@section('content')
    <style>
        .top-actions .badge-live { display: none !important; }
        [hidden] {
            display: none !important;
        }

        .sn-embed-status {
            display: inline-flex;
            align-items: center;
            padding: .3rem .65rem;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #4b5563;
            font-size: .75rem;
            font-weight: 700;
        }
        .sn-embed-status.is-active {
            color: #065f46;
            background: #ecfdf5;
            border-color: #a7f3d0;
        }
        .sn-embed-status.is-inactive {
            color: #92400e;
            background: #fffbeb;
            border-color: #fde68a;
        }
        .sn-links-wrap { display: flex; flex-direction: column; gap: 0.875rem; }
        .sn-card { background: #ffffff; border: 1px solid #e1e3e5; border-radius: 8px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); }
        .sn-available-card { padding: 0.75rem 1rem; }
        .sn-available-title { font-size: 0.6875rem; font-weight: 700; color:  #000000; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .sn-platform-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }
        .sn-chip-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.55rem; font-size: 0.75rem; font-weight: 600; color: #202223; background: #ffffff; border: 1px solid #dcdfe3; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; }
        .sn-chip-btn:hover { background: #f6f6f7; border-color: #c9cccf; color: #202223; }
        .sn-chip-custom { color: var(--primary, #4f46e5); background: #f5f7ff; border: 1px dashed #c7d2fe; }
        .sn-chip-custom:hover { background: #eef2ff; border-color: #a5b4fc; color: #3730a3; }
        .sn-channels-card { padding: 0.875rem 1rem; }
        .sn-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.625rem; border-bottom: 1px solid #f1f2f4; flex-wrap: wrap; gap: 0.5rem; }
        .sn-card-title { font-size: 0.875rem; font-weight: 700; color: #202223; margin: 0; }
        .sn-card-subtitle { font-size: 0.75rem; color:  #000000; margin: 0.15rem 0 0 0; }
        .sn-table-responsive { overflow-x: auto; }
        .sn-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; text-align: left; }
        .sn-table th { padding: 0.45rem 0.5rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color:  #000000; border-bottom: 1px solid #e1e3e5; white-space: nowrap; }
        .sn-table td { padding: 0.55rem 0.5rem; border-bottom: 1px solid #f1f2f4; vertical-align: middle; color: #202223; }
        .sn-table tr:hover td { background: #fafbfb; }
        .sn-order-cell { font-weight: 600; color:  #000000; font-size: 0.75rem; width: 50px; }
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
        .sn-modal-close { background: none; border: none; cursor: pointer; color:  #000000; }
        
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


    <style>
        .sn-settings-shell {
            display: grid !important;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr) !important;
            gap: 1.25rem;
            align-items: start;
            width: 100%;
            min-width: 0;
            margin-bottom: 1rem;
        }
        .sn-settings-main, .sn-settings-preview { min-width: 0 !important; width: 100%; }
        .sn-settings-preview { position: sticky; top: 84px; align-self: start; }
        .sn-available-platforms { margin-bottom: 1rem; }
        .sn-configured-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
        .sn-configured-item { display: flex; align-items: center; gap: .6rem; min-width: 0; padding: .65rem; border: 1px solid #e1e3e5; border-radius: 9px; background: #fff; }
        .sn-configured-toggle-form, .sn-configured-actions form { display: inline-flex; margin: 0; }
        .sn-configured-toggle { display: inline-flex; align-items: center; cursor: pointer; }
        .sn-configured-toggle input { width: 16px; height: 16px; margin: 0; accent-color: var(--primary, #4f46e5); cursor: pointer; }
        .sn-configured-platform-icon { width: 28px; height: 28px; flex: 0 0 28px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; color: #fff; overflow: hidden; }
        .sn-configured-platform-icon svg { width: 18px; height: 18px; }
        .sn-configured-platform-info { min-width: 0; flex: 1; }
        .sn-configured-platform-name { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; font-size: .8125rem; font-weight: 650; color: #202223; }
        .sn-configured-state { margin-top: .15rem; font-size: .6875rem; color:  #000000; }
        .sn-configured-state.is-active { color: #008060; }
        .sn-configured-state.is-inactive { color: #8a6d1d; }
        .sn-configured-actions { display: inline-flex; align-items: center; gap: .35rem; flex: 0 0 auto; }
        .sn-icon-action { width: 30px; height: 30px; padding: 0; justify-content: center; }
        .sn-icon-action svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        @media (max-width: 560px) {
            .sn-settings-shell { grid-template-columns: minmax(0, 1fr) !important; }
            .sn-settings-preview { position: static; }
            .sn-configured-grid { grid-template-columns: minmax(0, 1fr); }
        }
        .sn-settings-main { min-width:0; }
        .sn-settings-tabs { display:flex; flex-wrap:wrap; gap:.45rem; padding:.4rem; background:#f6f6f7; border:1px solid #e1e3e5; border-radius:9px; margin-bottom:.85rem; }
        .sn-settings-tab { border:1px solid transparent; background:transparent; color:#61666b; border-radius:7px; padding:.55rem .8rem; font-size:.8rem; font-weight:650; cursor:pointer; transition:all .15s ease; }
        .sn-settings-tab:hover { background:#fff; color:#202223; }
        .sn-settings-tab.is-active { background:#fff; border-color:#dfe3e8; color:#202223; box-shadow:0 1px 2px rgba(0,0,0,.05); }
        .sn-settings-panel { display:none; }
        .sn-settings-panel.is-active { display:block; }
        .sn-settings-panel .polaris-card { margin-bottom:.85rem; }
        .sn-settings-preview { min-width:0; }
        .sn-links-wrap { transition:opacity .15s ease; }
        @media(max-width:560px) { .sn-settings-shell { grid-template-columns:1fr !important; } .sn-settings-preview { position:static; max-height:none; overflow:visible; } }
        @media(max-width:640px) { .sn-settings-tabs { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); } .sn-settings-tab { text-align:center; } }

        /* Modern Shopify Polaris-Inspired Design Tokens */
        .settings-page-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(360px, 1fr);
            gap: 1.5rem;
            align-items: start;
        }

        /* Right Column: Sticky Storefront Preview Panel */
        .settings-preview-column {
            position: sticky;
            top: calc(74px + 1.25rem);
            align-self: start;
            max-height: calc(100vh - 74px - 2rem);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
            z-index: 10;
        }

        .settings-preview-column::-webkit-scrollbar {
            width: 6px;
        }

        .settings-preview-column::-webkit-scrollbar-track {
            background: transparent;
        }

        .settings-preview-column::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 9999px;
        }

        .preview-sticky-wrap {
            display: block;
        }

        @media (max-width: 560px) {
            .settings-page-grid {
                grid-template-columns: 1fr;
            }
            .settings-preview-column {
                order: initial;
                position: static;
                top: auto;
                align-self: auto;
                max-height: none;
                overflow-y: visible;
                overflow-x: visible;
            }
            .preview-sticky-wrap {
                position: static;
                top: auto;
            }
        }

        /* Polaris Cards */
        .polaris-card {
            background: #ffffff;
            border: 1px solid #d2d5d8;
            border-radius: 12px;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transition: border-color 0.15s ease;
        }

        .polaris-card-header {
            padding: 1.15rem 1.35rem 0.9rem;
            border-bottom: 1px solid #f1f2f4;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .polaris-card-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.01em;
        }

        .polaris-card-subtitle {
            font-size: 0.8125rem;
             
            margin-top: 0.15rem;
        }

        .polaris-card-body {
            padding: 1.25rem 1.35rem;
        }

        /* Labels and Help Text */
        .polaris-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .polaris-help-text {
            font-size: 0.75rem;
             
            line-height: 1.45;
            margin-top: 0.2rem;
        }

        .polaris-field-error {
            font-size: 0.75rem;
            color: var(--danger);
            margin-top: 0.35rem;
        }

        /* Polaris Badges */
        .polaris-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.71875rem;
            font-weight: 700;
            line-height: 1;
        }

        .polaris-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .polaris-badge-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .polaris-badge-subdued {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .polaris-badge-primary {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid var(--primary-border);
        }

        .polaris-badge-info {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        /* Polaris Accessible Switch */
        .polaris-setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .polaris-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
            cursor: pointer;
        }

        .polaris-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .polaris-switch-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #cbd5e1;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border-radius: 9999px;
        }

        .polaris-switch-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: #ffffff;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .polaris-switch input:checked + .polaris-switch-slider {
            background-color: var(--primary);
        }

        .polaris-switch input:focus-visible + .polaris-switch-slider {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.35);
        }

        .polaris-switch input:checked + .polaris-switch-slider:before {
            transform: translateX(20px);
        }

        /* Choice Cards Grid (Position) */
        .polaris-choice-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .grid-span-full {
            grid-column: span 2;
        }

        @media (max-width: 600px) {
            .polaris-choice-grid {
                grid-template-columns: 1fr;
            }
            .grid-span-full {
                grid-column: span 1;
            }
        }

        .polaris-choice-card {
            border: 1.5px solid #d2d5d8;
            border-radius: 10px;
            padding: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #ffffff;
            transition: all 0.15s ease;
            position: relative;
        }

        .polaris-choice-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .polaris-choice-card:hover {
            border-color: #000000;
            background: #f8fafc;
        }

        .polaris-choice-card.is-selected {
            border-color: var(--primary);
            background: var(--primary-light);
            box-shadow: 0 0 0 1px var(--primary);
        }

        .choice-card-diagram {
            width: 44px;
            height: 34px;
            flex-shrink: 0;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .screen-box {
            position: absolute;
            inset: 3px;
            border: 1px dashed #000000;
            border-radius: 3px;
        }

        .screen-dock-dot {
            width: 8px;
            height: 8px;
            border-radius: 3px;
            background: var(--primary);
            position: absolute;
        }

        /* Diagram Position Placements */
        .pos-icon-br .screen-dock-dot { bottom: 2px; right: 2px; width: 10px; height: 5px; border-radius: 2px; }
        .pos-icon-bl .screen-dock-dot { bottom: 2px; left: 2px; width: 10px; height: 5px; border-radius: 2px; }
        .pos-icon-vr .screen-dock-dot { top: 50%; right: 2px; transform: translateY(-50%); width: 5px; height: 10px; border-radius: 2px; }
        .pos-icon-vl .screen-dock-dot { top: 50%; left: 2px; transform: translateY(-50%); width: 5px; height: 10px; border-radius: 2px; }
        .pos-icon-custom .screen-dock-dot {
            top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 6px; height: 6px; border-radius: 50%;
            box-shadow: -8px 0 0 var(--primary), 8px 0 0 var(--primary), 0 -6px 0 var(--primary), 0 6px 0 var(--primary);
        }

        .choice-card-text {
            flex: 1;
            min-width: 0;
        }

        .choice-card-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .choice-card-desc {
            font-size: 0.71875rem;
             
            line-height: 1.35;
        }

        .choice-card-radio {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .polaris-choice-card.is-selected .choice-card-radio {
            border-color: var(--primary);
            background: var(--primary);
        }

        .choice-card-radio .radio-circle {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ffffff;
            opacity: 0;
            transition: opacity 0.15s ease;
        }

        .polaris-choice-card.is-selected .choice-card-radio .radio-circle {
            opacity: 1;
        }

        /* Subcard (Custom Position Panel) */
        .polaris-subcard {
            background: #f8fafc;
            border: 1px solid #d2d5d8;
            border-radius: 10px;
            padding: 1.15rem;
        }

        .subcard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }

        /* Polaris Segmented Controls */
        .polaris-segmented-control {
            display: flex;
            background: #f1f2f4;
            border-radius: 8px;
            padding: 3px;
            gap: 2px;
        }

        .polaris-segmented-tall .segmented-item {
            padding: 0.55rem 0.65rem;
            flex-direction: column;
            gap: 0.3rem;
        }

        .segmented-item {
            flex: 1;
            text-align: center;
            padding: 0.45rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }

        .segmented-item input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .segmented-item.is-active {
            background: #ffffff;
            color: var(--text-main);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-weight: 700;
        }

        .form-row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 600px) {
            .form-row-2col {
                grid-template-columns: 1fr;
            }
        }

        /* Offset Controls Box */
        .offset-device-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.9rem;
        }

        .offset-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .offset-control {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .offset-control-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .offset-name {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .offset-val-badge {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
            padding: 1px 6px;
            border-radius: 4px;
        }

        .offset-inputs-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .polaris-slider {
            flex: 1;
            height: 6px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .polaris-number-input {
            width: 58px;
            padding: 0.25rem 0.35rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            text-align: center;
            color: var(--text-main);
        }

        .polaris-number-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }

        /* Size Dot & Shape Preview Chips */
        .size-dot {
            border-radius: 50%;
            background: var(--primary);
            display: inline-block;
        }
        .size-dot-sm { width: 8px; height: 8px; }
        .size-dot-md { width: 12px; height: 12px; }
        .size-dot-lg { width: 16px; height: 16px; }

        .shape-preview {
            width: 14px;
            height: 14px;
            background: var(--primary);
            display: inline-block;
        }
        .shape-preview-circle { border-radius: 50%; }
        .shape-preview-rounded { border-radius: 4px; }
        .shape-preview-square { border-radius: 1px; }

        /* Hover Animation Grid */
        .polaris-anim-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        @media (max-width: 600px) {
            .polaris-anim-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .polaris-anim-card {
            border: 1.5px solid #d2d5d8;
            border-radius: 10px;
            padding: 0.85rem 0.65rem;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            background: #ffffff;
            transition: all 0.15s ease;
            position: relative;
        }

        .polaris-anim-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .polaris-anim-card:hover {
            border-color: #000000;
            background: #f8fafc;
        }

        .polaris-anim-card.is-selected {
            border-color: var(--primary);
            background: var(--primary-light);
            box-shadow: 0 0 0 1px var(--primary);
        }

        .anim-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mini-anim-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(79, 70, 229, 0.3);
        }

        .anim-card-body {
            flex: 1;
        }

        .anim-card-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .anim-card-desc {
            font-size: 0.6875rem;
             
            line-height: 1.25;
            margin-top: 0.1rem;
        }

        .anim-card-radio {
            position: absolute;
            top: 0.75rem;
            right: 0.65rem;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .polaris-anim-card.is-selected .anim-card-radio {
            border-color: var(--primary);
            background: var(--primary);
        }

        .anim-card-radio .radio-circle {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #ffffff;
            opacity: 0;
            transition: opacity 0.15s ease;
        }

        .polaris-anim-card.is-selected .anim-card-radio .radio-circle {
            opacity: 1;
        }

        /* Animation Keyframes */
        @keyframes sn-bounce {
            0%, 100% { transform: translateY(0); }
            35% { transform: translateY(-7px) scale(1.06); }
            65% { transform: translateY(-2px) scale(1.02); }
            85% { transform: translateY(-4px); }
        }
        @keyframes sn-swing {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(9deg); }
            40% { transform: rotate(-7deg); }
            60% { transform: rotate(4deg); }
            80% { transform: rotate(-2deg); }
        }

        /* Mini card animation triggers */
        .polaris-anim-card:hover .mini-anim-none { transform: none !important; }
        .polaris-anim-card:hover .mini-anim-scale { transform: scale(1.22) !important; }
        .polaris-anim-card:hover .mini-anim-bounce { animation: sn-bounce 0.52s cubic-bezier(0.36, 0.07, 0.19, 0.97) both !important; }
        .polaris-anim-card:hover .mini-anim-lift { transform: translateY(-4px) !important; box-shadow: 0 6px 14px rgba(79, 70, 229, 0.4) !important; }
        .polaris-anim-card:hover .mini-anim-glow { transform: scale(1.05) !important; box-shadow: 0 0 14px var(--primary) !important; }
        .polaris-anim-card:hover .mini-anim-swing { animation: sn-swing 0.55s ease-in-out both !important; }

        /* Textarea and Helper Tags */
        .polaris-textarea {
            width: 100%;
            padding: 0.65rem 0.85rem;
            font-size: 0.84375rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: var(--text-main);
            font-family: inherit;
            resize: vertical;
            line-height: 1.5;
            transition: all 0.15s ease;
        }

        .polaris-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }

        .textarea-hint-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.35rem;
            flex-wrap: wrap;
        }

        .placeholder-chip {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 7px;
            font-size: 0.71875rem;
            font-weight: 600;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .placeholder-chip:hover {
            background: var(--primary-light);
            border-color: var(--primary-border);
        }

        /* Polaris Contextual Sticky Save Bar */
        .polaris-save-bar {
            position: fixed;
            bottom: -80px;
            left: 280px;
            right: 2rem;
            z-index: 100;
            transition: bottom 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @media (max-width: 900px) {
            .polaris-save-bar {
                left: 1rem;
                right: 1rem;
            }
        }

        .polaris-save-bar.is-visible {
            bottom: 1.5rem;
        }

        .save-bar-inner {
            background: #0f172a;
            color: #ffffff;
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        }

        .save-bar-info {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .save-bar-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--warning);
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3);
        }

        .save-bar-text {
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .save-bar-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Right Column: Preview Card Styling */

        .preview-card {
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.06);
        }

        .preview-card-header {
            background: #f8fafc;
            padding: 0.65rem 0.9rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .browser-dots {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .b-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }
        .b-dot-red { background: #ef4444; }
        .b-dot-yellow { background: #f59e0b; }
        .b-dot-green { background: #10b981; }

        .browser-address-pill {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            padding: 0.2rem 0.65rem;
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .viewport-toggle {
            display: flex;
            background: #e2e8f0;
            border-radius: 6px;
            padding: 2px;
            gap: 2px;
        }

        .v-btn {
            background: transparent;
            border: none;
            padding: 3px 6px;
            border-radius: 4px;
            cursor: pointer;
             
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .v-btn.is-active {
            background: #ffffff;
            color: var(--text-main);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Canvas Viewport */
        .preview-canvas-viewport {
            background: #e2e8f0;
            padding: 0.75rem;
            display: flex;
            justify-content: center;
            transition: all 0.25s ease;
        }

        .preview-screen-frame {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            width: 100%;
            min-height: 350px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: max-width 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-radius 0.25s ease;
        }

        /* Mobile Simulation Style */
        .preview-screen-frame.is-mobile {
            max-width: 320px;
            border-radius: 20px;
            border: 4px solid #334155;
            min-height: 390px;
        }

        .sim-header {
            padding: 0.65rem 0.9rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .sim-logo-mark {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            background: var(--primary);
        }

        .sim-cart-badge {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
            padding: 1px 6px;
            border-radius: 4px;
        }

        .sim-hero-banner {
            padding: 1.1rem 0.85rem;
            text-align: center;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            margin: 0.5rem 0.5rem 1.25rem;
            border-radius: 6px;
        }

        .sim-tag {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--primary);
        }

        .sim-hero-title {
            font-size: 0.9375rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0.15rem 0;
        }

        .sim-hero-desc {
            font-size: 0.6875rem;
             
        }

        .preview-disabled-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(15, 23, 42, 0.88);
            color: #ffffff;
            padding: 0.45rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            z-index: 20;
            backdrop-filter: blur(4px);
        }

        /* Floating Preview Dock */
        .preview-floating-dock {
            position: absolute;
            display: flex;
            gap: 0.35rem;
            padding: 0.35rem 0.5rem;
            background: #ffffff;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            border-radius: 9999px;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 10;
        }

        .preview-icon-item {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
            position: relative;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .preview-icon-item img {
            width: 20px;
            height: 20px;
            object-fit: contain;
            display: block;
        }

        .preview-icon-item svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
        }

        .preview-tooltip {
            position: absolute;
            bottom: 115%;
            left: 50%;
            transform: translateX(-50%);
            background: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 30;
        }

        .preview-icon-item:hover .preview-tooltip {
            opacity: 1;
        }

        /* Preview Dock Hover Animations */
        .preview-floating-dock.sn-anim-none .preview-icon-item:hover {
            transform: none !important;
            animation: none !important;
        }

        .preview-floating-dock.sn-anim-scale .preview-icon-item:hover {
            transform: scale(1.16) !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.22) !important;
        }

        .preview-floating-dock.sn-anim-bounce .preview-icon-item:hover {
            animation: sn-bounce 0.52s cubic-bezier(0.36, 0.07, 0.19, 0.97) both !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.22) !important;
        }

        .preview-floating-dock.sn-anim-lift .preview-icon-item:hover,
        .preview-floating-dock:not([class*="sn-anim-"]) .preview-icon-item:hover {
            transform: translateY(-4px) scale(1.04) !important;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.25) !important;
        }

        .preview-floating-dock.sn-anim-glow .preview-icon-item:hover {
            transform: scale(1.05) !important;
            box-shadow: 0 0 14px rgba(255, 255, 255, 0.9), 0 0 20px var(--primary) !important;
        }

        .preview-floating-dock.sn-anim-swing .preview-icon-item:hover {
            animation: sn-swing 0.55s ease-in-out both !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.22) !important;
        }

        /* Polish: Hide developer-facing Live & Authenticated badge on storefront settings */
        .top-actions .badge-live {
            display: none !important;
        }

        /* Product Share Button Preview in Settings */
        .sn-product-share-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.2;
            padding: 10px 18px;
            border-radius: 6px;
            user-select: none;
            min-height: 40px;
            box-sizing: border-box;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .sn-product-share-icon {
            width: 18px;
            height: 18px;
            min-width: 18px;
            min-height: 18px;
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }
        .sn-share-btn-outline {
            background: transparent;
            color: #111827;
            border: 1.5px solid #d1d5db;
        }
        .sn-share-btn-solid {
            background: #111827;
            color: #ffffff;
            border: 1.5px solid #111827;
        }
        .sn-share-btn-minimal {
            background: transparent;
            color: #374151;
            border: 1.5px solid transparent;
        }

        /* ========================================================
           Floating Custom CTA Preview Styles (Shared & Isolated)
           ======================================================== */
        .socialnest-floating-cta {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            border-radius: 9999px !important;
            text-decoration: none !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16), 0 2px 5px rgba(0, 0, 0, 0.08) !important;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, filter 0.2s ease !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            box-sizing: border-box !important;
            line-height: 1.2 !important;
            outline: none !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            max-width: calc(100% - 24px) !important;
        }

        .socialnest-floating-cta:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.22), 0 3px 8px rgba(0, 0, 0, 0.1) !important;
            filter: brightness(1.06) !important;
        }

        /* Sizes */
        .socialnest-floating-cta--compact {
            padding: 6px 14px !important;
            min-height: 32px !important;
        }
        .socialnest-floating-cta--compact .socialnest-floating-cta__icon {
            width: 16px !important;
            height: 16px !important;
        }
        .socialnest-floating-cta--compact .socialnest-floating-cta__app-name {
            font-size: 9px !important;
        }
        .socialnest-floating-cta--compact .socialnest-floating-cta__text {
            font-size: 12px !important;
        }

        .socialnest-floating-cta--medium {
            padding: 8px 18px !important;
            min-height: 38px !important;
        }
        .socialnest-floating-cta--medium .socialnest-floating-cta__icon {
            width: 18px !important;
            height: 18px !important;
        }
        .socialnest-floating-cta--medium .socialnest-floating-cta__app-name {
            font-size: 10px !important;
        }
        .socialnest-floating-cta--medium .socialnest-floating-cta__text {
            font-size: 13px !important;
        }

        .socialnest-floating-cta--large {
            padding: 11px 24px !important;
            min-height: 44px !important;
        }
        .socialnest-floating-cta--large .socialnest-floating-cta__icon {
            width: 22px !important;
            height: 22px !important;
        }
        .socialnest-floating-cta--large .socialnest-floating-cta__app-name {
            font-size: 11px !important;
        }
        .socialnest-floating-cta--large .socialnest-floating-cta__text {
            font-size: 15px !important;
        }

        /* Icon & Sub-elements */
        .socialnest-floating-cta__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .socialnest-floating-cta__icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }

        .socialnest-floating-cta__icon[style*="display: none"],
        .socialnest-floating-cta__icon img[style*="display: none"],
        .socialnest-floating-cta__icon img:not([src]),
        .socialnest-floating-cta__icon img[src=""] {
            display: none !important;
        }

        .socialnest-floating-cta__def-icon {
            width: 1.15em !important;
            height: 1.15em !important;
            display: inline-block !important;
            background-color: currentColor !important;
            -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12h14'/%3E%3Cpath d='m12 5 7 7-7 7'/%3E%3C/svg%3E") no-repeat center / contain !important;
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12h14'/%3E%3Cpath d='m12 5 7 7-7 7'/%3E%3C/svg%3E") no-repeat center / contain !important;
        }

        .socialnest-floating-cta__content {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            justify-content: center !important;
            line-height: 1.15 !important;
        }

        .socialnest-floating-cta__app-name {
            opacity: 0.85 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            font-weight: 600 !important;
            margin-bottom: 2px !important;
            display: block !important;
        }

        .socialnest-floating-cta__text {
            font-weight: 700 !important;
            white-space: nowrap !important;
            display: block !important;
        }

        /* Specific rule for the live Storefront Preview overlay */
        #liveCtaPreview {
            position: absolute !important;
            bottom: 12px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 20 !important;
            cursor: default !important;
            pointer-events: auto !important;
        }

        #liveCtaPreview:hover {
            transform: translateX(-50%) translateY(-2px) !important;
        }

        /* Compact Google-Style Color Picker Styles */
        .color-picker-trigger-btn {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            width: 140px;
            max-width: 100%;
            height: 36px;
            min-height: 20px;
            max-height: 50px;
            padding: 0 10px;
            background: #ffffff;
            border: 1px solid #8c9196;
            border-radius: 8px;
            cursor: pointer;
            user-select: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-sizing: border-box;
        }

        .color-picker-trigger-btn:hover,
        .color-picker-trigger-btn:focus-visible {
            border-color: #5c5f62;
            outline: none;
            box-shadow: 0 0 0 1px #5c5f62;
        }

        .color-picker-trigger-btn.is-open {
            border-color: #008060;
            box-shadow: 0 0 0 2px rgba(0, 128, 96, 0.2);
        }

        .color-swatch-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            flex-shrink: 0;
            background-image: repeating-conic-gradient(#cbd5e1 0% 25%, #ffffff 0% 50%) 50% / 8px 8px;
            border: 1px solid rgba(0, 0, 0, 0.18);
            overflow: hidden;
            position: relative;
        }

        .color-swatch-fill {
            width: 100%;
            height: 100%;
            background-color: rgba(17, 24, 39, 1);
        }

        .color-hex-text {
            font-size: 0.8125rem;
            font-weight: 500;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
            color: #202223;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .color-chevron-icon {
            flex-shrink: 0;
            color:  #000000;
            transition: transform 0.2s ease;
        }

        .color-picker-trigger-btn.is-open .color-chevron-icon {
            transform: rotate(180deg);
        }

        /* Floating Google-Style Color Picker Popover */
        .google-color-picker-popover {
            position: fixed;
            z-index: 999999;
            width: 320px;
            background: #ffffff;
            border: 1px solid #dadce0;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.16), 0 1px 4px rgba(0, 0, 0, 0.08);
            padding: 12px;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #202124;
            user-select: none;
        }

        .cp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .cp-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #202124;
        }

        .cp-close-btn {
            border: none;
            background: transparent;
            font-size: 1.25rem;
            line-height: 1;
            color: #5f6368;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cp-close-btn:hover {
            background: #f1f3f4;
            color: #202124;
        }

        /* 2D Area */
        .cp-sat-val-area {
            position: relative;
            width: 100%;
            height: 140px;
            border-radius: 4px;
            overflow: hidden;
            cursor: crosshair;
            touch-action: none;
            background-color: #ff0000;
            outline: none;
        }

        .cp-sat-val-area:focus-visible {
            box-shadow: 0 0 0 2px #1a73e8;
        }

        .cp-sat-layer {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, #ffffff, rgba(255, 255, 255, 0));
        }

        .cp-val-layer {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, #000000, rgba(0, 0, 0, 0));
        }

        .cp-sat-val-handle {
            position: absolute;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 0 2px rgba(0, 0, 0, 0.7);
            transform: translate(-50%, -50%);
            pointer-events: none;
            left: 100%;
            top: 0%;
        }

        /* Sliders Section */
        .cp-sliders-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
        }

        .cp-preview-swatch-box {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            background-image: repeating-conic-gradient(#cbd5e1 0% 25%, #ffffff 0% 50%) 50% / 6px 6px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.06);
        }

        .cp-preview-swatch-fill {
            width: 100%;
            height: 100%;
            background-color: rgba(17, 24, 39, 1);
        }

        .cp-sliders-stack {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .cp-slider-track {
            position: relative;
            height: 10px;
            border-radius: 5px;
            cursor: pointer;
            touch-action: none;
            outline: none;
        }

        .cp-slider-track:focus-visible {
            box-shadow: 0 0 0 2px #1a73e8;
        }

        .cp-hue-track {
            background: linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);
        }

        .cp-alpha-track-bg {
            background-image: repeating-conic-gradient(#cbd5e1 0% 25%, #ffffff 0% 50%) 50% / 6px 6px;
            overflow: hidden;
        }

        .cp-alpha-gradient {
            position: absolute;
            inset: 0;
            border-radius: 5px;
        }

        .cp-slider-handle {
            position: absolute;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            transform: translate(-50%, -50%);
            top: 50%;
            left: 0%;
            pointer-events: none;
        }

        /* Inputs Section */
        .cp-inputs-container {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cp-inputs-row {
            display: flex;
            gap: 6px;
        }

        .cp-input-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .cp-group-hex {
            flex: 1.4;
        }

        .cp-group-alpha {
            flex: 1;
        }

        .cp-text-input {
            width: 100%;
            height: 28px;
            padding: 0 4px;
            text-align: center;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 0.75rem;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
            color: #202124;
            background: #ffffff;
            box-sizing: border-box;
            transition: border-color 0.15s ease;
        }

        .cp-text-input:focus {
            border-color: #1a73e8;
            outline: none;
            box-shadow: 0 0 0 1px #1a73e8;
        }

        .cp-input-label {
            font-size: 0.65rem;
            color: #5f6368;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
    </style>

    <div class="sn-links-wrap sn-available-platforms">
        @php
            $configuredBuiltInPlatforms = $links->where('platform', '!=', 'other')->pluck('platform')->toArray();
        @endphp
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
                    <button
    type="button"
    onclick="openModal('create')"
    class="sn-chip-btn"
    style="border-color: #c7d2fe; color: #4f46e5; font-weight: 600;"
>
    <span>+ Add Social Link</span>
</button>
                </div>
            </div>
    </div>

    <div class="sn-settings-shell">
        <div class="sn-settings-main">
            <div class="sn-settings-tabs" role="tablist" aria-label="Social storefront settings">
                <button type="button" class="sn-settings-tab is-active" data-settings-tab="configured-links" role="tab" aria-selected="true">Connected Platforms</button>
                <button type="button" class="sn-settings-tab" data-settings-tab="dock-position" role="tab" aria-selected="false">Position</button>
                <button type="button" class="sn-settings-tab" data-settings-tab="icon-appearance" role="tab" aria-selected="false">Appearance</button>
                <button type="button" class="sn-settings-tab" data-settings-tab="hover-animation" role="tab" aria-selected="false">Hover Animation</button>
                <button type="button" class="sn-settings-tab" data-settings-tab="social-links" role="tab" aria-selected="false">Social Links</button>
            </div>

            <form action="{{ route('shopify.settings.update') }}" method="POST" id="storefrontSettingsForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                {{-- Read-only state keeps the shared preview and dirty-state engine in sync with CTA/share settings. --}}
                <div hidden aria-hidden="true">
                    <input type="checkbox" id="productShareEnabledInput" {{ $settings->product_share_enabled ? 'checked' : '' }}>
                    <input type="text" id="product_share_button_text" value="{{ $settings->product_share_button_text ?? 'Share Product' }}">
                    <input type="radio" name="product_share_button_style" value="{{ $settings->product_share_button_style ?? 'outline' }}" checked>
                    <input type="text" id="product_share_button_background_color" value="{{ $settings->product_share_button_background_color ?? 'rgba(17, 24, 39, 1)' }}">
                    <input type="checkbox" id="floatingCtaEnabledInput" {{ $settings->floating_cta_enabled ? 'checked' : '' }}>
                    <input type="text" id="floating_cta_app_name" value="{{ $settings->floating_cta_app_name }}">
                    <input type="text" id="floating_cta_button_text" value="{{ $settings->floating_cta_button_text ?? 'Get Started' }}">
                    <input type="text" id="floating_cta_destination_url" value="{{ $settings->floating_cta_destination_url }}">
                    <input type="checkbox" id="floatingCtaShowIconInput" {{ $settings->floating_cta_show_icon ? 'checked' : '' }}>
                    <input type="text" id="floating_cta_background_color" value="{{ $settings->floating_cta_background_color ?? 'rgba(17, 24, 39, 1)' }}">
                    <input type="text" id="floating_cta_text_color" value="{{ $settings->floating_cta_text_color ?? 'rgba(255, 255, 255, 1)' }}">
                    <input type="radio" name="floating_cta_button_size" value="{{ $settings->floating_cta_button_size ?? 'medium' }}" checked>
                    <input type="checkbox" id="floatingCtaDesktopEnabledInput" {{ $settings->floating_cta_desktop_enabled ? 'checked' : '' }}>
                    <input type="checkbox" id="floatingCtaMobileEnabledInput" {{ $settings->floating_cta_mobile_enabled ? 'checked' : '' }}>
                </div>

                <div class="sn-settings-panel" data-settings-panel="social-links" role="tabpanel">
                <!-- Section E: Tooltips & Sharing -->
                <div class="polaris-card">
                    <div class="polaris-card-header">
                        <div>
                            <h3 class="polaris-card-title">Tooltips & Sharing Message</h3>
                            <p class="polaris-card-subtitle">Platform tooltip labels on hover and prepared WhatsApp sharing template</p>
                        </div>
                    </div>
                    <div class="polaris-card-body">
                        <!-- Platform Tooltips Switch -->
                        <div class="polaris-setting-row" style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--card-border);">
                            <div class="polaris-setting-info">
                                <label for="showTooltipsInput" class="polaris-label" style="cursor: pointer;">Display Platform Tooltips on Hover</label>
                                <p class="polaris-help-text">
                                    Shows the social channel name or custom label in a floating tooltip above each icon when hovered by visitors.
                                </p>
                            </div>
                            <div class="polaris-setting-control">
                                <label class="polaris-switch" for="showTooltipsInput">
                                    <input type="hidden" name="show_tooltips" value="0">
                                    <input type="checkbox" name="show_tooltips" id="showTooltipsInput" value="1" {{ old('show_tooltips', $settings->show_tooltips ? '1' : '0') == '1' ? 'checked' : '' }} onchange="onSettingChanged()">
                                    <span class="polaris-switch-slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- WhatsApp Message -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.35rem;">
                                <label for="whatsapp_message" class="polaris-label">Prepared WhatsApp Sharing Message</label>
                                <span style="font-size: 0.72rem;  " id="whatsappCharCount">0 / 500</span>
                            </div>
                            <textarea id="whatsapp_message" name="whatsapp_message" rows="3" maxlength="500" placeholder="Check out this product: {product_title} - {product_url}" class="polaris-textarea" oninput="onWhatsAppInput(this)">{{ old('whatsapp_message', $settings->whatsapp_message) }}</textarea>
                            <div class="textarea-hint-row">
                                <span style="font-size: 0.75rem;  ">Click placeholder tags to insert:</span>
                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                    <button type="button" class="placeholder-chip" onclick="insertTag('{product_title}')" title="Insert product title tag">+{product_title}</button>
                                    <button type="button" class="placeholder-chip" onclick="insertTag('{product_url}')" title="Insert product URL tag">+{product_url}</button>
                                </div>
                            </div>
                            @error('whatsapp_message')
                                <div class="polaris-field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                </div>
                <div class="sn-settings-panel" data-settings-panel="dock-position" role="tabpanel">
               
                <!-- Section B: Widget Position -->
                <div class="polaris-card">
                    <div class="polaris-card-header">
                        <div>
                            <h3 class="polaris-card-title">Widget Position</h3>
                            <p class="polaris-card-subtitle">Choose where the social dock is anchored on your customer screen</p>
                        </div>
                    </div>
                    <div class="polaris-card-body">
                        <label class="polaris-label" style="margin-bottom: 0.65rem; display: block;">
                            Dock Placement Position <span style="color: var(--danger);">*</span>
                        </label>

                        @php
                            $positions = [
                                'bottom_right' => [
                                    'label' => 'Bottom Right',
                                    'desc' => 'Classic floating corner',
                                    'icon' => 'pos-icon-br',
                                ],
                                'bottom_left' => [
                                    'label' => 'Bottom Left',
                                    'desc' => 'Alternate lower corner',
                                    'icon' => 'pos-icon-bl',
                                ],
                                'vertical_right' => [
                                    'label' => 'Vertical Right',
                                    'desc' => 'Middle-right screen edge',
                                    'icon' => 'pos-icon-vr',
                                ],
                                'vertical_left' => [
                                    'label' => 'Vertical Left',
                                    'desc' => 'Middle-left screen edge',
                                    'icon' => 'pos-icon-vl',
                                ],
                                'custom' => [
                                    'label' => 'Custom Position',
                                    'desc' => 'Custom 9-way alignment & independent desktop/mobile X/Y offsets',
                                    'icon' => 'pos-icon-custom',
                                ],
                            ];
                            $selectedPos = old('position', $settings->position);
                        @endphp

                        <div class="polaris-choice-grid">
                            @foreach($positions as $posKey => $pos)
                                <label class="polaris-choice-card {{ $selectedPos === $posKey ? 'is-selected' : '' }} {{ $posKey === 'custom' ? 'grid-span-full' : '' }}" data-pos="{{ $posKey }}">
                                    <input type="radio" name="position" value="{{ $posKey }}" {{ $selectedPos === $posKey ? 'checked' : '' }} onchange="onPositionChanged(this.value)">
                                    <div class="choice-card-diagram {{ $pos['icon'] }}">
                                        <div class="screen-box">
                                            <div class="screen-dock-dot"></div>
                                        </div>
                                    </div>
                                    <div class="choice-card-text">
                                        <div class="choice-card-title">{{ $pos['label'] }}</div>
                                        <div class="choice-card-desc">{{ $pos['desc'] }}</div>
                                    </div>
                                    <div class="choice-card-radio">
                                        <span class="radio-circle"></span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <!-- Conditional Custom Position Controls Panel -->
                        <div id="customPositionPanel" class="polaris-subcard" style="display: {{ $selectedPos === 'custom' ? 'block' : 'none' }}; margin-top: 1.25rem;">
                            <div class="subcard-header">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                                    <strong style="font-size: 0.875rem; color: var(--text-main);">Custom Placement & Viewport Offsets</strong>
                                </div>
                                <span class="polaris-badge polaris-badge-info">Pixel-Perfect</span>
                            </div>
                            <p class="polaris-help-text" style="margin-bottom: 1.25rem;">
                                Select viewport edge alignment and adjust pixel offsets (0–200 px) independently for desktop and mobile devices.
                            </p>

                            <!-- Alignment Segmented Controls -->
                            <div class="form-row-2col" style="margin-bottom: 1.25rem;">
                                <!-- Horizontal Alignment -->
                                <div>
                                    <label class="polaris-label" style="font-size: 0.75rem; margin-bottom: 0.35rem;">Horizontal Alignment</label>
                                    <div class="polaris-segmented-control">
                                        @php $curHAlign = old('custom_horizontal_align', $settings->custom_horizontal_align ?? 'right'); @endphp
                                        @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $hVal => $hLabel)
                                            <label class="segmented-item {{ $curHAlign === $hVal ? 'is-active' : '' }}" data-align-type="h" data-align="{{ $hVal }}">
                                                <input type="radio" name="custom_horizontal_align" value="{{ $hVal }}" {{ $curHAlign === $hVal ? 'checked' : '' }} onchange="onAlignChanged('h', this.value)">
                                                <span>{{ $hLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Vertical Alignment -->
                                <div>
                                    <label class="polaris-label" style="font-size: 0.75rem; margin-bottom: 0.35rem;">Vertical Alignment</label>
                                    <div class="polaris-segmented-control">
                                        @php $curVAlign = old('custom_vertical_align', $settings->custom_vertical_align ?? 'bottom'); @endphp
                                        @foreach(['top' => 'Top', 'center' => 'Center', 'bottom' => 'Bottom'] as $vVal => $vLabel)
                                            <label class="segmented-item {{ $curVAlign === $vVal ? 'is-active' : '' }}" data-align-type="v" data-align="{{ $vVal }}">
                                                <input type="radio" name="custom_vertical_align" value="{{ $vVal }}" {{ $curVAlign === $vVal ? 'checked' : '' }} onchange="onAlignChanged('v', this.value)">
                                                <span>{{ $vLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Desktop Offsets Section -->
                            <div class="offset-device-box" style="margin-bottom: 1rem;">
                                <div class="offset-box-header">
                                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                        <span style="font-size: 0.8125rem; font-weight: 700; color: var(--text-main);">Desktop Viewport Offsets</span>
                                    </div>
                                    <span style="font-size: 0.7rem;  ">Laptops & Desktops</span>
                                </div>
                                <div class="form-row-2col">
                                    <!-- Desktop X -->
                                    <div class="offset-control">
                                        <div class="offset-control-top">
                                            <span class="offset-name">Horizontal Offset (X)</span>
                                            <span class="offset-val-badge" id="label_offset_x_desktop">{{ old('custom_offset_x_desktop', $settings->custom_offset_x_desktop ?? 24) }}px</span>
                                        </div>
                                        <div class="offset-inputs-group">
                                            <input type="range" min="0" max="200" step="1" id="slider_offset_x_desktop" value="{{ old('custom_offset_x_desktop', $settings->custom_offset_x_desktop ?? 24) }}" oninput="syncOffset('custom_offset_x_desktop', this.value, 'slider')" class="polaris-slider" aria-label="Desktop horizontal offset slider">
                                            <input type="number" min="0" max="200" step="1" name="custom_offset_x_desktop" id="custom_offset_x_desktop" value="{{ old('custom_offset_x_desktop', $settings->custom_offset_x_desktop ?? 24) }}" oninput="syncOffset('custom_offset_x_desktop', this.value, 'number')" class="polaris-number-input" aria-label="Desktop horizontal offset px">
                                        </div>
                                    </div>
                                    <!-- Desktop Y -->
                                    <div class="offset-control">
                                        <div class="offset-control-top">
                                            <span class="offset-name">Vertical Offset (Y)</span>
                                            <span class="offset-val-badge" id="label_offset_y_desktop">{{ old('custom_offset_y_desktop', $settings->custom_offset_y_desktop ?? 24) }}px</span>
                                        </div>
                                        <div class="offset-inputs-group">
                                            <input type="range" min="0" max="200" step="1" id="slider_offset_y_desktop" value="{{ old('custom_offset_y_desktop', $settings->custom_offset_y_desktop ?? 24) }}" oninput="syncOffset('custom_offset_y_desktop', this.value, 'slider')" class="polaris-slider" aria-label="Desktop vertical offset slider">
                                            <input type="number" min="0" max="200" step="1" name="custom_offset_y_desktop" id="custom_offset_y_desktop" value="{{ old('custom_offset_y_desktop', $settings->custom_offset_y_desktop ?? 24) }}" oninput="syncOffset('custom_offset_y_desktop', this.value, 'number')" class="polaris-number-input" aria-label="Desktop vertical offset px">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Offsets Section -->
                            <div class="offset-device-box">
                                <div class="offset-box-header">
                                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                        <span style="font-size: 0.8125rem; font-weight: 700; color: var(--text-main);">Mobile Screen Offsets</span>
                                    </div>
                                    <span style="font-size: 0.7rem;  ">Smartphones & Tablets</span>
                                </div>
                                <div class="form-row-2col">
                                    <!-- Mobile X -->
                                    <div class="offset-control">
                                        <div class="offset-control-top">
                                            <span class="offset-name">Mobile Offset (X)</span>
                                            <span class="offset-val-badge" id="label_offset_x_mobile">{{ old('custom_offset_x_mobile', $settings->custom_offset_x_mobile ?? 16) }}px</span>
                                        </div>
                                        <div class="offset-inputs-group">
                                            <input type="range" min="0" max="200" step="1" id="slider_offset_x_mobile" value="{{ old('custom_offset_x_mobile', $settings->custom_offset_x_mobile ?? 16) }}" oninput="syncOffset('custom_offset_x_mobile', this.value, 'slider')" class="polaris-slider" aria-label="Mobile horizontal offset slider">
                                            <input type="number" min="0" max="200" step="1" name="custom_offset_x_mobile" id="custom_offset_x_mobile" value="{{ old('custom_offset_x_mobile', $settings->custom_offset_x_mobile ?? 16) }}" oninput="syncOffset('custom_offset_x_mobile', this.value, 'number')" class="polaris-number-input" aria-label="Mobile horizontal offset px">
                                        </div>
                                    </div>
                                    <!-- Mobile Y -->
                                    <div class="offset-control">
                                        <div class="offset-control-top">
                                            <span class="offset-name">Mobile Offset (Y)</span>
                                            <span class="offset-val-badge" id="label_offset_y_mobile">{{ old('custom_offset_y_mobile', $settings->custom_offset_y_mobile ?? 16) }}px</span>
                                        </div>
                                        <div class="offset-inputs-group">
                                            <input type="range" min="0" max="200" step="1" id="slider_offset_y_mobile" value="{{ old('custom_offset_y_mobile', $settings->custom_offset_y_mobile ?? 16) }}" oninput="syncOffset('custom_offset_y_mobile', this.value, 'slider')" class="polaris-slider" aria-label="Mobile vertical offset slider">
                                            <input type="number" min="0" max="200" step="1" name="custom_offset_y_mobile" id="custom_offset_y_mobile" value="{{ old('custom_offset_y_mobile', $settings->custom_offset_y_mobile ?? 16) }}" oninput="syncOffset('custom_offset_y_mobile', this.value, 'number')" class="polaris-number-input" aria-label="Mobile vertical offset px">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                </div>
                <div class="sn-settings-panel" data-settings-panel="icon-appearance" role="tabpanel">
                <!-- Section C: Appearance -->
                <div class="polaris-card">
                    <div class="polaris-card-header">
                        <div>
                            <h3 class="polaris-card-title">Icon Appearance</h3>
                            <p class="polaris-card-subtitle">Scale and geometry of floating social dock icons</p>
                        </div>
                    </div>
                    <div class="polaris-card-body">
                        <div class="form-row-2col">
                            <!-- Icon Size -->
                            <div>
                                <label for="icon_size" class="polaris-label" style="margin-bottom: 0.5rem; display: block;">Icon Size</label>
                                @php $curSize = old('icon_size', $settings->icon_size); @endphp
                                <div class="polaris-segmented-control polaris-segmented-tall">
                                    <label class="segmented-item {{ $curSize === 'small' ? 'is-active' : '' }}" data-size="small">
                                        <input type="radio" name="icon_size" value="small" {{ $curSize === 'small' ? 'checked' : '' }} onchange="onSizeChanged(this.value)">
                                        <span class="size-dot size-dot-sm"></span>
                                        <span>Small (32px)</span>
                                    </label>
                                    <label class="segmented-item {{ $curSize === 'medium' ? 'is-active' : '' }}" data-size="medium">
                                        <input type="radio" name="icon_size" value="medium" {{ $curSize === 'medium' ? 'checked' : '' }} onchange="onSizeChanged(this.value)">
                                        <span class="size-dot size-dot-md"></span>
                                        <span>Medium (40px)</span>
                                    </label>
                                    <label class="segmented-item {{ $curSize === 'large' ? 'is-active' : '' }}" data-size="large">
                                        <input type="radio" name="icon_size" value="large" {{ $curSize === 'large' ? 'checked' : '' }} onchange="onSizeChanged(this.value)">
                                        <span class="size-dot size-dot-lg"></span>
                                        <span>Large (48px)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Icon Shape -->
                            <div>
                                <label for="icon_shape" class="polaris-label" style="margin-bottom: 0.5rem; display: block;">Icon Shape</label>
                                @php $curShape = old('icon_shape', $settings->icon_shape); @endphp
                                <div class="polaris-segmented-control polaris-segmented-tall">
                                    <label class="segmented-item {{ $curShape === 'circle' ? 'is-active' : '' }}" data-shape="circle">
                                        <input type="radio" name="icon_shape" value="circle" {{ $curShape === 'circle' ? 'checked' : '' }} onchange="onShapeChanged(this.value)">
                                        <span class="shape-preview shape-preview-circle"></span>
                                        <span>Circle</span>
                                    </label>
                                    <label class="segmented-item {{ $curShape === 'rounded' ? 'is-active' : '' }}" data-shape="rounded">
                                        <input type="radio" name="icon_shape" value="rounded" {{ $curShape === 'rounded' ? 'checked' : '' }} onchange="onShapeChanged(this.value)">
                                        <span class="shape-preview shape-preview-rounded"></span>
                                        <span>Rounded</span>
                                    </label>
                                    <label class="segmented-item {{ $curShape === 'square' ? 'is-active' : '' }}" data-shape="square">
                                        <input type="radio" name="icon_shape" value="square" {{ $curShape === 'square' ? 'checked' : '' }} onchange="onShapeChanged(this.value)">
                                        <span class="shape-preview shape-preview-square"></span>
                                        <span>Square</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                </div>
                <div class="sn-settings-panel" data-settings-panel="hover-animation" role="tabpanel">
                <!-- Section D: Hover Animation Effects -->
                <div class="polaris-card">
                    <div class="polaris-card-header">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <h3 class="polaris-card-title">Hover Animation Effects</h3>
                                <span class="polaris-badge polaris-badge-success">All Free</span>
                            </div>
                            <p class="polaris-card-subtitle">Subtle interactive animations triggered when shoppers hover their cursor over icons</p>
                        </div>
                    </div>
                    <div class="polaris-card-body">
                        @php
                            $curAnim = old('hover_animation', $settings->hover_animation ?? 'lift');
                            $animOptions = [
                                'none' => ['label' => 'None', 'desc' => 'No hover animation', 'preview' => 'mini-anim-none', 'tag' => null],
                                'scale' => ['label' => 'Scale', 'desc' => 'Smooth zoom in slightly', 'preview' => 'mini-anim-scale', 'tag' => null],
                                'bounce' => ['label' => 'Bounce', 'desc' => 'Subtle lively bounce', 'preview' => 'mini-anim-bounce', 'tag' => null],
                                'lift' => ['label' => 'Lift', 'desc' => 'Moves slightly upward', 'preview' => 'mini-anim-lift', 'tag' => 'Default'],
                                'glow' => ['label' => 'Glow', 'desc' => 'Soft radial aura & glow', 'preview' => 'mini-anim-glow', 'tag' => null],
                                'swing' => ['label' => 'Swing', 'desc' => 'Smooth tilt left and right', 'preview' => 'mini-anim-swing', 'tag' => null],
                            ];
                        @endphp

                        <div class="polaris-anim-grid">
                            @foreach($animOptions as $aKey => $aData)
                                <label class="polaris-anim-card {{ $curAnim === $aKey ? 'is-selected' : '' }}" data-anim="{{ $aKey }}">
                                    <input type="radio" name="hover_animation" value="{{ $aKey }}" {{ $curAnim === $aKey ? 'checked' : '' }} onchange="onAnimationChanged(this.value)">
                                    
                                    <div class="anim-card-top">
                                        <div class="mini-anim-icon {{ $aData['preview'] }}" title="Hover to test animation">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                        </div>
                                        @if($aData['tag'])
                                            <span class="polaris-badge polaris-badge-primary" style="font-size: 0.65rem; padding: 1px 6px;">{{ $aData['tag'] }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="anim-card-body">
                                        <div class="anim-card-title">{{ $aData['label'] }}</div>
                                        <div class="anim-card-desc">{{ $aData['desc'] }}</div>
                                    </div>

                                    <div class="anim-card-radio">
                                        <span class="radio-circle"></span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('hover_animation')
                            <div class="polaris-field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>


                </div>
                <!-- Sticky Bottom Save Bar (Polaris Contextual Bar) -->
                <div class="polaris-save-bar" id="polarisSaveBar">
                    <div class="save-bar-inner">
                        <div class="save-bar-info">
                            <span class="save-bar-dot"></span>
                            <span class="save-bar-text">You have unsaved changes</span>
                        </div>
                        <div class="save-bar-actions">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="resetFormToSaved()" id="btnResetSettings">Discard</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btnSaveSettings">Save Settings</button>
                        </div>
                    </div>
                </div>


            </form>
            <div class="sn-settings-panel is-active" data-settings-panel="configured-links" role="tabpanel">
                <div class="sn-card sn-channels-card">
                    <div class="sn-card-head">
                        <div>
                            <h3 class="sn-card-title">Connected Platforms ({{ $links->count() }})</h3>
                            <p class="sn-card-subtitle">Select a channel to control its storefront visibility. Use the actions to edit or delete its configuration.</p>
                        </div>
                    </div>
                    @if($links->isEmpty())
                        <div class="sn-empty-wrap">
                            <div class="sn-empty-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div>
                            <h4 class="sn-empty-title">No social links configured yet</h4>
                            <button type="button" onclick="openModal('create')" class="btn btn-primary btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <span>Add First Social Link</span>
                            </button>
                        </div>
                    @else
                        <div class="sn-configured-grid">
                            @foreach($links as $link)
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
                                <div class="sn-configured-item">
                                    <form action="{{ route('shopify.social-links.toggle', $link->id) }}" method="POST" class="sn-configured-toggle-form">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sn-configured-toggle" title="{{ $link->is_active ? 'Visible on storefront' : 'Hidden from storefront' }}">
                                            <input type="checkbox" {{ $link->is_active ? 'checked' : '' }} onchange="this.form.submit()" aria-label="Show {{ $link->platform_name }} on storefront">
                                            <span class="sn-configured-checkmark"></span>
                                        </label>
                                    </form>
                                    <div class="sn-configured-platform-icon" style="background: {{ $link->brand_color }};">{!! $link->icon_html !!}</div>
                                    <div class="sn-configured-platform-info">
                                        <div class="sn-configured-platform-name">
                                            {{ $link->platform_name }}
                                            @if($link->isCustom()) <span class="sn-custom-badge">Custom</span> @endif
                                        </div>
                                        <div class="sn-configured-state {{ $link->is_active ? 'is-active' : 'is-inactive' }}">
                                            {{ $link->is_active ? 'Active on storefront' : 'Hidden from storefront' }}
                                        </div>
                                    </div>
                                    <div class="sn-configured-actions">
                                        <button type="button" class="sn-btn-action sn-btn-edit sn-icon-action" onclick="openModal('edit', {{ $editData }})" title="Edit {{ $link->platform_name }}" aria-label="Edit {{ $link->platform_name }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                        </button>
                                        <form action="{{ route('shopify.social-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this link?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sn-btn-action sn-btn-delete sn-icon-action" title="Delete {{ $link->platform_name }}" aria-label="Delete {{ $link->platform_name }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
        <div class="sn-settings-preview">
        <!-- Right Column: Interactive Live Simulation Preview -->
        <div class="settings-preview-column">
            <div class="preview-sticky-wrap">
                <!-- Preview Frame Card -->
                <div class="polaris-card preview-card">
                    <div class="preview-card-header">
                        <div class="browser-dots">
                            <span class="b-dot b-dot-red"></span>
                            <span class="b-dot b-dot-yellow"></span>
                            <span class="b-dot b-dot-green"></span>
                        </div>
                        <div class="browser-address-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>{{ $shop }}</span>
                        </div>
                        <!-- Viewport Switcher -->
                        <div class="viewport-toggle">
                            <button type="button" class="v-btn is-active" id="btnViewDesktop" onclick="setPreviewDevice('desktop')" title="Simulate Desktop Viewport">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            </button>
                            <button type="button" class="v-btn" id="btnViewMobile" onclick="setPreviewDevice('mobile')" title="Simulate Mobile Screen">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Storefront Canvas Simulation -->
                    <div class="preview-canvas-viewport" id="previewViewport">
                        <div class="preview-screen-frame" id="previewScreen">
                            <!-- Simulated Header -->
                            <div class="sim-header">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div class="sim-logo-mark"></div>
                                    <span style="font-size: 0.75rem; font-weight: 800; color: var(--text-main);">{{ str_replace('.myshopify.com', '', $shop) }}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                    <span style="font-size: 0.6875rem;  ">Search</span>
                                    <div class="sim-cart-badge">Cart (0)</div>
                                </div>
                            </div>

                            <!-- Simulated Hero Banner -->
                            <div class="sim-hero-banner">
                                <span class="sim-tag">New Collection</span>
                                <div class="sim-hero-title">Summer Essentials</div>
                                <div class="sim-hero-desc">Discover our latest collection</div>
                            </div>

                            <!-- Disabled Overlay Watermark -->
                            <div class="preview-disabled-badge" id="previewDisabledBadge" style="display: none;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                <span>Widget Disabled in Settings</span>
                            </div>

                            <!-- Live Floating Social Dock -->
                            <div id="liveDockPreview" class="preview-floating-dock sn-anim-{{ $curAnim }}">
                                @if($activeLinks->isEmpty())
                                    <!-- Fallback Sample Active Icons for Instant Preview -->
                                    <div class="preview-icon-item" style="background: #E1306C;" title="Instagram" data-label="Instagram">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                                        <span class="preview-tooltip">Instagram</span>
                                    </div>
                                    <div class="preview-icon-item" style="background: #25D366;" title="WhatsApp" data-label="WhatsApp">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                        <span class="preview-tooltip">WhatsApp</span>
                                    </div>
                                    <div class="preview-icon-item" style="background: #000000;" title="TikTok" data-label="TikTok">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                                        <span class="preview-tooltip">TikTok</span>
                                    </div>
                                @else
                                    @foreach($activeLinks as $l)
                                        <div class="preview-icon-item" style="background: {{ $l->brand_color }};" title="{{ $l->label ?: $l->platform_name }}" data-label="{{ $l->label ?: $l->platform_name }}">
                                            {!! $l->icon_html !!}
                                            <span class="preview-tooltip">{{ $l->label ?: $l->platform_name }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Live Floating Custom CTA Overlay in Preview -->
                            <div id="liveCtaPreview" class="socialnest-floating-cta socialnest-floating-cta--{{ $settings->floating_cta_button_size ?? 'medium' }}" style="position: absolute !important; bottom: 12px !important; left: 50% !important; transform: translateX(-50%) !important; z-index: 20 !important; cursor: default !important; background: {{ $settings->floating_cta_background_color ?? '#111827' }}; color: {{ $settings->floating_cta_text_color ?? '#ffffff' }}; text-decoration: none !important; {{ $settings->floating_cta_enabled ? '' : 'display: none !important;' }}">
                                <span class="socialnest-floating-cta__icon" id="previewScreenCtaIconWrap" style="{{ $settings->floating_cta_show_icon && $settings->getFloatingCtaIconUrl() ? '' : 'display: none !important;' }}">
                                    @if($settings->getFloatingCtaIconUrl())
                                        <img id="previewScreenCtaIconImg" src="{{ $settings->getFloatingCtaIconUrl() }}" alt="CTA Icon">
                                    @else
                                        <img id="previewScreenCtaIconImg" alt="CTA Icon" style="display: none !important;">
                                    @endif
                                </span>
                                <span class="socialnest-floating-cta__content">
                                    <span class="socialnest-floating-cta__text" id="previewScreenCtaText">{{ $settings->floating_cta_button_text ?: 'Shop Now' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </div>

    <script>
        // Storefront Settings State & Live Preview Engine
        let initialSettings = {};
        let currentSettings = {};
        let currentPreviewDevice = 'desktop';
        let beforeUnloadAttached = false;
        let isSaving = false;
        let isSubmitting = false;

        /**
         * Normalize settings object to strictly typed, consistent primitives.
         * Ensures robust equality comparison regardless of boolean format, string trimming, or numeric types.
         */
        function normalize(settings) {
            if (!settings || typeof settings !== 'object') return {};
            return {
                position: String(settings.position || 'bottom_right').trim(),
                custom_horizontal_align: String(settings.custom_horizontal_align || 'right').trim(),
                custom_vertical_align: String(settings.custom_vertical_align || 'bottom').trim(),
                custom_offset_x_desktop: Number.isFinite(Number(settings.custom_offset_x_desktop)) ? Math.max(0, Math.min(200, Math.round(Number(settings.custom_offset_x_desktop)))) : 24,
                custom_offset_y_desktop: Number.isFinite(Number(settings.custom_offset_y_desktop)) ? Math.max(0, Math.min(200, Math.round(Number(settings.custom_offset_y_desktop)))) : 24,
                custom_offset_x_mobile: Number.isFinite(Number(settings.custom_offset_x_mobile)) ? Math.max(0, Math.min(200, Math.round(Number(settings.custom_offset_x_mobile)))) : 16,
                custom_offset_y_mobile: Number.isFinite(Number(settings.custom_offset_y_mobile)) ? Math.max(0, Math.min(200, Math.round(Number(settings.custom_offset_y_mobile)))) : 16,
                icon_size: String(settings.icon_size || 'medium').trim(),
                icon_shape: String(settings.icon_shape || 'circle').trim(),
                hover_animation: String(settings.hover_animation || 'lift').trim(),
                show_tooltips: Boolean(settings.show_tooltips),
                whatsapp_message: settings.whatsapp_message == null ? '' : String(settings.whatsapp_message).trim(),
                product_share_enabled: Boolean(settings.product_share_enabled),
                product_share_button_text: settings.product_share_button_text == null || String(settings.product_share_button_text).trim() === '' ? 'Share Product' : String(settings.product_share_button_text).trim(),
                product_share_button_style: String(settings.product_share_button_style || 'outline').trim(),
                product_share_button_background_color: normalizeColor(settings.product_share_button_background_color || 'rgba(17, 24, 39, 1)'),
                floating_cta_enabled: Boolean(settings.floating_cta_enabled),
                floating_cta_app_name: settings.floating_cta_app_name == null ? '' : String(settings.floating_cta_app_name).trim(),
                floating_cta_button_text: settings.floating_cta_button_text == null || String(settings.floating_cta_button_text).trim() === '' ? 'Shop Now' : String(settings.floating_cta_button_text).trim(),
                floating_cta_destination_url: settings.floating_cta_destination_url == null ? '' : String(settings.floating_cta_destination_url).trim(),
                floating_cta_show_icon: settings.floating_cta_show_icon !== undefined ? Boolean(settings.floating_cta_show_icon) : true,
                floating_cta_background_color: String(settings.floating_cta_background_color || '#111827').toLowerCase().trim(),
                floating_cta_text_color: String(settings.floating_cta_text_color || '#ffffff').toLowerCase().trim(),
                floating_cta_button_size: String(settings.floating_cta_button_size || 'medium').trim(),
                floating_cta_desktop_enabled: settings.floating_cta_desktop_enabled !== undefined ? Boolean(settings.floating_cta_desktop_enabled) : true,
                floating_cta_mobile_enabled: settings.floating_cta_mobile_enabled !== undefined ? Boolean(settings.floating_cta_mobile_enabled) : true,
            };
        }

        /**
         * Read current form values for persisted storefront setting attributes.
         * Excludes non-persisted UI states (device toggle, preview interactions, hidden tokens, etc.)
         */
        function readCurrentSettings() {
            const position = document.querySelector('input[name="position"]:checked')?.value || 'bottom_right';
            const customHAlign = document.querySelector('input[name="custom_horizontal_align"]:checked')?.value || 'right';
            const customVAlign = document.querySelector('input[name="custom_vertical_align"]:checked')?.value || 'bottom';
            const offsetXDesktop = parseInt(document.getElementById('custom_offset_x_desktop')?.value, 10) || 0;
            const offsetYDesktop = parseInt(document.getElementById('custom_offset_y_desktop')?.value, 10) || 0;
            const offsetXMobile = parseInt(document.getElementById('custom_offset_x_mobile')?.value, 10) || 0;
            const offsetYMobile = parseInt(document.getElementById('custom_offset_y_mobile')?.value, 10) || 0;
            const iconSize = document.querySelector('input[name="icon_size"]:checked')?.value || 'medium';
            const iconShape = document.querySelector('input[name="icon_shape"]:checked')?.value || 'circle';
            const hoverAnimation = document.querySelector('input[name="hover_animation"]:checked')?.value || 'lift';
            const showTooltips = !!document.getElementById('showTooltipsInput')?.checked;
            const whatsappMessage = document.getElementById('whatsapp_message')?.value ?? '';
            const productShareEnabled = !!document.getElementById('productShareEnabledInput')?.checked;
            const productShareButtonText = document.getElementById('product_share_button_text')?.value ?? 'Share Product';
            const productShareButtonStyle = document.querySelector('input[name="product_share_button_style"]:checked')?.value || 'outline';
            const productShareButtonBgColor = document.getElementById('product_share_button_background_color')?.value || 'rgba(17, 24, 39, 1)';

            const floatingCtaEnabled = !!document.getElementById('floatingCtaEnabledInput')?.checked;
            const floatingCtaAppName = document.getElementById('floating_cta_app_name')?.value ?? '';
            const floatingCtaButtonText = document.getElementById('floating_cta_button_text')?.value ?? 'Shop Now';
            const floatingCtaDestinationUrl = document.getElementById('floating_cta_destination_url')?.value ?? '';
            const floatingCtaShowIcon = !!document.getElementById('floatingCtaShowIconInput')?.checked;
            const floatingCtaBgColor = document.getElementById('floating_cta_background_color')?.value || '#111827';
            const floatingCtaTextColor = document.getElementById('floating_cta_text_color')?.value || '#ffffff';
            const floatingCtaButtonSize = document.querySelector('input[name="floating_cta_button_size"]:checked')?.value || 'medium';
            const floatingCtaDesktopEnabled = !!document.getElementById('floatingCtaDesktopEnabledInput')?.checked;
            const floatingCtaMobileEnabled = !!document.getElementById('floatingCtaMobileEnabledInput')?.checked;

            return {
                position: position,
                custom_horizontal_align: customHAlign,
                custom_vertical_align: customVAlign,
                custom_offset_x_desktop: offsetXDesktop,
                custom_offset_y_desktop: offsetYDesktop,
                custom_offset_x_mobile: offsetXMobile,
                custom_offset_y_mobile: offsetYMobile,
                icon_size: iconSize,
                icon_shape: iconShape,
                hover_animation: hoverAnimation,
                show_tooltips: showTooltips,
                whatsapp_message: whatsappMessage,
                product_share_enabled: productShareEnabled,
                product_share_button_text: productShareButtonText,
                product_share_button_style: productShareButtonStyle,
                product_share_button_background_color: productShareButtonBgColor,
                floating_cta_enabled: floatingCtaEnabled,
                floating_cta_app_name: floatingCtaAppName,
                floating_cta_button_text: floatingCtaButtonText,
                floating_cta_destination_url: floatingCtaDestinationUrl,
                floating_cta_show_icon: floatingCtaShowIcon,
                floating_cta_background_color: floatingCtaBgColor,
                floating_cta_text_color: floatingCtaTextColor,
                floating_cta_button_size: floatingCtaButtonSize,
                floating_cta_desktop_enabled: floatingCtaDesktopEnabled,
                floating_cta_mobile_enabled: floatingCtaMobileEnabled,
            };
        }

        /**
         * Check if current form settings differ from the initial saved settings.
         */
        function isDirty() {
            currentSettings = normalize(readCurrentSettings());
            const initial = normalize(initialSettings);
            return JSON.stringify(currentSettings) !== JSON.stringify(initial);
        }

        /**
         * Update UI save bar and attach/remove beforeunload listener dynamically.
         */
        function setDirtyState(dirty) {
            const bar = document.getElementById('polarisSaveBar');
            if (bar) {
                if (dirty) {
                    bar.classList.add('is-visible');
                } else {
                    bar.classList.remove('is-visible');
                }
            }

            if (dirty) {
                if (!beforeUnloadAttached) {
                    window.addEventListener('beforeunload', handleBeforeUnload);
                    beforeUnloadAttached = true;
                }
            } else {
                if (beforeUnloadAttached) {
                    window.removeEventListener('beforeunload', handleBeforeUnload);
                    beforeUnloadAttached = false;
                }
            }
        }

        function checkDirtyState() {
            const dirty = isDirty();
            setDirtyState(dirty);
            return dirty;
        }

        /**
         * Restore form to the saved initialSettings state.
         */
        function resetFormToSaved() {
            applySettingsToForm(initialSettings);
            checkDirtyState();
        }

        function applySettingsToForm(settings) {
            const norm = normalize(settings);

           

            // 2. position
            const posRadio = document.querySelector('input[name="position"][value="' + norm.position + '"]');
            if (posRadio) posRadio.checked = true;
            onPositionChanged(norm.position, false);

            // 3. custom alignment
            const hRadio = document.querySelector('input[name="custom_horizontal_align"][value="' + norm.custom_horizontal_align + '"]');
            if (hRadio) hRadio.checked = true;
            onAlignChanged('h', norm.custom_horizontal_align, false);

            const vRadio = document.querySelector('input[name="custom_vertical_align"][value="' + norm.custom_vertical_align + '"]');
            if (vRadio) vRadio.checked = true;
            onAlignChanged('v', norm.custom_vertical_align, false);

            // 4. offsets
            syncOffset('custom_offset_x_desktop', norm.custom_offset_x_desktop, 'init');
            syncOffset('custom_offset_y_desktop', norm.custom_offset_y_desktop, 'init');
            syncOffset('custom_offset_x_mobile', norm.custom_offset_x_mobile, 'init');
            syncOffset('custom_offset_y_mobile', norm.custom_offset_y_mobile, 'init');

            // 5. icon size & shape
            const sizeRadio = document.querySelector('input[name="icon_size"][value="' + norm.icon_size + '"]');
            if (sizeRadio) sizeRadio.checked = true;
            onSizeChanged(norm.icon_size, false);

            const shapeRadio = document.querySelector('input[name="icon_shape"][value="' + norm.icon_shape + '"]');
            if (shapeRadio) shapeRadio.checked = true;
            onShapeChanged(norm.icon_shape, false);

            // 6. hover animation
            const animRadio = document.querySelector('input[name="hover_animation"][value="' + norm.hover_animation + '"]');
            if (animRadio) animRadio.checked = true;
            onAnimationChanged(norm.hover_animation, false);

            // 7. tooltips
            const tooltipsInput = document.getElementById('showTooltipsInput');
            if (tooltipsInput) tooltipsInput.checked = norm.show_tooltips;

            // 8. whatsapp message
            const waInput = document.getElementById('whatsapp_message');
            if (waInput) waInput.value = norm.whatsapp_message;
            updateWhatsAppCharCount();

            // 9. product share
            const psEnabledInput = document.getElementById('productShareEnabledInput');
            if (psEnabledInput) psEnabledInput.checked = norm.product_share_enabled;
            onProductShareChanged();

            const psTextInput = document.getElementById('product_share_button_text');
            if (psTextInput) psTextInput.value = norm.product_share_button_text;
            onProductShareInput();

            const styleRadio = document.querySelector('input[name="product_share_button_style"][value="' + norm.product_share_button_style + '"]');
            if (styleRadio) styleRadio.checked = true;
            onButtonStyleChanged(norm.product_share_button_style, false);

            // 10. product share button background color
            setColorFromExternal(norm.product_share_button_background_color || 'rgba(17, 24, 39, 1)', false);

            // 11. Floating Custom CTA
            const ctaEnabledInput = document.getElementById('floatingCtaEnabledInput');
            if (ctaEnabledInput) ctaEnabledInput.checked = norm.floating_cta_enabled;
            const ctaPanel = document.getElementById('floatingCtaOptionsPanel');
            if (ctaPanel) ctaPanel.style.opacity = norm.floating_cta_enabled ? '1' : '0.5';

            const ctaAppNameInput = document.getElementById('floating_cta_app_name');
            if (ctaAppNameInput) ctaAppNameInput.value = norm.floating_cta_app_name;

            const ctaBtnTextInput = document.getElementById('floating_cta_button_text');
            if (ctaBtnTextInput) ctaBtnTextInput.value = norm.floating_cta_button_text;

            const ctaUrlInput = document.getElementById('floating_cta_destination_url');
            if (ctaUrlInput) ctaUrlInput.value = norm.floating_cta_destination_url;

            const ctaShowIconInput = document.getElementById('floatingCtaShowIconInput');
            if (ctaShowIconInput) ctaShowIconInput.checked = norm.floating_cta_show_icon;

            const ctaBgInput = document.getElementById('floating_cta_background_color');
            if (ctaBgInput) ctaBgInput.value = norm.floating_cta_background_color;
            const ctaBgPicker = document.getElementById('floating_cta_bg_picker');
            if (ctaBgPicker && /^#[0-9a-f]{6}$/i.test(norm.floating_cta_background_color)) ctaBgPicker.value = norm.floating_cta_background_color;

            const ctaTextInput = document.getElementById('floating_cta_text_color');
            if (ctaTextInput) ctaTextInput.value = norm.floating_cta_text_color;
            const ctaTextPicker = document.getElementById('floating_cta_text_picker');
            if (ctaTextPicker && /^#[0-9a-f]{6}$/i.test(norm.floating_cta_text_color)) ctaTextPicker.value = norm.floating_cta_text_color;

            const ctaSizeRadio = document.querySelector('input[name="floating_cta_button_size"][value="' + norm.floating_cta_button_size + '"]');
            if (ctaSizeRadio) ctaSizeRadio.checked = true;
            onCtaSizeChanged(norm.floating_cta_button_size, false);

            const ctaDesktopInput = document.getElementById('floatingCtaDesktopEnabledInput');
            if (ctaDesktopInput) ctaDesktopInput.checked = norm.floating_cta_desktop_enabled;

            const ctaMobileInput = document.getElementById('floatingCtaMobileEnabledInput');
            if (ctaMobileInput) ctaMobileInput.checked = norm.floating_cta_mobile_enabled;

            updateCtaLivePreview();
            updateLivePreview();
        }

        function onSettingChanged() {
            updateLivePreview();
            checkDirtyState();
        }

        function onPositionChanged(posVal, updateDirty = true) {
            document.querySelectorAll('.polaris-choice-card').forEach(card => {
                const isMatch = card.getAttribute('data-pos') === posVal;
                if (isMatch) {
                    card.classList.add('is-selected');
                } else {
                    card.classList.remove('is-selected');
                }
            });

            const panel = document.getElementById('customPositionPanel');
            if (panel) {
                panel.style.display = posVal === 'custom' ? 'block' : 'none';
            }

            updateLivePreview();
            if (updateDirty) checkDirtyState();
        }

        function onAlignChanged(type, alignVal, updateDirty = true) {
            const inputName = 'custom_' + (type === 'h' ? 'horizontal' : 'vertical') + '_align';
            const radio = document.querySelector('input[name="' + inputName + '"][value="' + alignVal + '"]');
            if (radio && !radio.checked) {
                radio.checked = true;
            }

            const items = document.querySelectorAll('.segmented-item[data-align-type="' + type + '"]');
            items.forEach(item => {
                const isMatch = item.getAttribute('data-align') === alignVal;
                if (isMatch) {
                    item.classList.add('is-active');
                } else {
                    item.classList.remove('is-active');
                }
            });

            updateLivePreview();
            if (updateDirty) checkDirtyState();
        }

        function onSizeChanged(sizeVal, updateDirty = true) {
            document.querySelectorAll('.segmented-item[data-size]').forEach(item => {
                const isMatch = item.getAttribute('data-size') === sizeVal;
                if (isMatch) {
                    item.classList.add('is-active');
                } else {
                    item.classList.remove('is-active');
                }
            });

            updateLivePreview();
            if (updateDirty) checkDirtyState();
        }

        function onShapeChanged(shapeVal, updateDirty = true) {
            document.querySelectorAll('.segmented-item[data-shape]').forEach(item => {
                const isMatch = item.getAttribute('data-shape') === shapeVal;
                if (isMatch) {
                    item.classList.add('is-active');
                } else {
                    item.classList.remove('is-active');
                }
            });

            updateLivePreview();
            if (updateDirty) checkDirtyState();
        }

        function onAnimationChanged(animVal, updateDirty = true) {
            document.querySelectorAll('.polaris-anim-card').forEach(card => {
                const isMatch = card.getAttribute('data-anim') === animVal;
                if (isMatch) {
                    card.classList.add('is-selected');
                } else {
                    card.classList.remove('is-selected');
                }
            });

            updateLivePreview();
            if (updateDirty) checkDirtyState();
        }

        function onButtonStyleChanged(styleVal, updateDirty = true) {
            document.querySelectorAll('.segmented-item[data-btn-style]').forEach(item => {
                const isMatch = item.getAttribute('data-btn-style') === styleVal;
                if (isMatch) {
                    item.classList.add('is-active');
                } else {
                    item.classList.remove('is-active');
                }
            });

            const btnPreview = document.getElementById('productShareBtnPreview');
            if (btnPreview) {
                btnPreview.className = 'sn-product-share-btn sn-share-btn-' + styleVal;
                applyButtonPreviewColor(getCurrentColorString(), styleVal);
            }

            if (updateDirty) checkDirtyState();
        }

        function onProductShareChanged() {
            const isEnabled = document.getElementById('productShareEnabledInput')?.checked;
            const panel = document.getElementById('productShareOptionsPanel');
            if (panel) {
                panel.style.opacity = isEnabled ? '1' : '0.5';
            }
            checkDirtyState();
        }

        function onProductShareInput() {
            const input = document.getElementById('product_share_button_text');
            const previewText = document.getElementById('productShareBtnPreviewText');
            if (previewText) {
                previewText.textContent = (input && input.value.trim()) ? input.value : 'Share Product';
            }
            checkDirtyState();
        }

        function syncOffset(fieldId, value, source) {
            const numVal = parseInt(value, 10) || 0;
            const clamped = Math.max(0, Math.min(200, numVal));

            if (source === 'slider') {
                const numInput = document.getElementById(fieldId);
                if (numInput) numInput.value = clamped;
            } else if (source === 'number') {
                const slider = document.getElementById('slider_' + fieldId.replace('custom_', ''));
                if (slider) slider.value = clamped;
            } else {
                const numInput = document.getElementById(fieldId);
                if (numInput) numInput.value = clamped;
                const slider = document.getElementById('slider_' + fieldId.replace('custom_', ''));
                if (slider) slider.value = clamped;
            }

            const label = document.getElementById('label_' + fieldId.replace('custom_', ''));
            if (label) label.textContent = clamped + 'px';

            updateLivePreview();
            if (source !== 'init') {
                checkDirtyState();
            }
        }

        function setPreviewDevice(device) {
            currentPreviewDevice = device;
            const btnDesktop = document.getElementById('btnViewDesktop');
            const btnMobile = document.getElementById('btnViewMobile');
            const screen = document.getElementById('previewScreen');

            if (device === 'mobile') {
                btnMobile?.classList.add('is-active');
                btnDesktop?.classList.remove('is-active');
                screen?.classList.add('is-mobile');
            } else {
                btnDesktop?.classList.add('is-active');
                btnMobile?.classList.remove('is-active');
                screen?.classList.remove('is-mobile');
            }

            updateLivePreview();
        }

        function insertTag(tagText) {
            const textarea = document.getElementById('whatsapp_message');
            if (!textarea) return;

            const start = textarea.selectionStart || 0;
            const end = textarea.selectionEnd || 0;
            const text = textarea.value;

            textarea.value = text.substring(0, start) + tagText + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + tagText.length;

            updateWhatsAppCharCount();
            checkDirtyState();
        }

        function onWhatsAppInput(textarea) {
            updateWhatsAppCharCount();
            checkDirtyState();
        }

        function updateWhatsAppCharCount() {
            const textarea = document.getElementById('whatsapp_message');
            const counter = document.getElementById('whatsappCharCount');
            if (textarea && counter) {
                counter.textContent = textarea.value.length + ' / 500';
            }
        }

        function updateLivePreview() {
            const isEnabled = true;
            const pos = document.querySelector('input[name="position"]:checked')?.value || 'bottom_right';
            const size = document.querySelector('input[name="icon_size"]:checked')?.value || 'medium';
            const shape = document.querySelector('input[name="icon_shape"]:checked')?.value || 'circle';
            const showTooltips = document.getElementById('showTooltipsInput')?.checked;
            const anim = document.querySelector('input[name="hover_animation"]:checked')?.value || 'lift';

            const dock = document.getElementById('liveDockPreview');
            const disabledBadge = document.getElementById('previewDisabledBadge');
            const icons = document.querySelectorAll('.preview-icon-item');

            if (!dock) return;

            // Visibility
            if (isEnabled) {
                dock.style.opacity = '1';
                dock.style.pointerEvents = 'auto';
                if (disabledBadge) disabledBadge.style.display = 'none';
            } else {
                dock.style.opacity = '0.22';
                dock.style.pointerEvents = 'none';
                if (disabledBadge) disabledBadge.style.display = 'flex';
            }

            // Animation class
            dock.className = dock.className.replace(/\bsn-anim-\S+/g, '').trim() + ' sn-anim-' + anim;

            // Reset position styles
            dock.style.top = 'auto';
            dock.style.bottom = 'auto';
            dock.style.left = 'auto';
            dock.style.right = 'auto';
            dock.style.transform = 'none';

            // Check if floating CTA is active & visible on current preview device
            const isCtaActive = !!document.getElementById('floatingCtaEnabledInput')?.checked;
            const isCtaAllowedOnDevice = (currentPreviewDevice === 'mobile') 
                ? !!document.getElementById('floatingCtaMobileEnabledInput')?.checked 
                : !!document.getElementById('floatingCtaDesktopEnabledInput')?.checked;
            const isCtaVisible = isCtaActive && isCtaAllowedOnDevice;

            // Elevate bottom dock whenever floating CTA is visible so they never collide or overlap
            const needsElevation = isCtaVisible;
            const dockBottom = needsElevation ? '64px' : '0.75rem';

            if (pos === 'bottom_right') {
                dock.style.bottom = dockBottom;
                dock.style.right = '0.75rem';
                dock.style.flexDirection = 'row';
                dock.style.borderRadius = '9999px';
            } else if (pos === 'bottom_left') {
                dock.style.bottom = dockBottom;
                dock.style.left = '0.75rem';
                dock.style.flexDirection = 'row';
                dock.style.borderRadius = '9999px';
            } else if (pos === 'vertical_right') {
                dock.style.top = '50%';
                dock.style.right = '0.75rem';
                dock.style.transform = 'translateY(-50%)';
                dock.style.flexDirection = 'column';
                dock.style.borderRadius = '16px';
            } else if (pos === 'vertical_left') {
                dock.style.top = '50%';
                dock.style.left = '0.75rem';
                dock.style.transform = 'translateY(-50%)';
                dock.style.flexDirection = 'column';
                dock.style.borderRadius = '16px';
            } else if (pos === 'custom') {
                const hAlign = document.querySelector('input[name="custom_horizontal_align"]:checked')?.value || 'right';
                const vAlign = document.querySelector('input[name="custom_vertical_align"]:checked')?.value || 'bottom';

                // Use desktop or mobile offset based on currently toggled preview device
                let rawX = 24;
                let rawY = 24;

                if (currentPreviewDevice === 'mobile') {
                    rawX = parseInt(document.getElementById('custom_offset_x_mobile')?.value ?? 16, 10);
                    rawY = parseInt(document.getElementById('custom_offset_y_mobile')?.value ?? 16, 10);
                } else {
                    rawX = parseInt(document.getElementById('custom_offset_x_desktop')?.value ?? 24, 10);
                    rawY = parseInt(document.getElementById('custom_offset_y_desktop')?.value ?? 24, 10);
                }
                if (isNaN(rawX)) rawX = currentPreviewDevice === 'mobile' ? 16 : 24;
                if (isNaN(rawY)) rawY = currentPreviewDevice === 'mobile' ? 16 : 24;

                // Scale proportionally for preview container dimensions (0px raw = 0px scaled)
                const scaleX = Math.round(rawX * 0.4);
                const scaleY = Math.round(rawY * 0.4);

                if (hAlign === 'left') {
                    dock.style.left = scaleX + 'px';
                    dock.style.right = 'auto';
                } else if (hAlign === 'right') {
                    dock.style.right = scaleX + 'px';
                    dock.style.left = 'auto';
                } else {
                    dock.style.left = `calc(50% + ${scaleX}px)`;
                    dock.style.right = 'auto';
                }

                if (vAlign === 'top') {
                    dock.style.top = scaleY + 'px';
                    dock.style.bottom = 'auto';
                } else if (vAlign === 'bottom') {
                    dock.style.bottom = needsElevation ? `calc(${scaleY}px + 64px)` : scaleY + 'px';
                    dock.style.top = 'auto';
                } else {
                    dock.style.top = `calc(50% + ${scaleY}px)`;
                    dock.style.bottom = 'auto';
                }

                if (hAlign === 'center' && vAlign === 'center') {
                    dock.style.transform = 'translate(-50%, -50%)';
                } else if (hAlign === 'center') {
                    dock.style.transform = 'translateX(-50%)';
                } else if (vAlign === 'center') {
                    dock.style.transform = 'translateY(-50%)';
                } else {
                    dock.style.transform = 'none';
                }

                if (vAlign === 'center' && (hAlign === 'left' || hAlign === 'right')) {
                    dock.style.flexDirection = 'column';
                    dock.style.borderRadius = '16px';
                } else {
                    dock.style.flexDirection = 'row';
                    dock.style.borderRadius = '9999px';
                }
            }

            // Size dimensions for preview dock
            let iconPx = '28px';
            if (size === 'small') iconPx = '22px';
            if (size === 'large') iconPx = '34px';

            // Shape radius
            let radius = '50%';
            if (shape === 'rounded') radius = '8px';
            if (shape === 'square') radius = '2px';

            icons.forEach(ic => {
                ic.style.width = iconPx;
                ic.style.height = iconPx;
                ic.style.borderRadius = radius;

                const tip = ic.querySelector('.preview-tooltip');
                if (tip) {
                    tip.style.display = showTooltips ? 'block' : 'none';
                }
            });

            updateCtaLivePreview();
        }

        // Floating Custom CTA Event Handlers & Live Preview
        let uploadedCtaIconDataUrl = null;

        function onCtaSettingChanged() {
            updateLivePreview();
            checkDirtyState();
        }

        function onCtaSizeChanged(sizeVal, updateDirty = true) {
            document.querySelectorAll('.segmented-item[data-cta-size]').forEach(item => {
                item.classList.toggle('is-active', item.getAttribute('data-cta-size') === sizeVal);
            });
            updateLivePreview();
            if (updateDirty) checkDirtyState();
        }

        function onCtaColorSync(type, hexVal) {
            if (type === 'bg') {
                const textInput = document.getElementById('floating_cta_background_color');
                if (textInput) textInput.value = hexVal;
            } else {
                const textInput = document.getElementById('floating_cta_text_color');
                if (textInput) textInput.value = hexVal;
            }
            updateCtaLivePreview();
            checkDirtyState();
        }

        function onCtaColorTextSync(type, hexVal) {
            if (type === 'bg') {
                const picker = document.getElementById('floating_cta_bg_picker');
                if (picker && /^#[0-9a-f]{6}$/i.test(hexVal.trim())) picker.value = hexVal.trim();
            } else {
                const picker = document.getElementById('floating_cta_text_picker');
                if (picker && /^#[0-9a-f]{6}$/i.test(hexVal.trim())) picker.value = hexVal.trim();
            }
            updateCtaLivePreview();
            checkDirtyState();
        }

        function onCtaIconFileSelected(event) {
            const file = event.target?.files?.[0];
            if (!file) return;

            const fnSpan = document.getElementById('ctaIconFilename');
            if (fnSpan) fnSpan.textContent = file.name;

            const removeInput = document.getElementById('remove_floating_cta_icon');
            if (removeInput) removeInput.value = '0';

            const reader = new FileReader();
            reader.onload = function(e) {
                uploadedCtaIconDataUrl = e.target.result;
                const container = document.getElementById('ctaIconPreviewContainer');
                if (container) container.style.display = 'flex';
                const img = document.getElementById('ctaIconImgPreview');
                if (img) img.src = uploadedCtaIconDataUrl;

                updateCtaLivePreview();
                checkDirtyState();
            };
            reader.readAsDataURL(file);
        }

        function removeCtaIcon() {
            uploadedCtaIconDataUrl = null;
            const removeInput = document.getElementById('remove_floating_cta_icon');
            if (removeInput) removeInput.value = '1';

            const fileInput = document.getElementById('floating_cta_icon');
            if (fileInput) fileInput.value = '';

            const container = document.getElementById('ctaIconPreviewContainer');
            if (container) container.style.display = 'none';

            const fnSpan = document.getElementById('ctaIconFilename');
            if (fnSpan) fnSpan.textContent = '';

            updateCtaLivePreview();
            checkDirtyState();
        }

        function updateCtaLivePreview() {
            const isEnabled = !!document.getElementById('floatingCtaEnabledInput')?.checked;
            const panel = document.getElementById('floatingCtaOptionsPanel');
            if (panel) panel.style.opacity = isEnabled ? '1' : '0.5';

            const appName = document.getElementById('floating_cta_app_name')?.value?.trim() || '';
            const btnText = document.getElementById('floating_cta_button_text')?.value?.trim() || 'Shop Now';
            const showIcon = !!document.getElementById('floatingCtaShowIconInput')?.checked;
            const bgColor = document.getElementById('floating_cta_background_color')?.value?.trim() || '#111827';
            const textColor = document.getElementById('floating_cta_text_color')?.value?.trim() || '#ffffff';
            const size = document.querySelector('input[name="floating_cta_button_size"]:checked')?.value || 'medium';
            const desktopEnabled = !!document.getElementById('floatingCtaDesktopEnabledInput')?.checked;
            const mobileEnabled = !!document.getElementById('floatingCtaMobileEnabledInput')?.checked;

            const isIconRemoved = document.getElementById('remove_floating_cta_icon')?.value === '1';
            const ctaPreviewImg = document.getElementById('ctaIconImgPreview');
            const rawSrc = ctaPreviewImg ? (ctaPreviewImg.getAttribute('src') || '').trim() : '';
            const isValidUrl = rawSrc && (rawSrc.startsWith('http') || rawSrc.startsWith('data:') || rawSrc.startsWith('/'));
            const activeIconUrl = isIconRemoved ? null : (uploadedCtaIconDataUrl || (isValidUrl ? rawSrc : null));
            const hasCustomIcon = Boolean(activeIconUrl);

            // Helper to synchronize both preview buttons with identical styling, layout, typography and icon
            function syncCtaElement(ctaEl, iconWrapEl, imgEl, textEl) {
                if (!ctaEl) return;
                ctaEl.className = 'socialnest-floating-cta socialnest-floating-cta--' + size;
                ctaEl.style.backgroundColor = bgColor;
                ctaEl.style.color = textColor;

                // Only show icon if icon is enabled AND a custom logo is actually uploaded
                const shouldShowIcon = Boolean(showIcon && hasCustomIcon && activeIconUrl);
                if (iconWrapEl) {
                    iconWrapEl.style.setProperty('display', shouldShowIcon ? 'inline-flex' : 'none', 'important');
                }

                if (textEl) {
                    textEl.textContent = btnText;
                }

                if (imgEl) {
                    if (shouldShowIcon && activeIconUrl) {
                        imgEl.src = activeIconUrl;
                        imgEl.style.setProperty('display', 'block', 'important');
                    } else {
                        imgEl.removeAttribute('src');
                        imgEl.style.setProperty('display', 'none', 'important');
                    }
                }
            }

            // 1. In-section preview (Floating CTA Live Preview card)
            syncCtaElement(
                document.getElementById('sectionCtaPreview'),
                document.getElementById('sectionCtaIconWrap'),
                document.getElementById('sectionCtaIconImg'),
                document.getElementById('sectionCtaText')
            );

            // 2. Storefront right-side preview (simulated fixed bottom-center)
            const liveCta = document.getElementById('liveCtaPreview');
            if (liveCta) {
                const isDeviceAllowed = (currentPreviewDevice === 'mobile') ? mobileEnabled : desktopEnabled;
                const shouldShow = isEnabled && isDeviceAllowed;

                liveCta.style.display = shouldShow ? 'inline-flex' : 'none';

                syncCtaElement(
                    liveCta,
                    document.getElementById('previewScreenCtaIconWrap'),
                    document.getElementById('previewScreenCtaIconImg'),
                    document.getElementById('previewScreenCtaText')
                );
            }
        }

        // Beforeunload safety for unsaved changes
        function handleBeforeUnload(e) {
            if (isSubmitting || !isDirty()) {
                return;
            }
            e.preventDefault();
            e.returnValue = '';
            return '';
        }

        // Display brief toast banner for save feedback
        function showToastNotification(message, type = 'success') {
            let toast = document.getElementById('settingsToastNotice');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'settingsToastNotice';
                toast.style.position = 'fixed';
                toast.style.top = '1.25rem';
                toast.style.right = '1.25rem';
                toast.style.zIndex = '99999';
                toast.style.padding = '0.75rem 1.25rem';
                toast.style.borderRadius = '8px';
                toast.style.fontSize = '0.875rem';
                toast.style.fontWeight = '600';
                toast.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.2)';
                toast.style.display = 'flex';
                toast.style.alignItems = 'center';
                toast.style.gap = '0.5rem';
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                document.body.appendChild(toast);
            }

            if (type === 'success') {
                toast.style.background = '#065f46';
                toast.style.color = '#ffffff';
                toast.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <span>' + message + '</span>';
            } else {
                toast.style.background = '#991b1b';
                toast.style.color = '#ffffff';
                toast.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> <span>' + message + '</span>';
            }

            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            clearTimeout(toast._timeout);
            toast._timeout = setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
            }, 4000);
        }

        // Form save submission handler
        async function handleFormSubmit(e) {
            e.preventDefault();
            if (isSaving) return;

            const form = document.getElementById('storefrontSettingsForm');
            if (!form) return;

            const submitBtns = form.querySelectorAll('button[type="submit"]');
            isSaving = true;

            submitBtns.forEach(btn => {
                btn.disabled = true;
                if (!btn.getAttribute('data-orig-text')) {
                    btn.setAttribute('data-orig-text', btn.textContent);
                }
                btn.textContent = 'Saving...';
            });

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data && data.success) {
                        // Successful server save: update initialSettings with saved data, clear dirty state & unload warning
                        initialSettings = normalize(data.settings || readCurrentSettings());
                        currentSettings = normalize(readCurrentSettings());
                        setDirtyState(false);
                        showToastNotification(data.message || 'Storefront settings saved successfully!', 'success');
                    } else {
                        throw new Error(data?.message || 'Save failed');
                    }
                } else {
                    const errData = await response.json().catch(() => null);
                    const msg = errData?.message || 'Failed to save settings. Please check the form and try again.';
                    showToastNotification(msg, 'error');
                    // Server save failed: keep dirty state true
                    setDirtyState(true);
                }
            } catch (err) {
                console.error('Error saving settings:', err);
                showToastNotification('Failed to save settings. Please try again.', 'error');
                // Server save failed: keep dirty state true
                setDirtyState(true);
            } finally {
                isSaving = false;
                submitBtns.forEach(btn => {
                    btn.disabled = false;
                    const orig = btn.getAttribute('data-orig-text');
                    if (orig) btn.textContent = orig;
                });
            }
        }

        // ==========================================
        // Google-Style Compact Color Picker Engine
        // ==========================================
        const cpState = {
            h: 220,     // Hue: 0 - 360
            s: 0.56,    // Saturation: 0 - 1
            v: 0.15,    // Brightness / Value: 0 - 1
            a: 1.0,     // Alpha: 0 - 1
            isOpen: false,
            activePointer: null
        };

        function parseColor(str) {
            if (!str || typeof str !== 'string') return { r: 17, g: 24, b: 39, a: 1 };
            str = str.trim();
            if (str.startsWith('#')) {
                let hex = str.slice(1);
                if (hex.length === 3 || hex.length === 4) {
                    hex = hex.split('').map(c => c + c).join('');
                }
                const num = parseInt(hex, 16);
                if (isNaN(num)) return { r: 17, g: 24, b: 39, a: 1 };
                if (hex.length === 8) {
                    return {
                        r: (num >> 24) & 255,
                        g: (num >> 16) & 255,
                        b: (num >> 8) & 255,
                        a: Math.round(((num & 255) / 255) * 100) / 100
                    };
                }
                return {
                    r: (num >> 16) & 255,
                    g: (num >> 8) & 255,
                    b: num & 255,
                    a: 1
                };
            }
            const match = str.match(/[\d.]+/g);
            if (match && match.length >= 3) {
                return {
                    r: Math.max(0, Math.min(255, parseInt(match[0], 10) || 0)),
                    g: Math.max(0, Math.min(255, parseInt(match[1], 10) || 0)),
                    b: Math.max(0, Math.min(255, parseInt(match[2], 10) || 0)),
                    a: match[3] !== undefined ? Math.max(0, Math.min(1, Math.round(parseFloat(match[3]) * 100) / 100)) : 1
                };
            }
            return { r: 17, g: 24, b: 39, a: 1 };
        }

        function normalizeColor(str) {
            const p = parseColor(str);
            return `rgba(${p.r}, ${p.g}, ${p.b}, ${p.a})`;
        }

        function hsvToRgb(h, s, v) {
            const c = v * s;
            const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
            const m = v - c;
            let r1 = 0, g1 = 0, b1 = 0;
            if (h >= 0 && h < 60) { r1 = c; g1 = x; b1 = 0; }
            else if (h >= 60 && h < 120) { r1 = x; g1 = c; b1 = 0; }
            else if (h >= 120 && h < 180) { r1 = 0; g1 = c; b1 = x; }
            else if (h >= 180 && h < 240) { r1 = 0; g1 = x; b1 = c; }
            else if (h >= 240 && h < 300) { r1 = x; g1 = 0; b1 = c; }
            else { r1 = c; g1 = 0; b1 = x; }
            return {
                r: Math.round((r1 + m) * 255),
                g: Math.round((g1 + m) * 255),
                b: Math.round((b1 + m) * 255)
            };
        }

        function rgbToHsv(r, g, b) {
            const rNorm = r / 255;
            const gNorm = g / 255;
            const bNorm = b / 255;
            const max = Math.max(rNorm, gNorm, bNorm);
            const min = Math.min(rNorm, gNorm, bNorm);
            const d = max - min;
            let h = 0;
            const v = max;
            const s = max === 0 ? 0 : d / max;

            if (d !== 0) {
                if (max === rNorm) {
                    h = ((gNorm - bNorm) / d) % 6;
                } else if (max === gNorm) {
                    h = (bNorm - rNorm) / d + 2;
                } else {
                    h = (rNorm - gNorm) / d + 4;
                }
                h = Math.round(h * 60);
                if (h < 0) h += 360;
            }
            return { h, s, v };
        }

        function getCurrentColorString() {
            const rgb = hsvToRgb(cpState.h, cpState.s, cpState.v);
            return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${cpState.a})`;
        }

        function getCurrentHexString() {
            const rgb = hsvToRgb(cpState.h, cpState.s, cpState.v);
            let hex = '#' + rgb.r.toString(16).padStart(2, '0') + rgb.g.toString(16).padStart(2, '0') + rgb.b.toString(16).padStart(2, '0');
            if (cpState.a < 1) {
                hex += Math.round(cpState.a * 255).toString(16).padStart(2, '0');
            }
            return hex.toUpperCase();
        }

        function applyButtonPreviewColor(colorStr, styleVal = null) {
            const preview = document.getElementById('productShareBtnPreview');
            if (!preview) return;

            const p = parseColor(colorStr);
            const style = styleVal || document.querySelector('input[name="product_share_button_style"]:checked')?.value || 'outline';

            const w = 255 * (1 - p.a);
            const lum = 0.299 * (p.r * p.a + w) + 0.587 * (p.g * p.a + w) + 0.114 * (p.b * p.a + w);
            const contrastTextColor = lum < 140 ? '#ffffff' : '#0f172a';

            if (style === 'solid') {
                preview.style.backgroundColor = colorStr;
                preview.style.borderColor = colorStr;
                preview.style.color = contrastTextColor;
            } else if (style === 'outline') {
                preview.style.backgroundColor = 'transparent';
                preview.style.borderColor = colorStr;
                preview.style.color = colorStr;
            } else {
                // minimal
                preview.style.backgroundColor = 'transparent';
                preview.style.borderColor = 'transparent';
                preview.style.color = colorStr;
            }
        }

        function updateColorPickerUI(updateDirty = true) {
            const rgb = hsvToRgb(cpState.h, cpState.s, cpState.v);
            const colorStr = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${cpState.a})`;
            const hexStr = getCurrentHexString();

            // 1. Trigger swatch fill and hex text
            const triggerSwatch = document.getElementById('productShareColorSwatchFill');
            if (triggerSwatch) triggerSwatch.style.backgroundColor = colorStr;

            const triggerHex = document.getElementById('productShareColorHexText');
            if (triggerHex) triggerHex.textContent = hexStr;

            // 2. Hidden input for form submission
            const hiddenInput = document.getElementById('product_share_button_background_color');
            if (hiddenInput) hiddenInput.value = colorStr;

            // 3. Popover 2D Area background and handle position
            const satValArea = document.getElementById('cpSatValArea');
            if (satValArea) {
                satValArea.style.backgroundColor = `hsl(${cpState.h}, 100%, 50%)`;
            }
            const satValHandle = document.getElementById('cpSatValHandle');
            if (satValHandle) {
                satValHandle.style.left = `${cpState.s * 100}%`;
                satValHandle.style.top = `${(1 - cpState.v) * 100}%`;
            }

            // 4. Sliders handles & preview
            const popoverPreview = document.getElementById('cpCurrentColorFill');
            if (popoverPreview) popoverPreview.style.backgroundColor = colorStr;

            const hueHandle = document.getElementById('cpHueHandle');
            if (hueHandle) hueHandle.style.left = `${(cpState.h / 360) * 100}%`;

            const alphaHandle = document.getElementById('cpAlphaHandle');
            if (alphaHandle) alphaHandle.style.left = `${cpState.a * 100}%`;

            const alphaGradient = document.getElementById('cpAlphaGradient');
            if (alphaGradient) {
                alphaGradient.style.background = `linear-gradient(to right, rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0), rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 1))`;
            }

            // 5. Inputs (if not active element)
            const hexInput = document.getElementById('cpHexInput');
            if (hexInput && document.activeElement !== hexInput) {
                hexInput.value = hexStr;
            }
            const alphaPercentInput = document.getElementById('cpAlphaPercentInput');
            if (alphaPercentInput && document.activeElement !== alphaPercentInput) {
                alphaPercentInput.value = `${Math.round(cpState.a * 100)}%`;
            }
            const rInput = document.getElementById('cpRInput');
            if (rInput && document.activeElement !== rInput) rInput.value = rgb.r;
            const gInput = document.getElementById('cpGInput');
            if (gInput && document.activeElement !== gInput) gInput.value = rgb.g;
            const bInput = document.getElementById('cpBInput');
            if (bInput && document.activeElement !== bInput) bInput.value = rgb.b;
            const aInput = document.getElementById('cpAInput');
            if (aInput && document.activeElement !== aInput) aInput.value = cpState.a;

            // 6. Live product share button preview
            applyButtonPreviewColor(colorStr);

            if (updateDirty) checkDirtyState();
        }

        function setColorFromExternal(colorStr, updateDirty = false) {
            const p = parseColor(colorStr);
            const hsv = rgbToHsv(p.r, p.g, p.b);
            cpState.h = hsv.h;
            cpState.s = hsv.s;
            cpState.v = hsv.v;
            cpState.a = p.a;
            updateColorPickerUI(updateDirty);
        }

        function positionColorPicker() {
            const trigger = document.getElementById('productShareColorTrigger');
            const popover = document.getElementById('googleColorPickerPopover');
            if (!trigger || !popover || popover.style.display === 'none') return;

            const rect = trigger.getBoundingClientRect();
            const popWidth = 320;
            const popHeight = popover.offsetHeight || 335;
            const vpWidth = window.innerWidth;
            const vpHeight = window.innerHeight;

            let left = rect.left;
            if (left + popWidth > vpWidth - 12) {
                left = Math.max(12, vpWidth - popWidth - 12);
            }
            if (left < 12) left = 12;

            let top = rect.bottom + 6;
            if (top + popHeight > vpHeight - 12) {
                const topAbove = rect.top - popHeight - 6;
                if (topAbove >= 12) {
                    top = topAbove;
                } else {
                    top = Math.max(12, vpHeight - popHeight - 12);
                }
            }

            popover.style.left = `${Math.round(left)}px`;
            popover.style.top = `${Math.round(top)}px`;
        }

        function openColorPicker() {
            const popover = document.getElementById('googleColorPickerPopover');
            const trigger = document.getElementById('productShareColorTrigger');
            if (!popover || !trigger) return;

            popover.style.display = 'block';
            trigger.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            cpState.isOpen = true;
            positionColorPicker();
        }

        function closeColorPicker() {
            const popover = document.getElementById('googleColorPickerPopover');
            const trigger = document.getElementById('productShareColorTrigger');
            if (!popover) return;

            popover.style.display = 'none';
            if (trigger) {
                trigger.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }
            cpState.isOpen = false;
        }

        function toggleColorPicker(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            if (cpState.isOpen) {
                closeColorPicker();
            } else {
                openColorPicker();
            }
        }

        function initColorPicker() {
            const hiddenInput = document.getElementById('product_share_button_background_color');
            const initColor = hiddenInput ? hiddenInput.value : 'rgba(17, 24, 39, 1)';
            setColorFromExternal(initColor, false);

            // Set up pointer drag for 2D area
            const satValArea = document.getElementById('cpSatValArea');
            if (satValArea && !satValArea._bound) {
                satValArea._bound = true;
                const updateFromPointer = (e) => {
                    const rect = satValArea.getBoundingClientRect();
                    const x = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
                    const y = Math.max(0, Math.min(rect.height, e.clientY - rect.top));
                    cpState.s = Math.round((x / rect.width) * 100) / 100;
                    cpState.v = Math.round((1 - (y / rect.height)) * 100) / 100;
                    updateColorPickerUI(true);
                };

                satValArea.addEventListener('pointerdown', (e) => {
                    e.preventDefault();
                    satValArea.setPointerCapture(e.pointerId);
                    satValArea._isDragging = true;
                    updateFromPointer(e);
                });

                satValArea.addEventListener('pointermove', (e) => {
                    if (satValArea._isDragging) {
                        e.preventDefault();
                        updateFromPointer(e);
                    }
                });

                const endDrag = (e) => {
                    if (satValArea._isDragging) {
                        satValArea._isDragging = false;
                        try { satValArea.releasePointerCapture(e.pointerId); } catch (_) {}
                    }
                };
                satValArea.addEventListener('pointerup', endDrag);
                satValArea.addEventListener('pointercancel', endDrag);

                // Keyboard controls
                satValArea.addEventListener('keydown', (e) => {
                    const step = e.shiftKey ? 0.05 : 0.01;
                    let handled = false;
                    if (e.key === 'ArrowRight') { cpState.s = Math.min(1, cpState.s + step); handled = true; }
                    else if (e.key === 'ArrowLeft') { cpState.s = Math.max(0, cpState.s - step); handled = true; }
                    else if (e.key === 'ArrowUp') { cpState.v = Math.min(1, cpState.v + step); handled = true; }
                    else if (e.key === 'ArrowDown') { cpState.v = Math.max(0, cpState.v - step); handled = true; }

                    if (handled) {
                        e.preventDefault();
                        updateColorPickerUI(true);
                    }
                });
            }

            // Set up pointer drag for Hue track
            const hueTrack = document.getElementById('cpHueTrack');
            if (hueTrack && !hueTrack._bound) {
                hueTrack._bound = true;
                const updateHue = (e) => {
                    const rect = hueTrack.getBoundingClientRect();
                    const x = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
                    cpState.h = Math.round((x / rect.width) * 360) % 360;
                    updateColorPickerUI(true);
                };

                hueTrack.addEventListener('pointerdown', (e) => {
                    e.preventDefault();
                    hueTrack.setPointerCapture(e.pointerId);
                    hueTrack._isDragging = true;
                    updateHue(e);
                });

                hueTrack.addEventListener('pointermove', (e) => {
                    if (hueTrack._isDragging) {
                        e.preventDefault();
                        updateHue(e);
                    }
                });

                const endHueDrag = (e) => {
                    if (hueTrack._isDragging) {
                        hueTrack._isDragging = false;
                        try { hueTrack.releasePointerCapture(e.pointerId); } catch (_) {}
                    }
                };
                hueTrack.addEventListener('pointerup', endHueDrag);
                hueTrack.addEventListener('pointercancel', endHueDrag);

                hueTrack.addEventListener('keydown', (e) => {
                    const step = e.shiftKey ? 10 : 2;
                    let handled = false;
                    if (e.key === 'ArrowRight') { cpState.h = (cpState.h + step) % 360; handled = true; }
                    else if (e.key === 'ArrowLeft') { cpState.h = (cpState.h - step + 360) % 360; handled = true; }
                    if (handled) {
                        e.preventDefault();
                        updateColorPickerUI(true);
                    }
                });
            }

            // Set up pointer drag for Alpha track
            const alphaTrack = document.getElementById('cpAlphaTrack');
            if (alphaTrack && !alphaTrack._bound) {
                alphaTrack._bound = true;
                const updateAlpha = (e) => {
                    const rect = alphaTrack.getBoundingClientRect();
                    const x = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
                    cpState.a = Math.round((x / rect.width) * 100) / 100;
                    updateColorPickerUI(true);
                };

                alphaTrack.addEventListener('pointerdown', (e) => {
                    e.preventDefault();
                    alphaTrack.setPointerCapture(e.pointerId);
                    alphaTrack._isDragging = true;
                    updateAlpha(e);
                });

                alphaTrack.addEventListener('pointermove', (e) => {
                    if (alphaTrack._isDragging) {
                        e.preventDefault();
                        updateAlpha(e);
                    }
                });

                const endAlphaDrag = (e) => {
                    if (alphaTrack._isDragging) {
                        alphaTrack._isDragging = false;
                        try { alphaTrack.releasePointerCapture(e.pointerId); } catch (_) {}
                    }
                };
                alphaTrack.addEventListener('pointerup', endAlphaDrag);
                alphaTrack.addEventListener('pointercancel', endAlphaDrag);

                alphaTrack.addEventListener('keydown', (e) => {
                    const step = e.shiftKey ? 0.05 : 0.01;
                    let handled = false;
                    if (e.key === 'ArrowRight') { cpState.a = Math.min(1, Math.round((cpState.a + step) * 100) / 100); handled = true; }
                    else if (e.key === 'ArrowLeft') { cpState.a = Math.max(0, Math.round((cpState.a - step) * 100) / 100); handled = true; }
                    if (handled) {
                        e.preventDefault();
                        updateColorPickerUI(true);
                    }
                });
            }

            // Input event listeners
            const hexInput = document.getElementById('cpHexInput');
            if (hexInput && !hexInput._bound) {
                hexInput._bound = true;
                hexInput.addEventListener('input', () => {
                    let val = hexInput.value.trim();
                    if (!val.startsWith('#')) val = '#' + val;
                    if (/^#[0-9a-fA-F]{3,8}$/.test(val)) {
                        const p = parseColor(val);
                        const hsv = rgbToHsv(p.r, p.g, p.b);
                        cpState.h = hsv.h;
                        cpState.s = hsv.s;
                        cpState.v = hsv.v;
                        if (val.length === 5 || val.length === 9) {
                            cpState.a = p.a;
                        }
                        updateColorPickerUI(true);
                    }
                });
            }

            const alphaPercentInput = document.getElementById('cpAlphaPercentInput');
            if (alphaPercentInput && !alphaPercentInput._bound) {
                alphaPercentInput._bound = true;
                alphaPercentInput.addEventListener('input', () => {
                    const raw = alphaPercentInput.value.replace('%', '').trim();
                    const num = parseInt(raw, 10);
                    if (!isNaN(num)) {
                        cpState.a = Math.max(0, Math.min(100, num)) / 100;
                        updateColorPickerUI(true);
                    }
                });
            }

            const syncRgbInputs = () => {
                const r = Math.max(0, Math.min(255, parseInt(document.getElementById('cpRInput')?.value, 10) || 0));
                const g = Math.max(0, Math.min(255, parseInt(document.getElementById('cpGInput')?.value, 10) || 0));
                const b = Math.max(0, Math.min(255, parseInt(document.getElementById('cpBInput')?.value, 10) || 0));
                const hsv = rgbToHsv(r, g, b);
                cpState.h = hsv.h;
                cpState.s = hsv.s;
                cpState.v = hsv.v;
                updateColorPickerUI(true);
            };

            ['cpRInput', 'cpGInput', 'cpBInput'].forEach(id => {
                const el = document.getElementById(id);
                if (el && !el._bound) {
                    el._bound = true;
                    el.addEventListener('input', syncRgbInputs);
                }
            });

            const aInput = document.getElementById('cpAInput');
            if (aInput && !aInput._bound) {
                aInput._bound = true;
                aInput.addEventListener('input', () => {
                    const num = parseFloat(aInput.value);
                    if (!isNaN(num)) {
                        cpState.a = Math.max(0, Math.min(1, Math.round(num * 100) / 100));
                        updateColorPickerUI(true);
                    }
                });
            }

            // Close on document click outside or escape key
            if (!document._boundColorPickerDismiss) {
                document._boundColorPickerDismiss = true;
                document.addEventListener('pointerdown', (e) => {
                    if (!cpState.isOpen) return;
                    const popover = document.getElementById('googleColorPickerPopover');
                    const trigger = document.getElementById('productShareColorTrigger');
                    if (popover && !popover.contains(e.target) && trigger && !trigger.contains(e.target)) {
                        closeColorPicker();
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && cpState.isOpen) {
                        closeColorPicker();
                    }
                });

                window.addEventListener('resize', positionColorPicker);
                window.addEventListener('scroll', positionColorPicker, true);
            }
        }

        // Expose global helper methods immediately
        window.normalize = normalize;
        window.normalizeSettings = normalize;
        window.readCurrentSettings = readCurrentSettings;
        window.isDirty = isDirty;
        window.checkDirtyState = checkDirtyState;
        window.setDirtyState = setDirtyState;
        window.resetFormToSaved = resetFormToSaved;
        window.applySettingsToForm = applySettingsToForm;
        window.handleFormSubmit = handleFormSubmit;
        window.handleBeforeUnload = handleBeforeUnload;
        window.toggleColorPicker = toggleColorPicker;
        window.openColorPicker = openColorPicker;
        window.closeColorPicker = closeColorPicker;
        window.setColorFromExternal = setColorFromExternal;
        window.onCtaSettingChanged = onCtaSettingChanged;
        window.onCtaSizeChanged = onCtaSizeChanged;
        window.onCtaColorSync = onCtaColorSync;
        window.onCtaColorTextSync = onCtaColorTextSync;
        window.onCtaIconFileSelected = onCtaIconFileSelected;
        window.removeCtaIcon = removeCtaIcon;
        window.updateCtaLivePreview = updateCtaLivePreview;

        function initStorefrontSettingsPage() {
            initColorPicker();

            // Seed initialSettings from current rendered form values (loaded from server)
            initialSettings = normalize(readCurrentSettings());
            currentSettings = normalize(readCurrentSettings());

            window.initialSettings = initialSettings;
            window.currentSettings = currentSettings;

            // Ensure clean initial state: never mark dirty on load and do NOT attach beforeunload
            setDirtyState(false);

            updateWhatsAppCharCount();
            updateLivePreview();
            updateCtaLivePreview();

            const form = document.getElementById('storefrontSettingsForm');
            if (form && !form._boundAjaxSubmit) {
                form.addEventListener('submit', handleFormSubmit);
                form._boundAjaxSubmit = true;
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initStorefrontSettingsPage);
        } else {
            initStorefrontSettingsPage();
        }
    </script>
    <script>
        // Tab navigation is presentation-only; all settings remain in one safe update form.
        (function () {
            function initSocialLinkTabs() {
                const root = document.querySelector('.sn-settings-shell');
                if (!root || root.dataset.tabsInitialized === '1') return;
                const tabs = root.querySelectorAll('[data-settings-tab]');
                const panels = root.querySelectorAll('[data-settings-panel]');
                if (!tabs.length || !panels.length) return;

                function activateSettingsTab(name) {
                    tabs.forEach(tab => {
                        const active = tab.dataset.settingsTab === name;
                        tab.classList.toggle('is-active', active);
                        tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    panels.forEach(panel => {
                        const active = panel.dataset.settingsPanel === name;
                        panel.classList.toggle('is-active', active);
                        panel.hidden = !active;
                    });
                    if (name !== 'configured-links' && typeof updateLivePreview === 'function') {
                        updateLivePreview();
                    }
                }

                tabs.forEach(tab => tab.addEventListener('click', () => activateSettingsTab(tab.dataset.settingsTab)));
                root.dataset.tabsInitialized = '1';
                activateSettingsTab('configured-links');
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSocialLinkTabs, { once: true });
            } else {
                initSocialLinkTabs();
            }
        })();
    </script>

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
                        <p style="font-size: 0.75rem; color:  #000000; margin-bottom: 1.25rem;">This represents exactly how the link will appear.</p>
                        
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
                        <div style="font-size: 0.65rem; font-weight: 700; color:  #000000; margin-bottom: 0.3rem;">Discovered (Click to Select)</div>
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
                <div class="sn-plat-icon" style="background: #f1f2f4; color:  #000000; width: 28px; height: 28px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                </div>
                <span style="font-weight: 500; font-size: 0.8125rem; color:  #000000;">Click to select a platform...</span>
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