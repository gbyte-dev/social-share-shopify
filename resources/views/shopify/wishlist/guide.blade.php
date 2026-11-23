
@extends('layouts.shopify')

@section('header_title', 'Wishlist Guide')
@section('header_subtitle', 'Learn how Wishlist works and how to enable it in your store.')
@section('header_actions')
<div class="sn-dashboard-header-actions">
    <div class="sn-dashboard-header-row">
        <span id="wishlist-status-badge" class="sn-embed-status sn-warning" aria-live="polite">
    ⚠️ Checking Wishlist status...
    </span>


        <button
            type="button"
            id="wishlist-setup-btn"
            data-url="{{ route('shopify.wishlist.setup') }}"
            data-status-url="{{ route('shopify.wishlist.status') }}"
            class="btn btn-primary btn-sm sn-wishlist-setup-btn">

            <svg width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8z"/>
            </svg>

            <span>Setup Wishlist</span>

            <svg class="sn-wishlist-arrow"
                 width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="m12 5 7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>

<style>
    .sn-wishlist-setup-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 38px;
        padding: 0 16px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        letter-spacing: 0.1px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.10);
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .sn-warning {
    background-color: #fff3cd; /* Soft yellow background */
    color: #856404;            /* Dark amber text for readability */
    border: 1px solid #ffeeba;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    }


    .sn-wishlist-setup-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .sn-wishlist-setup-btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    .sn-wishlist-arrow {
        transition: transform 0.2s ease;
    }

    .sn-wishlist-setup-btn:hover:not(:disabled) .sn-wishlist-arrow {
        transform: translateX(3px);
    }

    .sn-dashboard-header-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
</style>
@endsection

@section('content')

<div class="wishlist-guide">

    {{-- Overview --}}
    <section class="wg-overview">
        <div class="wg-overview-content">
            <span class="wg-eyebrow">SOCIALVIRE · WISHLIST</span>
            <h2>Help customers save products for later</h2>
            <p>
                Let shoppers save products they love and revisit them whenever
                they are ready to purchase.
            </p>
        </div>
        <div class="wg-overview-icon">♡</div>
    </section>

    {{-- Features --}}
    <section class="wg-section">
        <div class="wg-section-heading">
            <h3>What does Wishlist do?</h3>
            <p>Key capabilities of the Wishlist feature.</p>
        </div>

        <div class="wg-grid">
            <article class="wg-card">
                <span class="wg-icon">♡</span>
                <div>
                    <h4>Save products</h4>
                    <p>Customers save products they like for later.</p>
                </div>
            </article>

            <article class="wg-card">
                <span class="wg-icon">▤</span>
                <div>
                    <h4>View saved items</h4>
                    <p>Customers revisit their saved products anytime.</p>
                </div>
            </article>

            <article class="wg-card">
                <span class="wg-icon">↗</span>
                <div>
                    <h4>Continue shopping</h4>
                    <p>Customers return to saved products and purchase them.</p>
                </div>
            </article>
        </div>
    </section>

    {{-- Workflow --}}
    <section class="wg-section">
        <div class="wg-section-heading">
            <h3>How it works</h3>
            <p>From product discovery to purchase.</p>
        </div>

        <div class="wg-workflow">
            <div class="wg-workflow-item">
                <span>01</span>
                <div>
                    <h4>Browse products</h4>
                    <p>Customers explore products in your storefront.</p>
                </div>
            </div>

            <div class="wg-workflow-item">
                <span>02</span>
                <div>
                    <h4>Save to Wishlist</h4>
                    <p>Customers click the Wishlist button to save a product.</p>
                </div>
            </div>

            <div class="wg-workflow-item">
                <span>03</span>
                <div>
                    <h4>View saved products</h4>
                    <p>Customers open their Wishlist to see saved items.</p>
                </div>
            </div>

            <div class="wg-workflow-item">
                <span>04</span>
                <div>
                    <h4>Return to shopping</h4>
                    <p>Customers revisit products and continue shopping.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Setup --}}
    <section class="wg-section" id="wg-setup">
        <div class="wg-section-heading">
            <h3>Setup instructions</h3>
            <p>Enable the Wishlist app extension in your Shopify theme.</p>
        </div>

        <div class="wg-panel">
            <div class="wg-setup-row">
                <span class="wg-number">1</span>
                <div>
                    <h4>Open Shopify Theme Editor</h4>
                    <p>Go to Shopify Admin → Online Store → Themes → Customize.</p>
                </div>
            </div>

            <div class="wg-setup-row">
                <span class="wg-number">2</span>
                <div>
                    <h4>Locate Socialvire Wishlist</h4>
                    <p>Find the Wishlist extension in the App embeds panel or relevant theme section.</p>
                </div>
            </div>

            <div class="wg-setup-row">
                <span class="wg-number">3</span>
                <div>
                    <h4>Enable the extension</h4>
                    <p>Turn on the Wishlist extension and configure available settings.</p>
                </div>
            </div>

            <div class="wg-setup-row">
                <span class="wg-number">4</span>
                <div>
                    <h4>Save and verify</h4>
                    <p>Save your theme and preview your storefront to check the Wishlist button.</p>
                </div>
            </div>
        </div>

        <div class="wg-note">
            <strong>Note:</strong>
            Extension placement depends on your theme. Make sure you edit the
            theme published to your storefront.
        </div>
    </section>

    {{-- Important concepts --}}
    <section class="wg-section">
        <div class="wg-section-heading">
            <h3>Important things to know</h3>
            <p>Understand how Wishlist works with other shopping features.</p>
        </div>

        <div class="wg-info-list">
            <div>
                <strong>Wishlist vs Cart</strong>
                <p>Saving a product does not add it to the cart or reserve inventory.</p>
            </div>

            <div>
                <strong>Customer access</strong>
                <p>Saved-item persistence depends on the storefront's customer identity implementation.</p>
            </div>

            <div>
                <strong>Theme compatibility</strong>
                <p>The app extension must be enabled in the active theme to appear on your storefront.</p>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="wg-section">
        <div class="wg-section-heading">
            <h3>Frequently asked questions</h3>
            <p>Quick answers to common questions.</p>
        </div>

        <div class="wg-faq">
            <details>
                <summary>Does Wishlist add products to the cart?</summary>
                <p>No. Wishlist saves products for later. Customers must add products to their cart separately.</p>
            </details>

            <details>
                <summary>Where can customers view saved products?</summary>
                <p>Customers can access saved products through the Wishlist page provided by your storefront implementation.</p>
            </details>

            <details>
                <summary>Why is the Wishlist button not visible?</summary>
                <p>Check whether the app extension is enabled in your active Shopify theme and changes are saved.</p>
            </details>

            <details>
                <summary>Does Wishlist reserve product stock?</summary>
                <p>No. Saving a product does not reserve inventory. Availability can change before purchase.</p>
            </details>
        </div>
    </section>

