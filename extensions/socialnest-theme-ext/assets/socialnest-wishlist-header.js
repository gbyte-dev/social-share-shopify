/**
 * SocialNest Storefront Wishlist - Header Integration Module
 * Injects and animates the header navigation wishlist icon and counter badge
 */

var doc = typeof document !== 'undefined' ? document : null;

/**
 * Inject header wishlist button & count badge into the theme navigation.
 */
export function initHeader(itemsCount) {
  if (!doc) return;
  var count = typeof itemsCount === 'number' ? itemsCount : 0;
  var existingBtn = doc.querySelector('.sn-header-wishlist-btn');
  var header = doc.querySelector('#header-component, .header, header');
  var columns = doc.querySelector('.header__columns');

  if (existingBtn) {
    if (header) header.classList.add('sn-has-header-wishlist');
    if (columns) columns.classList.add('sn-has-header-wishlist');
    syncHeader(count);
    return;
  }

  var cartTarget = doc.querySelector('header-actions [data-testid="cart-drawer-trigger"], header-actions cart-icon, header-actions .action__cart, #cart-icon-bubble, a.header__icon--cart, a[href*="/cart"].header__icon, .header__icons a[href*="/cart"], header a[href*="/cart"]');
  var iconsContainer = doc.querySelector('header-actions, .header__icons, .header__actions, .site-header__icons, header .header__column--right');

  if (cartTarget || iconsContainer) {
    var a = doc.createElement('a');
    a.href = '/pages/wishlist';
    a.className = 'header__icon header-actions__action sn-header-wishlist-btn';
    a.setAttribute('aria-label', 'Wishlist');
    a.setAttribute('title', 'Wishlist');
    a.innerHTML = '<span class="svg-wrapper"><svg class="sn-header-wishlist-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></span><span class="sn-wishlist-count-badge">' + count + '</span>';

    if (cartTarget && cartTarget.parentNode) {
      cartTarget.parentNode.insertBefore(a, cartTarget);
    } else if (iconsContainer) {
      iconsContainer.appendChild(a);
    }

    if (header) header.classList.add('sn-has-header-wishlist');
    if (columns) columns.classList.add('sn-has-header-wishlist');
  }
}

/**
 * Synchronize header badge number and trigger pop animation.
 */
export function syncHeader(itemsCount) {
  if (!doc) return;
  var count = typeof itemsCount === 'number' ? itemsCount : 0;
  doc.querySelectorAll('.sn-wishlist-count-badge').forEach(function (badge) {
    if (badge.textContent !== String(count)) {
      badge.classList.remove('sn-count-pop');
      void badge.offsetWidth; // Force reflow to re-trigger CSS keyframe pop
      badge.classList.add('sn-count-pop');
    }
    badge.textContent = count;
  });
}

