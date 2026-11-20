"use strict";
import { initSocialDock, renderDock, initFloatingCta } from "./socialnest-social-dock.js";
import { createProductFeatures } from "./socialnest-product-share.js";

const t = document;
const e = window;

if (e.SocialNestStorefront && typeof e.SocialNestStorefront.renderDock === "function") {
  e.SocialNestStorefront.init();
} else {
  const o = navigator;
  const a = encodeURIComponent;
  const s = location;
  const n = el => !(!e.Shopify?.designMode && el?.dataset.designMode !== "true");
  const i = (selector, root = t) => root?.querySelector(selector);
  const r = "/api/storefront/";

  const l = payload => {
    try {
      payload.shop = g.lastShop || s.hostname;
      payload.referrer = s.href;
      const body = JSON.stringify(payload);
      const endpoint = (g.lastAppUrl || "") + r + "track-click";
      if (!o.sendBeacon?.(endpoint, body)) {
        fetch(endpoint, {
          method: "POST",
          headers: { "content-type": "application/json" },
          body,
          keepalive: true,
        }).catch(() => { });
      }
    } catch { }
  };

  const c = "addEventListener";
  const p = () => g.rootEl || (g.rootEl = i("#socialnest-floating-dock-root"));

  const isLocalDomain = url => {
    if (!url || typeof url !== "string") return true;
    const lower = url.toLowerCase();
    return lower.includes(".test") || lower.includes("localhost") || lower.includes("127.0.0.1");
  };

  const sanitizeUrl = url => {
    if (!url || typeof url !== "string") return "";
    const clean = url.trim().replace(/\/+$/, "");
    return isLocalDomain(clean) ? "" : clean;
  };

  let m = null;

  const h = settings => {
    m?.initProductShare(settings);
    initFloatingCta(settings, l);
  };

  const g = Object.assign(e.SocialNestStorefront || {}, {
    rootEl: null,
    _reqId: 0,
    isProductPage: () => m?.isProductPage() || false,
    getProductInfo: () => m?.getProductInfo() || null,
    openShareModal: (...args) => m?.openShareModal(...args),
    initFloatingCta: settings => initFloatingCta(settings, l),
    renderDock: (links, settings, customRoot) => renderDock(links, settings, customRoot || p(), l),
    initProductShare: (...args) => m?.initProductShare(...args),
    renderExt: h,

    init() {
      const root = p();
      const shop = root?.dataset.shop || e.Shopify?.shop || s.hostname || "";
      const appUrl =
        sanitizeUrl(root?.dataset.appUrl) ||
        sanitizeUrl(e.__SOCIALNEST_APP_URL__) ||
        sanitizeUrl(e.__SOCIALVIRE_APP_URL__) ||
        sanitizeUrl(e.__SOCIALNEST_PUBLIC_APP_URL__) ||
        "" ||
        "https://socialvire.webvire.com";
      const isDesignMode = n(root);

      this.rootEl = root;
      this.lastAppUrl = appUrl;
      this.lastShop = shop;

      initSocialDock({
        getRootEl: () => p(),
        isDesignMode: () => n(p()),
        trackClick: l,
      });

      if (this.settings) {
        h(this.settings);
      }

      if (shop || isDesignMode) {
        this.fetchAndRender(appUrl, shop, isDesignMode);
      }

      e.SocialNestWishlist?.init?.();
    },

    refresh() {
      const root = p();
      if (this.lastAppUrl && this.lastShop) {
        this.fetchAndRender(this.lastAppUrl, this.lastShop, n(root));
      } else {
        this.init();
      }
      e.SocialNestWishlist?.init?.();
    },

    fetchAndRender(appUrl, shop, isDesignMode) {
      const reqId = ++this._reqId;
      const root = p();
      const directUrl = (sanitizeUrl(appUrl) || "https://socialvire.webvire.com") + r + "config?shop=" + a(shop) + (isDesignMode ? "&preview=1" : "");
      let proxyPath = root?.dataset.appProxyPath || e.__SOCIALVIRE_PROXY_PATH__ || e.__SOCIALNEST_PROXY_PATH__ || "/apps/socialvire";
      if (typeof proxyPath === "string" && proxyPath.indexOf("/apps/socialnest") !== -1) {
        proxyPath = proxyPath.replace("/apps/socialnest", "/apps/socialvire");
      }
      const proxyUrl = (proxyPath.replace(/\/+$/, "") + "/config?shop=" + a(shop) + (isDesignMode ? "&preview=1" : ""));

      const doFetch = targetUrl => fetch(targetUrl).then(res => {
        if (!res.ok) throw new Error("HTTP " + res.status);
        return res.json();
      });

      const hasCustomAppUrl = !!(sanitizeUrl(root?.dataset.appUrl) || sanitizeUrl(e.__SOCIALNEST_APP_URL__) || sanitizeUrl(e.__SOCIALVIRE_APP_URL__));

      (hasCustomAppUrl ? doFetch(directUrl) : doFetch(proxyUrl).catch(() => doFetch(directUrl)))
        .then(data => {
          if (reqId === this._reqId) {
            this.settings = data?.settings || {};
            if (data?.public_app_url && !isLocalDomain(data.public_app_url)) {
              e.__SOCIALNEST_PUBLIC_APP_URL__ = data.public_app_url;
              e.__SOCIALVIRE_PUBLIC_APP_URL__ = data.public_app_url;
            }
            if (data?.app_proxy_path) {
              const currentRoot = p();
              if (currentRoot && !currentRoot.dataset.appProxyPath) {
                currentRoot.dataset.appProxyPath = data.app_proxy_path;
              }
              e.__SOCIALNEST_PROXY_PATH__ = data.app_proxy_path;
              e.__SOCIALVIRE_PROXY_PATH__ = data.app_proxy_path;
            }

            if (e.SocialNestWishlist?.getState?.()?.hasLoadError) {
              e.SocialNestWishlist.fetchItems?.();
            }

            const isEnabled = data?.success === true && data?.enabled === true;
            const currentRoot = p();
            if (currentRoot) {
              currentRoot.style.display = isEnabled ? "" : "none";
              if (isEnabled) {
                renderDock(data.links || [], this.settings, currentRoot, l);
              } else {
                currentRoot.innerHTML = "";
              }
            }
            h(this.settings);
          }
        })
        .catch(err => {
          console.warn("[SocialNest] Error fetching storefront widget config:", err);
          const currentRoot = p();
          if (reqId === this._reqId && currentRoot) {
            currentRoot.innerHTML = "";
            currentRoot.style.display = "none";
          }
        });
    },

    copyCurrentPageLink(buttonEl) {
      if (o.clipboard?.writeText) {
        o.clipboard.writeText(s.href);
      }
      const span = i("span", buttonEl);
      if (span) {
        const orig = span.textContent || "Copy";
        span.textContent = "Copied!";
        setTimeout(() => {
          span.textContent = orig;
        }, 2000);
      }
    },
  });

  e.SocialNestStorefront = g;

  // Initialize product features synchronously
  m = createProductFeatures({
    rootEl: () => g.rootEl,
    settings: () => g.settings,
    track: l,
    isDesignMode: () => n(g.rootEl),
  });

  if (t.readyState === "loading") {
    t[c]("DOMContentLoaded", () => g.init());
  } else {
    g.init();
  }

  e[c]("load", () => {
    if (g.rootEl && !g.rootEl.innerHTML) {
      g.init();
    }
  });

  t[c]("shopify:section:load", () => {
    g.refresh();
    e.SocialNestWishlist?.init?.();
  });
}

export { initSocialDock, renderDock, initFloatingCta, createProductFeatures };
