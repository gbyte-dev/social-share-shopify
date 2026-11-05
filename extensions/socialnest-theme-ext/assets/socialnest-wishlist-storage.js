/**
 * SocialNest Storefront Wishlist - Storage & Identity Module
 * Handles shop resolution, API URLs, and persistent guest UUIDs
 */

var doc = typeof document !== 'undefined' ? document : null;
var win = typeof window !== 'undefined' ? window : null;
var memStorage = {};

/**
 * Resolve shop domain from DOM, Shopify global, or location hostname.
 */
export function getShopDomain() {
  if (!doc) return '';
  var dock = doc.querySelector('#socialnest-floating-dock-root');
  var wrapper = doc.querySelector('.socialnest-wishlist-page-wrapper');
  return (
    (dock && dock.dataset.shop) ||
    (wrapper && wrapper.dataset.shop) ||
    (win && win.Shopify && win.Shopify.shop) ||
    (typeof location !== 'undefined' ? location.hostname : '') ||
    ''
  ).trim();
}

/**
 * Resolve direct app URL if specified on embed blocks (e.g. https://socialnest.test).
 */
export function getDirectAppUrl() {
  if (!doc) return '';
  var dock = doc.querySelector('#socialnest-floating-dock-root');
  var wrapper = doc.querySelector('.socialnest-wishlist-page-wrapper');
  var custom = (dock && dock.dataset.appUrl) || (wrapper && wrapper.dataset.appUrl) || '';
  if (!custom && typeof location !== 'undefined' && (location.hostname.indexOf('myshopify.com') !== -1 || location.hostname.indexOf('.test') !== -1)) {
    custom = 'https://socialnest.test';
  }
  return custom ? custom.replace(/\/+$/, '') : '';
}

/**
 * Resolve proxy API base URL.
 * Uses Shopify App Proxy path (/apps/socialnest/wishlist) by default,
 * with seamless fallback for local development or custom endpoints.
 */
export function getApiBase() {
  var customUrl = (win && win.__sn_wishlist_api_base ? win.__sn_wishlist_api_base : '').trim();
  if (customUrl) {
    return customUrl.replace(/\/+$/, '');
  }
  var directUrl = getDirectAppUrl();
  if (directUrl && (directUrl.indexOf('.test') !== -1 || directUrl.indexOf('localhost') !== -1 || directUrl.indexOf('127.0.0.1') !== -1)) {
    return directUrl + '/api/storefront/wishlist';
  }
  return '/apps/socialnest/wishlist';
}

/**
 * Retrieve or create a persistent guest UUID stored across storefront visits.
 */
export function getGuestUuid() {
  var uuid = '';
  try {
    uuid = (win && win.localStorage) ? win.localStorage.getItem('sn_wishlist_guest_uuid') : memStorage['sn_wishlist_guest_uuid'];
    if (!uuid || uuid.length < 16) {
      if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        uuid = crypto.randomUUID();
      } else {
        uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
          var r = (Math.random() * 16) | 0;
          var v = c === 'x' ? r : (r & 0x3) | 0x8;
          return v.toString(16);
        });
      }
      if (win && win.localStorage) {
        win.localStorage.setItem('sn_wishlist_guest_uuid', uuid);
      } else {
        memStorage['sn_wishlist_guest_uuid'] = uuid;
      }
    }
  } catch (e) {
    if (!uuid) {
      uuid = 'sn_guest_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
    }
  }
  return uuid;
}
