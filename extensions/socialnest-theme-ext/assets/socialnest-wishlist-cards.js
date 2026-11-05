/**
 * SocialNest Storefront Wishlist - Product Cards Integration Module
 * Injects and synchronizes heart buttons on collection & catalog product cards
 */

import { isInWishlist, toggleProduct } from './socialnest-wishlist-api.js';

var doc = typeof document !== 'undefined' ? document : null;
var win = typeof window !== 'undefined' ? window : null;

/**
 * Inject heart buttons on catalog/collection product cards.
 */
export function initCards(onUpdate) {
  if (!doc) return;
  var cards;
  try {
    cards = doc.querySelectorAll('.card-wrapper, .card--product, .product-card, .product-card-wrapper, .grid__item .card, .product-item, .product-grid-item, [data-product-card]');
  } catch (e) {
    cards = doc.querySelectorAll('.card-wrapper, .card--product, .product-card, .product-item, [data-product-card]');
  }

  cards.forEach(function (card) {
    if (card.querySelector('.sn-card-wishlist-btn')) return;

    var link = card.querySelector('a[href*="/products/"]');
    if (!link) return;

    var cleanUrl = (link.href || '').split('?')[0];
    var matchHandle = (cleanUrl.match(/\/products\/([^/?#]+)/) || [])[1] || '';
    var handle = matchHandle.trim();

    var productId = card.dataset.productId
      || card.querySelector('[data-product-id]')?.dataset.productId
      || card.querySelector('form[action*="/cart/add"] [name="id"], form[action*="/cart/add"] [data-product-id]')?.value
      || card.querySelector('input[name="product-id"]')?.value
      || handle;

    if (!productId) return;

    var titleEl = card.querySelector('.card__heading, .product-card__title, .card__title, .product-title, h3 a, h2 a');
    var title = (titleEl ? titleEl.textContent : link.textContent || '').trim();

    var imgEl = card.querySelector('.card__media img, .media img, img');
    var img = imgEl ? (imgEl.src || imgEl.dataset.src || '') : '';

    var priceEl = card.querySelector('.price-item--regular, .price, .product-card__price, .price__regular');
    var price = (priceEl ? priceEl.textContent : '').trim().replace(/\s+/g, ' ');

    var currency = (win && win.Shopify && win.Shopify.currency && win.Shopify.currency.active) ? win.Shopify.currency.active : '$';

    var productData = {
      id: String(productId),
      product_id: String(productId),
      handle: handle,
      title: title,
      url: cleanUrl,
      img: img,
      price: price,
      currency: currency
    };

    var container = card.querySelector('.card__inner, .card__media, .media, .product-card__image-wrapper, .product-item__image-wrapper') || card;
    if (container && container.classList) {
      container.classList.add('sn-card-container-relative');
    }

    var active = isInWishlist(productId) || (handle && isInWishlist(handle));
    var btn = doc.createElement('button');
    btn.type = 'button';
    btn.className = 'sn-card-wishlist-btn' + (active ? ' sn-wishlist-active' : '');
    btn.dataset.productId = String(productId);
    if (handle) {
      btn.dataset.productHandle = handle;
    }
    btn.setAttribute('aria-label', active ? 'Remove from wishlist' : 'Add to wishlist');
    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    btn.setAttribute('title', active ? 'Remove from wishlist' : 'Add to wishlist');
    btn.innerHTML = '<svg class="sn-heart-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';

    btn.onclick = function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleProduct(productData, onUpdate);
    };

    if (container) {
      container.appendChild(btn);
    }
  });
}

/**
 * Synchronize active visual state across all rendered card heart buttons.
 */
export function syncCards() {
  if (!doc) return;
  doc.querySelectorAll('.sn-card-wishlist-btn').forEach(function (btn) {
    var pid = btn.dataset.productId;
    var handle = btn.dataset.productHandle;
    var active = isInWishlist(pid) || (handle && isInWishlist(handle));
    btn.classList.toggle('sn-wishlist-active', !!active);
    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    btn.setAttribute('aria-label', active ? 'Remove from wishlist' : 'Add to wishlist');
    btn.setAttribute('title', active ? 'Remove from wishlist' : 'Add to wishlist');
  });
}

