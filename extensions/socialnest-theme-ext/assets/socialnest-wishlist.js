/**
 * SocialNest Storefront Wishlist System - Main Entry Point
 * Orchestrates storage, API synchronization, product cards, header badge, and dedicated wishlist page
 */

import { getGuestUuid } from './socialnest-wishlist-storage.js';
import {
  getItems,
  getState,
  isInWishlist,
  addProduct,
  removeProduct,
  toggleProduct,
  fetchWishlist
} from './socialnest-wishlist-api.js';
import { initCards, syncCards } from './socialnest-wishlist-cards.js';
import { initHeader, syncHeader } from './socialnest-wishlist-header.js';
import { renderWishlistPage } from './socialnest-wishlist-page.js';

var doc = typeof document !== 'undefined' ? document : null;
var win = typeof window !== 'undefined' ? window : null;

/**
 * Synchronize all UI elements (header counter, card heart buttons, and wishlist page grid).
 */
export function updateUI() {
  var items = getItems();
  syncHeader(items.length);
  syncCards();
  renderWishlistPage(updateUI);

  if (win) {
    try {
      win.dispatchEvent(new CustomEvent('socialnest:wishlist:updated', {
        detail: { items: items, count: items.length }
      }));
    } catch (e) {}
  }
}

/**
 * Initialize all wishlist integrations on the current page.
 */
export function init() {
  var items = getItems();
  initCards(updateUI);
  initHeader(items.length);
  renderWishlistPage(updateUI);

  var state = getState();
  if (!state.isInitialized) {
    state.isInitialized = true;
    fetchWishlist(updateUI);
  }
}

// Module export instance for programmatic access
export var wishlist = {
  getGuestUuid: getGuestUuid,
  getList: getItems,
  getState: getState,
  has: isInWishlist,
  add: function(p) { return addProduct(p, updateUI); },
  remove: function(id) { return removeProduct(id, updateUI); },
  toggle: function(p) { return toggleProduct(p, updateUI); },
  fetchItems: function() { return fetchWishlist(updateUI); },
  initCards: function() { return initCards(updateUI); },
  initHeader: function() { return initHeader(getItems().length); },
  renderPage: function() { return renderWishlistPage(updateUI); },
  updateUI: updateUI,
  init: init
};

// Register to global window for storefront integrations
if (win) {
  if (win.SocialNestWishlistLoaded && win.SocialNestWishlist) {
    win.SocialNestWishlist.init();
  } else {
    win.SocialNestWishlistLoaded = true;
    win.SocialNestWishlist = wishlist;
    win.SocialNestStorefront = win.SocialNestStorefront || {};
    win.SocialNestStorefront.wishlist = wishlist;
    win.SocialNestStorefront.getWishlist = wishlist.getList;
    win.SocialNestStorefront.isInWishlist = wishlist.has;
    win.SocialNestStorefront.addToWishlist = wishlist.add;
    win.SocialNestStorefront.removeFromWishlist = wishlist.remove;
    win.SocialNestStorefront.toggleWishlist = wishlist.toggle;
    win.SocialNestStorefront.initWishlist = wishlist.init;
    win.SocialNestStorefront.updateWishlistUI = wishlist.updateUI;

    if (doc) {
      doc.addEventListener('shopify:section:load', function() {
        wishlist.init();
      });

      var debounceTimer;
      if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(function() {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(function() {
            initCards(updateUI);
            initHeader(getItems().length);
          }, 80);
        }).observe(doc.body || doc.documentElement, { childList: true, subtree: true });
      }

      if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }
    }
  }
}

export default wishlist;
