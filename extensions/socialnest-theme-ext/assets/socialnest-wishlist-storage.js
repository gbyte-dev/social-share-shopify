/**
 * SocialNest Storefront Wishlist - Storage & Identity Module
 * Handles shop resolution, API URLs, and persistent guest UUIDs
 */

function getDoc() {
  return typeof document !== "undefined" ? document : null;
}

function getWin() {
  return typeof window !== "undefined" ? window : null;
}

var memStorage = {};

/**
 * Resolve shop domain from DOM, Shopify global, or location hostname.
 */
export function getShopDomain() {
  var doc = getDoc();
  var win = getWin();
  if (!doc) return "";
  var dock = doc.querySelector("#socialnest-floating-dock-root");
  var wrapper = doc.querySelector(".socialnest-wishlist-page-wrapper");
  return (
    (dock && dock.dataset.shop) ||
    (wrapper && wrapper.dataset.shop) ||
    (win && win.Shopify && win.Shopify.shop) ||
    (typeof location !== "undefined" ? location.hostname : "") ||
    ""
  ).trim();
}

var PROD_APP_URL = "https://socialvire.webvire.com";

export function isInvalidPublicHost(url) {
  if (!url || typeof url !== "string") return true;
  var lower = url.trim().toLowerCase();
  return (
    lower.indexOf(".test") !== -1 ||
    lower.indexOf("localhost") !== -1 ||
    lower.indexOf("127.0.0.1") !== -1
  );
}

export function isLocalEnvironment() {
  if (typeof location !== "undefined" && location.hostname) {
    return isInvalidPublicHost(location.hostname);
  }
  return false;
}

function cleanUrl(url) {
  return (url || "").trim().replace(/\/+$/, "");
}

/**
 * Resolve direct app URL for the active environment (CLI tunnel or production).
 * Strictly guards against calling local (.test) domains from public storefronts.
 */
export function getDirectAppUrl() {
  var win = getWin();
  var doc = getDoc();

  if (win && win.__SOCIALNEST_APP_URL__ && typeof win.__SOCIALNEST_APP_URL__ === "string") {
    var winUrl = cleanUrl(win.__SOCIALNEST_APP_URL__);
    if (winUrl && !isInvalidPublicHost(winUrl)) return winUrl;
  }
  if (win && win.__SOCIALVIRE_APP_URL__ && typeof win.__SOCIALVIRE_APP_URL__ === "string") {
    var winVireUrl = cleanUrl(win.__SOCIALVIRE_APP_URL__);
    if (winVireUrl && !isInvalidPublicHost(winVireUrl)) return winVireUrl;
  }

  if (doc) {
    var dock = doc.querySelector("#socialnest-floating-dock-root");
    var wrapper = doc.querySelector(".socialnest-wishlist-page-wrapper");
    var custom = (dock && dock.dataset.appUrl) || (wrapper && wrapper.dataset.appUrl);
    if (custom && typeof custom === "string" && custom.trim() !== "") {
      var customClean = cleanUrl(custom);
      if (customClean && !isInvalidPublicHost(customClean)) return customClean;
    }
  }

  if (win && win.__SOCIALNEST_PUBLIC_APP_URL__ && typeof win.__SOCIALNEST_PUBLIC_APP_URL__ === "string") {
    var discovered = cleanUrl(win.__SOCIALNEST_PUBLIC_APP_URL__);
    if (discovered && !isInvalidPublicHost(discovered)) return discovered;
  }
  if (win && win.__SOCIALVIRE_PUBLIC_APP_URL__ && typeof win.__SOCIALVIRE_PUBLIC_APP_URL__ === "string") {
    var discoveredVire = cleanUrl(win.__SOCIALVIRE_PUBLIC_APP_URL__);
    if (discoveredVire && !isInvalidPublicHost(discoveredVire)) return discoveredVire;
  }

  // Reliable production fallback: immediately available without awaiting asynchronous dock configuration
  if (PROD_APP_URL && !isInvalidPublicHost(PROD_APP_URL)) {
    return cleanUrl(PROD_APP_URL);
  }

  return "";
}

/**
 * Resolve App Proxy base subpath.
 */
export function getApiBase() {
  var win = getWin();
  var doc = getDoc();

  if (win && win.__SOCIALVIRE_PROXY_PATH__) {
    var pv = cleanUrl(win.__SOCIALVIRE_PROXY_PATH__);
    if (pv.indexOf("/apps/socialnest") !== -1) {
      pv = pv.replace("/apps/socialnest", "/apps/socialvire");
    }
    return pv.indexOf("/wishlist") !== -1 ? pv : pv + "/wishlist";
  }
  if (win && win.__SOCIALNEST_PROXY_PATH__) {
    var p = cleanUrl(win.__SOCIALNEST_PROXY_PATH__);
    if (p.indexOf("/apps/socialnest") !== -1) {
      p = p.replace("/apps/socialnest", "/apps/socialvire");
    }
    return p.indexOf("/wishlist") !== -1 ? p : p + "/wishlist";
  }
  if (doc) {
    var dock = doc.querySelector("#socialnest-floating-dock-root");
    var wrapper = doc.querySelector(".socialnest-wishlist-page-wrapper");
    var customProxy = (dock && dock.dataset.appProxyPath) || (wrapper && wrapper.dataset.appProxyPath);
    if (customProxy && typeof customProxy === "string" && customProxy.trim() !== "") {
      var cp = cleanUrl(customProxy);
      if (cp.indexOf("/apps/socialnest") !== -1) {
        cp = cp.replace("/apps/socialnest", "/apps/socialvire");
      }
      return cp.indexOf("/wishlist") !== -1 ? cp : cp + "/wishlist";
    }
  }
  return "/apps/socialvire/wishlist";
}

/**
 * Retrieve or create a persistent guest UUID stored across storefront visits.
 */
export function getGuestUuid() {
  var win = getWin();
  var uuid = "";
  try {
    uuid =
      win && win.localStorage
        ? win.localStorage.getItem("sn_wishlist_guest_uuid")
        : memStorage["sn_wishlist_guest_uuid"];
    if (!uuid || uuid.length < 16) {
      if (
        typeof crypto !== "undefined" &&
        typeof crypto.randomUUID === "function"
      ) {
        uuid = crypto.randomUUID();
      } else {
        uuid = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(
          /[xy]/g,
          function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === "x" ? r : (r & 0x3) | 0x8;
            return v.toString(16);
          },
        );
      }
      if (win && win.localStorage) {
        win.localStorage.setItem("sn_wishlist_guest_uuid", uuid);
      } else {
        memStorage["sn_wishlist_guest_uuid"] = uuid;
      }
    }
  } catch (e) {
    if (!uuid) {
      uuid =
        "sn_guest_" +
        Date.now() +
        "_" +
        Math.random().toString(36).slice(2, 10);
    }
  }
  return uuid;
}
