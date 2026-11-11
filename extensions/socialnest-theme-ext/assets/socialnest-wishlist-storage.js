/**
 * SocialNest Storefront Wishlist - Storage & Identity Module
 * Handles shop resolution, API URLs, and persistent guest UUIDs
 */

var doc = typeof document !== "undefined" ? document : null;
var win = typeof window !== "undefined" ? window : null;
var memStorage = {};

/**
 * Resolve shop domain from DOM, Shopify global, or location hostname.
 */
export function getShopDomain() {
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

/**
 * Resolve direct app URL if specified on embed blocks (e.g. https://socialvire.webvire.com).
 */
export function getDirectAppUrl() {
  return 'https://socialvire.webvire.com';
}

export function getApiBase() {
  return '/apps/socialvire/wishlist';
}

/**
 * Retrieve or create a persistent guest UUID stored across storefront visits.
 */
export function getGuestUuid() {
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
