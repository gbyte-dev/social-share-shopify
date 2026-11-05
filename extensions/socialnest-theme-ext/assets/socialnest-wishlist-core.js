/**
 * SocialNest Storefront Wishlist Core System
 * Database-backed, multi-store, guest & customer persistent wishlist
 * Compatible with Shopify App Proxy & Theme App Extensions
 */
(function () {
  'use strict';

  var doc = document;
  var win = window;

  if (win.SocialNestWishlistLoaded && win.SocialNestWishlist) {
    win.SocialNestWishlist.init();
    return;
  }
  win.SocialNestWishlistLoaded = true;

  // In-memory wishlist cache & request lifecycle state
  var items = [];
  var inFlight = {};
  var isInitialLoading = true;
  var hasLoadError = false;
  var isInitialized = false;
  var migrationPromise = null;

  /**
   * Resolve shop domain from DOM, Shopify global, or location hostname.
   */
  function getShopDomain() {
    var dock = doc.querySelector('#socialnest-floating-dock-root');
    var wrapper = doc.querySelector('.socialnest-wishlist-page-wrapper');
    return (
      (dock && dock.dataset.shop) ||
      (wrapper && wrapper.dataset.shop) ||
      (win.Shopify && win.Shopify.shop) ||
      location.hostname ||
      ''
    ).trim();
  }

  /**
   * Resolve direct app URL if specified on embed blocks (e.g. https://socialnest.test).
   */
  function getDirectAppUrl() {
    var dock = doc.querySelector('#socialnest-floating-dock-root');
    var wrapper = doc.querySelector('.socialnest-wishlist-page-wrapper');
    var custom = (dock && dock.dataset.appUrl) || (wrapper && wrapper.dataset.appUrl) || '';
    if (!custom && (location.hostname.indexOf('myshopify.com') !== -1 || location.hostname.indexOf('.test') !== -1)) {
      custom = 'https://socialnest.test';
    }
    return custom ? custom.replace(/\/+$/, '') : '';
  }

  /**
   * Resolve proxy API base URL.
   * Uses Shopify App Proxy path (/apps/socialnest/wishlist) by default,
   * with seamless fallback for local development or custom endpoints.
   */
  function getApiBase() {
    var customUrl = (win.__sn_wishlist_api_base || '').trim();
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
  function getGuestUuid() {
    var uuid = '';
    try {
      uuid = win.localStorage ? win.localStorage.getItem('sn_wishlist_guest_uuid') : '';
      if (!uuid || uuid.length < 16) {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
          uuid = crypto.randomUUID();
        } else {
          uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
          });
        }
        if (win.localStorage) {
          win.localStorage.setItem('sn_wishlist_guest_uuid', uuid);
        }
      }
    } catch (e) {
      if (!uuid) {
        uuid = 'sn_guest_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
      }
    }
    return uuid;
  }

  /**
   * Secure fetch wrapper targeting Shopify App Proxy with identity headers and direct fallback.
   */
  function apiRequest(endpoint, options) {
    options = options || {};
    var base = getApiBase();
    var cleanEndpoint = endpoint ? (endpoint.charAt(0) === '/' ? endpoint : '/' + endpoint) : '';
    var url = base + cleanEndpoint;
    var guestUuid = getGuestUuid();
    var shop = getShopDomain();

    var method = (options.method || 'GET').toUpperCase();
    if (method === 'GET') {
      var sep = url.indexOf('?') !== -1 ? '&' : '?';
      url += sep + 'guest_uuid=' + encodeURIComponent(guestUuid);
      if (shop) {
        url += '&shop=' + encodeURIComponent(shop);
      }
    }

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
      } catch (e) { }
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
      .then(function (res) {
        console.log('[SN Wishlist] API response received:', res.status, res.statusText, url);
        if (!res.ok) {
          return res.json().catch(function () {
            return { success: false, error: 'HTTP ' + res.status };
          }).then(function (errJson) {
            throw errJson;
          });
        }
        return res.json();
      })
      .catch(function (err) {
        console.warn('[SN Wishlist] Request error on primary endpoint:', url, err);

        var directUrl = getDirectAppUrl();
        if (directUrl && url.indexOf('/apps/socialnest/wishlist') !== -1) {
          var fallbackBase = directUrl + '/api/storefront/wishlist';
          var fallbackUrl = fallbackBase + cleanEndpoint;
          if (method === 'GET') {
            var fsep = fallbackUrl.indexOf('?') !== -1 ? '&' : '?';
            fallbackUrl += fsep + 'guest_uuid=' + encodeURIComponent(guestUuid);
            if (shop) {
              fallbackUrl += '&shop=' + encodeURIComponent(shop);
            }
          }
          console.log('[SN Wishlist] Retrying via direct storefront fallback API:', fallbackUrl);
          var fallbackOptions = Object.assign({}, options);
          return fetch(fallbackUrl, fallbackOptions).then(function (fbRes) {
            console.log('[SN Wishlist] Fallback API response received:', fbRes.status, fbRes.statusText);
            if (!fbRes.ok) {
              return fbRes.json().catch(function () {
                return { success: false, error: 'HTTP ' + fbRes.status };
              }).then(function (fbErr) {
                throw fbErr;
              });
            }
            return fbRes.json();
          });
        }

        throw err;
      });
  }

  /**
   * One-time safe migration from browser localStorage (socialnest_wishlist_v1) to MySQL.
   * Only clears local data after successful server confirmation.
   */
  function migrateLegacyLocalStorage() {
    if (migrationPromise) {
      return migrationPromise;
    }

    migrationPromise = new Promise(function (resolve) {
      try {
        var raw = win.localStorage ? win.localStorage.getItem('socialnest_wishlist_v1') : null;
        if (!raw) {
          return resolve();
        }
        var legacyItems = JSON.parse(raw);
        if (!Array.isArray(legacyItems) || legacyItems.length === 0) {
          if (win.localStorage) {
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
        }).then(function (res) {
          if (res && res.success) {
            if (win.localStorage) {
              win.localStorage.removeItem('socialnest_wishlist_v1');
            }
          }
          resolve();
        }).catch(function () {
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
  function isInWishlist(idOrHandle) {
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
  function fetchWishlist() {
    isInitialLoading = true;
    hasLoadError = false;
    updateUI();

    return migrateLegacyLocalStorage()
      .then(function () {
        return apiRequest('');
      })
      .then(function (res) {
        console.log('[SN Wishlist] fetchWishlist loaded response:', res);
        isInitialLoading = false;
        hasLoadError = false;
        if (res && res.success && Array.isArray(res.items)) {
          items = res.items;
        } else if (res && Array.isArray(res)) {
          items = res;
        }
        updateUI();
        return items;
      })
      .catch(function (err) {
        console.error('[SN Wishlist] fetchWishlist request failed:', err);
        isInitialLoading = false;
        hasLoadError = true;
        updateUI();
        return items;
      });
  }

  /**
   * Add a product to the database wishlist.
   */
  function addProduct(product) {
    if (!product || (!product.id && !product.product_id && !product.handle)) {
      return Promise.resolve(items);
    }

    var pid = String(product.product_id || product.id || product.handle);
    if (isInWishlist(pid) || inFlight[pid]) {
      return Promise.resolve(items);
    }

    inFlight[pid] = true;

    // Optimistic UI update
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
    updateUI();

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
    }).then(function (res) {
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
      updateUI();
      return items;
    }).catch(function (err) {
      delete inFlight[pid];
      console.error('[SN Wishlist] addProduct failed to save, rolling back UI:', err);
      items = items.filter(function (item) {
        return String(item.id || item.product_id) !== pid;
      });
      updateUI();
      throw err;
    });
  }

  /**
   * Remove a product from the database wishlist.
   */
  function removeProduct(idOrHandle) {
    if (!idOrHandle) return Promise.resolve(items);
    var pid = String(idOrHandle).trim();
    if (inFlight[pid]) return Promise.resolve(items);

    inFlight[pid] = true;

    var removedBackup = items.filter(function (item) {
      return String(item.id || item.product_id) === pid || (item.handle && String(item.handle) === pid);
    });

    items = items.filter(function (item) {
      return String(item.id || item.product_id) !== pid && (!item.handle || String(item.handle) !== pid);
    });
    updateUI();

    return apiRequest('/remove', {
      method: 'POST',
      body: JSON.stringify({
        product_id: pid,
        guest_uuid: getGuestUuid()
      })
    }).then(function () {
      delete inFlight[pid];
      updateUI();
      return items;
    }).catch(function (err) {
      delete inFlight[pid];
      if (removedBackup.length > 0) {
        items = removedBackup.concat(items);
      }
      updateUI();
      throw err;
    });
  }

  /**
   * Toggle product wishlist status.
   */
  function toggleProduct(product) {
    if (!product) return Promise.resolve(items);
    var pid = String(product.product_id || product.id || product.handle || '');
    if (!pid) return Promise.resolve(items);

    if (isInWishlist(pid)) {
      return removeProduct(pid);
    } else {
      return addProduct(product);
    }
  }

  /**
   * Inject heart buttons on catalog/collection product cards.
   */
  function initCards() {
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

      var currency = (win.Shopify && win.Shopify.currency && win.Shopify.currency.active) ? win.Shopify.currency.active : '$';

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
      if (container.classList) {
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
        toggleProduct(productData);
      };

      container.appendChild(btn);
    });
  }

  /**
   * Inject header wishlist button & count badge.
   */
  function initHeader() {
    var existingBtn = doc.querySelector('.sn-header-wishlist-btn');
    var count = items.length;
    var header = doc.querySelector('#header-component, .header, header');
    var columns = doc.querySelector('.header__columns');

    if (existingBtn) {
      if (header) header.classList.add('sn-has-header-wishlist');
      if (columns) columns.classList.add('sn-has-header-wishlist');

      var badge = existingBtn.querySelector('.sn-wishlist-count-badge');
      if (badge) {
        if (badge.textContent !== String(count)) {
          badge.classList.remove('sn-count-pop');
          void badge.offsetWidth;
          badge.classList.add('sn-count-pop');
        }
        badge.textContent = count;
      }
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

      if (cartTarget) {
        cartTarget.parentNode.insertBefore(a, cartTarget);
      } else if (iconsContainer) {
        iconsContainer.appendChild(a);
      }

      if (header) header.classList.add('sn-has-header-wishlist');
      if (columns) columns.classList.add('sn-has-header-wishlist');
    }
  }

  /**
   * Sync active state across all visible buttons, badges, and the dedicated page.
   */
  function updateUI() {
    var count = items.length;

    doc.querySelectorAll('.sn-wishlist-count-badge').forEach(function (badge) {
      if (badge.textContent !== String(count)) {
        badge.classList.remove('sn-count-pop');
        void badge.offsetWidth;
        badge.classList.add('sn-count-pop');
      }
      badge.textContent = count;
    });

    doc.querySelectorAll('.sn-card-wishlist-btn').forEach(function (btn) {
      var pid = btn.dataset.productId;
      var handle = btn.dataset.productHandle;
      var active = isInWishlist(pid) || (handle && isInWishlist(handle));
      btn.classList.toggle('sn-wishlist-active', !!active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      btn.setAttribute('aria-label', active ? 'Remove from wishlist' : 'Add to wishlist');
      btn.setAttribute('title', active ? 'Remove from wishlist' : 'Add to wishlist');
    });

    // Invoke page renderer if loaded
    if (instance && typeof instance.renderPage === 'function') {
      instance.renderPage();
    } else if (typeof win.__sn_wishlist_render_page === 'function') {
      win.__sn_wishlist_render_page();
    }

    try {
      win.dispatchEvent(new CustomEvent('socialnest:wishlist:updated', {
        detail: { items: items, count: count }
      }));
    } catch (e) { }
  }

  /**
   * Main initializer.
   */
  function init() {
    initCards();
    initHeader();

    if (instance && typeof instance.renderPage === 'function') {
      instance.renderPage();
    } else if (typeof win.__sn_wishlist_render_page === 'function') {
      win.__sn_wishlist_render_page();
    }

    if (!isInitialized) {
      isInitialized = true;
      fetchWishlist();
    }
  }

  // Export module to global scope
  var instance = {
    getGuestUuid: getGuestUuid,
    getList: function () { return items; },
    getState: function () {
      return {
        items: items,
        inFlight: inFlight,
        isInitialLoading: isInitialLoading,
        hasLoadError: hasLoadError,
        isInitialized: isInitialized
      };
    },
    has: isInWishlist,
    add: addProduct,
    remove: removeProduct,
    toggle: toggleProduct,
    fetchItems: fetchWishlist,
    initCards: initCards,
    initHeader: initHeader,
    renderPage: typeof win.__sn_wishlist_render_page === 'function' ? win.__sn_wishlist_render_page : null,
    updateUI: updateUI,
    init: init
  };

  win.SocialNestWishlist = instance;
  win.SocialNestStorefront = win.SocialNestStorefront || {};
  win.SocialNestStorefront.wishlist = instance;
  win.SocialNestStorefront.getWishlist = instance.getList;
  win.SocialNestStorefront.isInWishlist = instance.has;
  win.SocialNestStorefront.addToWishlist = instance.add;
  win.SocialNestStorefront.removeFromWishlist = instance.remove;
  win.SocialNestStorefront.toggleWishlist = instance.toggle;
  win.SocialNestStorefront.initWishlist = instance.init;
  win.SocialNestStorefront.updateWishlistUI = instance.updateUI;

  // Re-initialize when Shopify theme sections load dynamically
  doc.addEventListener('shopify:section:load', function () {
    instance.init();
  });

  // Observe DOM for infinite scrolling & dynamic product catalogs
  var debounceTimer;
  if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        initCards();
        initHeader();
      }, 80);
    }).observe(doc.body || doc.documentElement, { childList: true, subtree: true });
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