</div>

<style>
.wishlist-guide {
    width: 100%;
    max-width: none;
    margin: 0;
    color: #202223;
    font-size: 13px;
}

/* Overview */
.wg-overview {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 17px 20px;
    background: #f6f7f8;
    border: 1px solid #e1e3e5;
    border-radius: 9px;
    margin-bottom: 17px;
}

.wg-overview-content {
    max-width: 750px;
}

.wg-eyebrow {
    color: #616a75;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .7px;
}

.wg-overview h2 {
    font-size: 20px;
    line-height: 1.3;
    margin: 5px 0;
    color: #202223;
}

.wg-overview p,
.wg-section-heading p,
.wg-card p,
.wg-workflow-item p,
.wg-setup-row p,
.wg-info-list p,
.wg-faq details p {
    color: #616a75;
    font-size: 12px;
    line-height: 1.5;
    margin: 3px 0 0;
}

.wg-overview-icon {
    width: 52px;
    height: 52px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    background: #e8edf8;
    border-radius: 9px;
    color: #304e7b;
    font-size: 34px;
}

/* Sections */
.wg-section {
    margin-bottom: 19px;
}

.wg-section-heading {
    margin-bottom: 9px;
}

.wg-section-heading h3 {
    font-size: 15px;
    font-weight: 650;
    margin: 0;
    color: #202223;
}

/* Feature cards */
.wg-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.wg-card {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 12px 14px;
    border: 1px solid #e1e3e5;
    border-radius: 8px;
    background: #fff;
}

.wg-icon {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 7px;
    background: #f1f2f5;
    color: #304e7b;
    font-size: 19px;
}

.wg-card h4,
.wg-workflow-item h4,
.wg-setup-row h4 {
    font-size: 13px;
    font-weight: 600;
    margin: 0;
    color: #202223;
}

/* Workflow */
.wg-workflow {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
}

.wg-workflow-item {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 11px 13px;
    border: 1px solid #e1e3e5;
    border-radius: 8px;
    background: #fff;
}

