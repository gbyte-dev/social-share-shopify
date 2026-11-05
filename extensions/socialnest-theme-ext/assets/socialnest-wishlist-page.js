/**
 * SocialNest Storefront Wishlist - Wishlist Page Rendering Module
 * Renders dedicated wishlist grid, loading spinner, error state, and empty state
 */

import { getState, fetchWishlist, removeProduct } from './socialnest-wishlist-api.js';

var doc = typeof document !== 'undefined' ? document : null;

/**
 * Hide theme's default page title to avoid duplicate "Wishlist" titles.
 */
export function hideThemeTitles(wrapper) {
  if (!doc || !wrapper) return;
  try {
    doc.querySelectorAll('.main-page-title, h1.page-title, .page-title, .section-header__title, .section-header h1, .page-header h1').forEach(function(el) {
      if (!el.classList.contains('sn-wishlist-title') && !wrapper.contains(el)) {
        el.style.setProperty('display', 'none', 'important');
        el.setAttribute('aria-hidden', 'true');
      }
    });
    var sec = wrapper.closest('.shopify-section') || wrapper.parentElement;
    if (sec) {
      sec.querySelectorAll('h1').forEach(function(h) {
        if (!h.classList.contains('sn-wishlist-title') && !wrapper.contains(h)) {
          h.style.setProperty('display', 'none', 'important');
          h.setAttribute('aria-hidden', 'true');
        }
      });
    }
  } catch (e) {}
}

/**
 * Render dedicated Wishlist page (/pages/wishlist).
 */
export function renderWishlistPage(onUpdate) {
  if (!doc) return;
  var root = doc.querySelector('#socialnest-wishlist-grid-root');
  if (!root) return;

  var wrapper = root.closest('.socialnest-wishlist-page-wrapper');
  hideThemeTitles(wrapper);

  var state = getState();
  var items = state.items || [];
  var isInitialLoading = state.isInitialLoading;
  var hasLoadError = state.hasLoadError;

  var emptyHeading = (wrapper ? wrapper.dataset.emptyHeading : null) || 'Your wishlist is empty';
  var emptyText = (wrapper ? wrapper.dataset.emptyText : null) || 'Explore our catalog and click the heart icon on any product to save it here.';
  var continueUrl = (wrapper ? wrapper.dataset.continueUrl : null) || '/collections/all';
  var continueLabel = (wrapper ? wrapper.dataset.continueLabel : null) || 'Continue Shopping';

  var totalCountEl = wrapper ? wrapper.querySelector('.sn-wishlist-total-count') : null;
  if (totalCountEl) {
    totalCountEl.textContent = items.length === 1 ? '1 item' : (items.length + ' items');
  }

  // 1. Loading State
  if (isInitialLoading) {
    root.innerHTML = '<div class="sn-wishlist-loading" style="grid-column: 1 / -1; text-align: center; padding: 3.5rem 1rem; color: #6b7280;">' +
      '<div class="sn-loading-spinner" aria-label="Loading wishlist"></div>' +
      '<p style="margin-top: 1rem; font-size: 0.95rem; font-weight: 500; color: #4b5563;">Loading your saved items...</p>' +
      '</div>';
    return;
  }

  // 2. API Error State
  if (hasLoadError && items.length === 0) {
    root.innerHTML = '<div class="sn-wishlist-empty" style="grid-column: 1 / -1;">' +
      '<div class="sn-wishlist-empty__icon"><svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>' +
      '<h2 class="sn-wishlist-empty__heading">Unable to load wishlist</h2>' +
      '<p class="sn-wishlist-empty__text">We could not retrieve your saved items. Please check your connection and try again.</p>' +
      '<button type="button" class="sn-wishlist-empty__btn btn-retry-wishlist" style="cursor: pointer; border: none;">Retry</button>' +
      '</div>';
    var retryBtn = root.querySelector('.btn-retry-wishlist');
    if (retryBtn) {
      retryBtn.onclick = function() {
        fetchWishlist(onUpdate);
      };
    }
    return;
  }

  // 3. Empty State
  if (items.length === 0) {
    root.innerHTML = '<div class="sn-wishlist-empty">' +
      '<div class="sn-wishlist-empty__icon">' +
      '<svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5">' +
      '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>' +
      '</svg>' +
      '</div>' +
      '<h2 class="sn-wishlist-empty__heading">' + emptyHeading + '</h2>' +
      '<p class="sn-wishlist-empty__text">' + emptyText + '</p>' +
      '<a href="' + continueUrl + '" class="sn-wishlist-empty__btn">' + continueLabel + '</a>' +
      '</div>';
    return;
  }

  // 4. Populated Product Grid Cards
  var html = items.map(function(item) {
    var pid = String(item.id || item.product_id || '');
    var rawTitle = (item.title || item.product_title || '').trim();
    var handle = item.handle || item.product_handle || '';
    var title = rawTitle || (handle ? handle.replace(/-/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); }) : 'Product');
    var img = item.img || item.image || item.product_image || '';
    var price = item.price || item.product_price || '';
    var url = item.url || item.product_url || (handle ? '/products/' + handle : '#');
    var safeTitle = title.replace(/"/g, '&quot;');

    return '<div class="sn-wishlist-card" data-product-id="' + pid + '">' +
      '<div class="sn-wishlist-card__image-wrap">' +
      '<a href="' + url + '" class="sn-wishlist-card__image-link">' +
      (img
        ? '<img src="' + img + '" alt="' + safeTitle + '" loading="lazy" class="sn-wishlist-card__img">'
        : '<div class="sn-wishlist-card__no-img" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;background:#f3f4f6;"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></div>') +
      '</a>' +
      '<button type="button" class="sn-wishlist-remove-btn sn-wishlist-heart-btn sn-wishlist-active"' +
      ' data-product-id="' + pid + '"' +
      ' aria-label="Remove ' + safeTitle + ' from wishlist"' +
      ' title="Remove from wishlist">' +
      '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>' +
      '</button>' +
      '</div>' +
      '<div class="sn-wishlist-card__info">' +
      '<h3 class="sn-wishlist-card__title">' +
      '<a href="' + url + '">' + title + '</a>' +
      '</h3>' +
      (price ? '<div class="sn-wishlist-card__price">' + price + '</div>' : '') +
      '<div class="sn-wishlist-card__actions">' +
      '<a href="' + url + '" class="sn-wishlist-card__view-btn">View Product</a>' +
      '</div>' +
      '</div>' +
      '</div>';
  }).join('');

  root.innerHTML = html;

  root.querySelectorAll('.sn-wishlist-remove-btn').forEach(function(btn) {
    btn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      var pid = btn.dataset.productId;
      var card = btn.closest('.sn-wishlist-card');
      if (card) {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
      }
      setTimeout(function() {
        removeProduct(pid, onUpdate);
      }, 150);
    };
  });
}
