/**
 * SocialNest Storefront Wishlist - API & Data Synchronization Module
 * Manages database synchronization, optimistic updates, and API requests
 */

import { getShopDomain, getDirectAppUrl, getApiBase, getGuestUuid } from './socialnest-wishlist-storage.js';

var win = typeof window !== 'undefined' ? window : null;

var items = [];
var inFlight = {};
var isInitialLoading = true;
var hasLoadError = false;
var isInitialized = false;
var migrationPromise = null;

export function getItems() {
  return items;
}

export function getState() {
  return {
    items: items,
    inFlight: inFlight,
    isInitialLoading: isInitialLoading,
    hasLoadError: hasLoadError,
    isInitialized: isInitialized
  };
}

function buildRequestUrl(base, endpoint, method, guestUuid, shop) {
  var clean = endpoint ? (endpoint.charAt(0) === '/' ? endpoint : '/' + endpoint) : '';
  var url = base + clean;
  if (method === 'GET') {
    url += (url.indexOf('?') !== -1 ? '&' : '?') + 'guest_uuid=' + encodeURIComponent(guestUuid);
    if (shop) {
      url += '&shop=' + encodeURIComponent(shop);
    }
  }
  return url;
}

function parseResponse(res) {
  if (!res.ok) {
    return res.json().catch(function() {
      return { success: false, error: 'HTTP ' + res.status };
    }).then(function(err) {
      throw err;
    });
  }
  return res.json();
}

/**
 * Secure fetch wrapper targeting Shopify App Proxy with identity headers and direct fallback.
 */
export function apiRequest(endpoint, options) {
  options = options || {};
  var guestUuid = getGuestUuid();
  var shop = getShopDomain();
  var method = (options.method || 'GET').toUpperCase();
  var url = buildRequestUrl(getApiBase(), endpoint, method, guestUuid, shop);

  var headers = {
    'Accept': 'application/json',
    'X-Guest-UUID': guestUuid
  };
  if (shop) {
    headers['X-Shopify-Shop-Domain'] = shop;
  }

  if (options.body && typeof options.body === 'string') {
    headers['Content-Type'] = 'application/json';
    try {
      var parsed = JSON.parse(options.body);
      if (shop && !parsed.shop) {
        parsed.shop = shop;
        options.body = JSON.stringify(parsed);
      }
    } catch (e) {}
  }

  if (options.headers) {
    for (var k in options.headers) {
      if (Object.prototype.hasOwnProperty.call(options.headers, k)) {
        headers[k] = options.headers[k];
      }
    }
  }

  options.headers = headers;
  options.credentials = 'same-origin';

  console.log('[SN Wishlist] API request dispatch:', method, url);

  return fetch(url, options)
    .then(parseResponse)
    .catch(function(err) {
      console.warn('[SN Wishlist] Request error on primary endpoint:', url, err);

      var directUrl = getDirectAppUrl();
      if (directUrl && url.indexOf('/apps/socialnest/wishlist') !== -1) {
        var fallbackUrl = buildRequestUrl(directUrl + '/api/storefront/wishlist', endpoint, method, guestUuid, shop);
        console.log('[SN Wishlist] Retrying via direct storefront fallback API:', fallbackUrl);
        var fallbackOptions = Object.assign({}, options);
        return fetch(fallbackUrl, fallbackOptions).then(parseResponse);
      }

      throw err;
    });
}

/**
 * One-time safe migration from browser localStorage (socialnest_wishlist_v1) to server.
 */
export function migrateLegacyLocalStorage() {
  if (migrationPromise) {
    return migrationPromise;
  }

  migrationPromise = new Promise(function(resolve) {
    try {
      var raw = (win && win.localStorage) ? win.localStorage.getItem('socialnest_wishlist_v1') : null;
      if (!raw) {
        return resolve();
      }
      var legacyItems = JSON.parse(raw);
      if (!Array.isArray(legacyItems) || legacyItems.length === 0) {
        if (win && win.localStorage) {
          win.localStorage.removeItem('socialnest_wishlist_v1');
        }
        return resolve();
      }

      apiRequest('/migrate-local', {
        method: 'POST',
        body: JSON.stringify({
          items: legacyItems,
          guest_uuid: getGuestUuid()
        })
      }).then(function(res) {
        if (res && res.success && win && win.localStorage) {
          win.localStorage.removeItem('socialnest_wishlist_v1');
        }
        resolve();
      }).catch(function() {
        resolve();
      });
    } catch (err) {
      resolve();
    }
  });

  return migrationPromise;
}

/**
 * Check if a product ID or handle exists in the wishlist.
 */
export function isInWishlist(idOrHandle) {
  if (!idOrHandle) return false;
  var target = String(idOrHandle).trim();
  for (var i = 0; i < items.length; i++) {
    var item = items[i];
    if (String(item.id || item.product_id) === target) return true;
    if (item.handle && String(item.handle) === target) return true;
  }
  return false;
}