.wg-workflow-item > span {
    font-size: 13px;
    font-weight: 700;
    color: #526b9b;
}

.wg-setup-row h4 {
    margin: 0;
}

/* Setup */
.wg-panel {
    padding: 2px 14px;
    border: 1px solid #e1e3e5;
    border-radius: 8px;
    background: #fff;
}

.wg-setup-row {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 11px 0;
    border-bottom: 1px solid #f0f1f2;
}

.wg-setup-row:last-child {
    border-bottom: 0;
}

.wg-number {
    display: grid;
    place-items: center;
    width: 25px;
    height: 25px;
    flex-shrink: 0;
    border-radius: 50%;
    background: #f1f2f5;
    color: #304e7b;
    font-size: 11px;
    font-weight: 700;
}

.wg-note {
    margin-top: 9px;
    padding: 9px 12px;
    border-radius: 7px;
    background: #f6f7f8;
    color: #515960;
    font-size: 12px;
    line-height: 1.5;
}

/* Information */
.wg-info-list {
    border: 1px solid #e1e3e5;
    border-radius: 8px;
    padding: 0 13px;
    background: #fff;
}

.wg-info-list > div {
    padding: 10px 0;
    border-bottom: 1px solid #f0f1f2;
}

.wg-info-list > div:last-child {
    border-bottom: 0;
}

.wg-info-list strong {
    font-size: 12px;
    color: #202223;
}

/* FAQ */
.wg-faq {
    display: grid;
    gap: 7px;
}

.wg-faq details {
    padding: 11px 13px;
    border: 1px solid #e1e3e5;
    border-radius: 7px;
    background: #fff;
}

.wg-faq summary {
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: #202223;
}

.wg-faq details p {
    margin-top: 7px;
}

@media (max-width: 700px) {
    .wg-overview {
        padding: 14px;
    }

    .wg-overview-icon {
        display: none;
    }

    .wg-grid,
    .wg-workflow {
        grid-template-columns: 1fr;
    }

    .wg-card {
        padding: 11px;
    }
}
</style>