/**
 * Fetch wishlist items from Laravel database via App Proxy or direct storefront API.
 */
export function fetchWishlist(onUpdate) {
  isInitialLoading = true;
  hasLoadError = false;
  if (typeof onUpdate === 'function') onUpdate();

  return migrateLegacyLocalStorage()
    .then(function() {
      return apiRequest('');
    })
    .then(function(res) {
      console.log('[SN Wishlist] fetchWishlist loaded response:', res);
      isInitialLoading = false;
      hasLoadError = false;
      if (res && res.success && Array.isArray(res.items)) {
        items = res.items;
      } else if (res && Array.isArray(res)) {
        items = res;
      }
      if (typeof onUpdate === 'function') onUpdate();
      return items;
    })
    .catch(function(err) {
      console.error('[SN Wishlist] fetchWishlist request failed:', err);
      isInitialLoading = false;
      hasLoadError = true;
      if (typeof onUpdate === 'function') onUpdate();
      return items;
    });
}

/**
 * Add a product to the database wishlist.
 */
export function addProduct(product, onUpdate) {
  if (!product || (!product.id && !product.product_id && !product.handle)) {
    return Promise.resolve(items);
  }

  var pid = String(product.product_id || product.id || product.handle);
  if (isInWishlist(pid) || inFlight[pid]) {
    return Promise.resolve(items);
  }

  inFlight[pid] = true;

  var optimisticItem = {
    id: pid,
    product_id: pid,
    variant_id: product.variant_id ? String(product.variant_id) : null,
    handle: product.handle ? String(product.handle) : '',
    title: product.title ? String(product.title) : '',
    url: product.url ? String(product.url) : '',
    img: product.img || product.image || '',
    price: product.price ? String(product.price) : '',
    currency: product.currency || '$'
  };
  items.unshift(optimisticItem);
  if (typeof onUpdate === 'function') onUpdate();

  var payload = {
    product_id: pid,
    variant_id: optimisticItem.variant_id,
    title: optimisticItem.title,
    handle: optimisticItem.handle,
    image: optimisticItem.img,
    price: optimisticItem.price,
    url: optimisticItem.url,
    currency: optimisticItem.currency,
    guest_uuid: getGuestUuid(),
    shop: getShopDomain()
  };

  console.log('[SN Wishlist] addProduct initiated for product:', pid, payload);

  return apiRequest('/add', {
    method: 'POST',
    body: JSON.stringify(payload)
  }).then(function(res) {
    delete inFlight[pid];
    console.log('[SN Wishlist] addProduct successfully synced to database:', res);
    if (res && res.success && res.item) {
      for (var i = 0; i < items.length; i++) {
        if (String(items[i].id || items[i].product_id) === pid) {
          items[i] = res.item;
          break;
        }
      }
    }
    if (typeof onUpdate === 'function') onUpdate();
    return items;
  }).catch(function(err) {
    delete inFlight[pid];
    console.error('[SN Wishlist] addProduct failed to save, rolling back UI:', err);
    items = items.filter(function(item) {
      return String(item.id || item.product_id) !== pid;
    });
    if (typeof onUpdate === 'function') onUpdate();
    throw err;
  });
}

/**
 * Remove a product from the database wishlist.
 */
export function removeProduct(idOrHandle, onUpdate) {
  if (!idOrHandle) return Promise.resolve(items);
  var pid = String(idOrHandle).trim();
  if (inFlight[pid]) return Promise.resolve(items);

  inFlight[pid] = true;

  var removedBackup = items.filter(function(item) {
    return String(item.id || item.product_id) === pid || (item.handle && String(item.handle) === pid);
  });

  items = items.filter(function(item) {
    return String(item.id || item.product_id) !== pid && (!item.handle || String(item.handle) !== pid);
  });
  if (typeof onUpdate === 'function') onUpdate();

  return apiRequest('/remove', {
    method: 'POST',
    body: JSON.stringify({
      product_id: pid,
      guest_uuid: getGuestUuid()
    })
  }).then(function() {
    delete inFlight[pid];
    if (typeof onUpdate === 'function') onUpdate();
    return items;
  }).catch(function(err) {
    delete inFlight[pid];
    if (removedBackup.length > 0) {
      items = removedBackup.concat(items);
    }
    if (typeof onUpdate === 'function') onUpdate();
    throw err;
  });
}

/**
 * Toggle product wishlist status.
 */
export function toggleProduct(product, onUpdate) {
  if (!product) return Promise.resolve(items);
  var pid = String(product.product_id || product.id || product.handle || '');
  if (!pid) return Promise.resolve(items);

  if (isInWishlist(pid)) {
    return removeProduct(pid, onUpdate);
  } else {
    return addProduct(product, onUpdate);
  }
}