<script>
    (function () {
        function initWishlistHeader() {
            const wishlistBtn = document.getElementById('wishlist-setup-btn');
            const wishlistStatusBadge = document.getElementById('wishlist-status-badge');

            if (!wishlistBtn || !wishlistStatusBadge || wishlistBtn._boundWishlist) {
                return;
            }

            wishlistBtn._boundWishlist = true;

            let inFlightWishlistCheck = false;
            let currentWishlistStatus = 'unknown';

            function setWishlistUI(status, customMessage) {
                if (!wishlistStatusBadge || !wishlistBtn) return;

                if (['active', 'setup_pending', 'preparing', 'not_configured'].includes(status)) {
                    currentWishlistStatus = status;
                }

                wishlistStatusBadge.classList.remove('is-active', 'is-inactive');

                if (status === 'checking') {
                    wishlistStatusBadge.textContent = 'Checking Wishlist Status...';
                    wishlistBtn.textContent = 'Setup Wishlist';
                    wishlistBtn.disabled = true;

                } else if (status === 'active') {
                    wishlistStatusBadge.textContent = 'Wishlist Active';
                    wishlistStatusBadge.classList.add('is-active');
                    wishlistBtn.textContent = 'Customize Wishlist';
                    wishlistBtn.disabled = false;

                } else if (status === 'setup_pending') {
                    wishlistStatusBadge.textContent = 'Waiting for Theme Save';
                    wishlistStatusBadge.classList.add('is-inactive');
                    wishlistBtn.textContent = 'Complete Wishlist Setup';
                    wishlistBtn.disabled = false;

                } else if (status === 'preparing') {
                    wishlistStatusBadge.textContent = 'Waiting for Theme Save';
                    wishlistStatusBadge.classList.add('is-inactive');
                    wishlistBtn.textContent = 'Preparing Wishlist Setup...';
                    wishlistBtn.disabled = true;

                } else if (status === 'not_configured') {
                    wishlistStatusBadge.textContent = 'Wishlist Not Configured';
                    wishlistStatusBadge.classList.add('is-inactive');
                    wishlistBtn.textContent = 'Setup Wishlist';
                    wishlistBtn.disabled = false;

                } else {
                    wishlistStatusBadge.textContent = customMessage || 'Wishlist status unavailable';
                    wishlistBtn.disabled = false;
                }
            }

            async function checkWishlistStatus() {
                if (!wishlistBtn.dataset.statusUrl || inFlightWishlistCheck) {
                    return;
                }

                inFlightWishlistCheck = true;

                try {
                    const response = await shopifyAuthenticatedFetch(
                        wishlistBtn.dataset.statusUrl,
                        {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                    const data = await response.json();

                    if (
                        response.status === 403 &&
                        (data.code === 'UPGRADE_REQUIRED' ||
                        data.error === 'UPGRADE_REQUIRED')
                    ) {
                        wishlistBtn.dataset.upgradeUrl = data.upgrade_url || '';

                        wishlistStatusBadge.textContent = 'Wishlist Available on Pro';
                        wishlistStatusBadge.classList.remove('is-active');
                        wishlistStatusBadge.classList.add('is-inactive');

                        wishlistBtn.disabled = false;
                        wishlistBtn.textContent = 'Upgrade to Pro';

                        return;
                    }

                    if (!response.ok || !data.success) {
                        throw new Error(
                            data.message || `Status check HTTP error ${response.status}`
                        );
                    }

                    delete wishlistBtn.dataset.upgradeUrl;

                    setWishlistUI(data.setup_status || 'not_configured');

                    if (data.deep_link_url) {
                        wishlistBtn.dataset.editorUrl = data.deep_link_url;
                    }

                } catch (error) {
                    console.warn('[SocialNest] Wishlist status check failed:', error);

                    wishlistStatusBadge.textContent = 'Unable to Verify Wishlist Status';
                    wishlistStatusBadge.classList.remove('is-active');
                    wishlistStatusBadge.classList.add('is-inactive');

                    wishlistBtn.disabled = false;
                    wishlistBtn.textContent = 'Check Wishlist Setup';

                } finally {
                    inFlightWishlistCheck = false;
                }
            }

            wishlistBtn.addEventListener('click', async function () {

                if (wishlistBtn.dataset.upgradeUrl) {
                    window.top.location.href = wishlistBtn.dataset.upgradeUrl;
                    return;
                }

                if (wishlistBtn.disabled) return;

                setWishlistUI('preparing');

                try {
                    const response = await shopifyAuthenticatedFetch(
                        wishlistBtn.dataset.url,
                        {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                preview_path: '/pages/wishlist'
                            })
                        }
                    );

                    const responseText = await response.text();

                    let data;

                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('Wishlist invalid JSON response:', {
                            status: response.status,
                            response: responseText
                        });

                        throw new Error('Server returned an invalid response.');
                    }

                    if (!response.ok || !data.success) {
                        console.error('Wishlist setup API failed:', {
                            status: response.status,
                            response: data
                        });

                        throw new Error(
                            data.message || `Request failed: ${response.status}`
                        );
                    }

                    if (!data.deep_link_url) {
                        throw new Error('Theme Editor URL missing.');
                    }

                    console.info('Wishlist setup successful:', data);

                    if (!data.installed) {
                        setWishlistUI('setup_pending');
                    }

                    let targetUrl = data.deep_link_url;

                    try {
                        const parsedUrl = new URL(targetUrl, window.location.origin);

                        if (parsedUrl.searchParams.get('template') === 'page') {
                            parsedUrl.searchParams.delete('template');
                        }

                        const pageHandle = data.page && data.page.handle
                            ? data.page.handle
                            : 'wishlist';

                        parsedUrl.searchParams.set(
                            'previewPath',
                            '/pages/' + pageHandle
                        );

                        targetUrl = parsedUrl.toString();

                    } catch (urlError) {
                        if (targetUrl && !targetUrl.includes('previewPath=')) {
                            const glue = targetUrl.includes('?') ? '&' : '?';

                            targetUrl += glue + 'previewPath=%2Fpages%2Fwishlist';
                        }
                    }

                    window.top.location.href = targetUrl;

                } catch (error) {
                    console.error('Wishlist setup failed:', error);

                    AppNotice.error(
                        error.message || 'Wishlist setup failed. Please try again.',
                        'Wishlist Setup Failed'
                    );

                    setWishlistUI(
                        currentWishlistStatus !== 'unknown'
                            ? currentWishlistStatus
                            : 'not_configured'
                    );
                }
            });

            setWishlistUI('checking');
            checkWishlistStatus();
        }

        // Initial page load
        initWishlistHeader();

        // Support Shopify SPA header replacement
        if (!window._wishlistHeaderObserver) {
            window._wishlistHeaderObserver = new MutationObserver(function () {
                initWishlistHeader();
            });

            window._wishlistHeaderObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

    })();
</script>


@endsection
