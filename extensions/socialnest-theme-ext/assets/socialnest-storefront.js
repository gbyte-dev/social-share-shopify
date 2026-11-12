
"use strict";
!(function () {
  let t = document,
    e = window;
  if (e.SocialNestStorefront) return e.SocialNestStorefront.init();
  let a = navigator,
    s = encodeURIComponent,
    o = e.ShopifyAnalytics,
    n = location,
    r = (t) => !(!e.Shopify?.designMode && "true" !== t?.dataset.designMode),
    i = (e, a = t) => a?.querySelector(e),
    c = (e = "div") => t.createElement(e),
    p = (t, e, s = e?.textContent || "Copy") => (
      a.clipboard?.writeText(t),
      e &&
        ((e.textContent = "Copied!"),
        setTimeout(() => (e.textContent = s), 2e3))
    ),
    d = "/api/storefront/",
    h = (t) => {
      try {
        ((t.shop = A.lastShop || n.hostname), (t.referrer = n.href));
        let e = JSON.stringify(t),
          s = A.lastAppUrl + d + "track-click";
        a.sendBeacon?.(s, e) ||
          fetch(s, {
            method: "POST",
            headers: { "content-type": "application/json" },
            body: e,
            keepalive: 1,
          }).catch(() => {});
      } catch {}
    },
    u = (t, e, a = "product_share") =>
      h({
        platform: t,
        link_type: a,
        product_id: e?.id,
        product_title: e?.title,
      }),
    g = "addEventListener",
    y = () => 768 >= e.innerWidth || /Mobi|Android/i.test(a.userAgent),
    S = (e) => {
      let a = "socialnest-floating-cta";
      if (
        (i("." + a)?.remove(),
        !(
          e?.cta_enabled &&
          e.cta_text &&
          e.cta_url &&
          (y() ? e.cta_mobile_enabled : e.cta_desktop_enabled)
        ))
      )
        return;
      let s = c("a");
      ((s.className = `${a} ${a}--${e.cta_size || "medium"}${e.cta_mobile_enabled ? "" : " sn-cta-hide-mobile"}${e.cta_desktop_enabled ? "" : " sn-cta-hide-desktop"}`),
        (s.href = e.cta_url),
        (s.target = "_blank"),
        (s.rel = "noopener"),
        (s.style.cssText = `background:${e.cta_background_color || "#111827"};color:${e.cta_text_color || "#fff"}`));
      let o = `<div class="${a}__`;
      ((s.innerHTML =
        (e.cta_show_icon && e.cta_icon_url
          ? `${o}icon"><img src="${e.cta_icon_url}"></div>`
          : "") +
        `${o}content"><span class="${a}__text">${e.cta_text}</span></div>`),
        (s.onclick = () => u("floating_cta", null, "floating_cta")),
        t.body.append(s));
    },
    w = (t, e) => {
      let a = A.rootEl;
      if (!a || !t?.length) return a && (a.innerHTML = "");
      let i = e.position || "bottom_right",
        l = "custom" === i,
        p = e.custom_horizontal_align || "right",
        d = e.custom_vertical_align || "bottom";
      (/^(left|center)$/.test(p) || (p = "right"),
        /^(top|center)$/.test(d) || (d = "bottom"));
      let h = (t, a) => {
          let s = e["custom_offset_" + (t[0] + "_" + t.slice(1)).toLowerCase()];
          return isNaN(s) || null == s || "" === s
            ? a
            : Math.max(0, Math.min(200, +s));
        },
        [u, m] = [h("XDesktop", 24), h("YDesktop", 24)],
        [_, f] = [h("XMobile", u), h("YMobile", m)],
        {
          icon_size: $ = "medium",
          icon_shape: b = "circle",
          hover_animation: g = "lift",
          whatsapp_message: y = "",
        } = e,
        k = 0 != e.show_tooltips;
      if (
        ((a.className = `socialnest-dock-container sn-pos-${i.replace(/_/g, "-")} sn-size-${$} sn-shape-${b} sn-anim-${g}`),
        (a.dataset.hAlign = p),
        (a.dataset.vAlign = d),
        (a.style.cssText = ""),
        l)
      ) {
        let t = "--sn-custom-",
          o =
            "left" === p
              ? `calc(env(safe-area-inset-left,0px) + var(${t}x))`
              : "right" === p
                ? "auto"
                : `calc(50% + var(${t}x))`,
          n =
            "right" === p
              ? `calc(env(safe-area-inset-right,0px) + var(${t}x))`
              : "auto",
          r =
            "top" === d
              ? `calc(env(safe-area-inset-top,0px) + var(${t}y))`
              : "bottom" === d
                ? "auto"
                : `calc(50% + var(${t}y))`,
          s =
            "bottom" === d
              ? `calc(env(safe-area-inset-bottom,0px) + var(${t}y))`
              : "auto",
          c =
            "center" === p && "center" === d
              ? "translate(-50%,-50%)"
              : "center" === p
                ? "translateX(-50%)"
                : "center" === d
                  ? "translateY(-50%)"
                  : "none";
        a.style.cssText = `${t}x-desktop:${u}px;${t}y-desktop:${m}px;${t}x-mobile:${_}px;${t}y-mobile:${f}px;left:${o};right:${n};top:${r};bottom:${s};transform:${c};flex-direction:${"center" === d && "center" !== p ? "column" : "row"}`;
      }
      a.innerHTML = t
        .map((t) => {
          let e = (t.platform || "").toLowerCase().trim(),
            a = t.custom_name || t.name || t.label || e,
            o = t.url || "#";
          "whatsapp" === e &&
            y &&
            !o.includes("text=") &&
            (o += (o.includes("?") ? "&" : "?") + "text=" + s(y));
          let n = t["logo_" + $] || t.logo_medium;
          return `<a href="${o}" target="_blank" rel="noopener" class="sn-dock-item" style="background:${t.brand_color || "#4f46e5"}" aria-label="${a}" data-platform="${e}"${t.id ? " data-link-id=" + t.id : ""}>${n ? `<img src="${n}" class="sn-custom-logo">` : t.icon_svg || ""}${k ? `<span class="sn-dock-tooltip">${a}</span>` : ""}</a>`;
        })
        .join("");
    },
    M = (t) => (productFeatures?.initProductShare(t), S(t)),
    A = (e.SocialNestStorefront = {
      rootEl: null,
      _reqId: 0,
      isProductPage: () => productFeatures?.isProductPage() || false,
      getProductInfo: () => productFeatures?.getProductInfo() || null,
      openShareModal: (...args) => productFeatures?.openShareModal(...args),
      initFloatingCta: S,
      renderDock: w,
      initProductShare: (...args) => productFeatures?.initProductShare(...args),
      renderExt: M,
      init() {
        let t = i("#socialnest-floating-dock-root"),
          a = t?.dataset.shop || e.Shopify?.shop || n.hostname || "",
          s = (t?.dataset.appUrl || "https://socialvire.webvire.com").replace(/\/+$/, ""),
          o = r(t);
        ((this.rootEl = t),
          (this.lastAppUrl = s),
          (this.lastShop = a),
          t &&
            !t.dataset.d &&
            ((t.dataset.d = "1"),
            (t.onclick = (e) => {
              let a = e.target.closest(".sn-dock-item");
              a &&
                (r(t)
                  ? e.preventDefault()
                  : h({
                      link_id: a.dataset.linkId,
                      platform: a.dataset.platform,
                      link_type: "floating_dock",
                    }));
            })),
          this.settings && M(this.settings),
          (a || o) && this.fetchAndRender(s, a, o));
        e.SocialNestWishlist?.init();
      },
      refresh() {
        this.lastAppUrl && this.lastShop
          ? this.fetchAndRender(this.lastAppUrl, this.lastShop, r(this.rootEl))
          : this.init();
        e.SocialNestWishlist?.init();
      },
      fetchAndRender(t, e, a) {
        let o = ++this._reqId,
          n = this.rootEl;
        fetch(t + d + "config?shop=" + s(e) + (a ? "&preview=1" : ""))
          .then((t) => t.json())
          .then((t) => {
            if (o === this._reqId) {
              this.settings = t?.settings || {};
              let r = 0 != t?.enabled && 0 != t?.success;
              (n &&
                ((n.style.display = r ? "" : "none"),
                r ? w(t.links || [], this.settings) : (n.innerHTML = "")),
                M(this.settings));
            }
          })
          .catch(() => {
            o === this._reqId &&
              n &&
              ((n.innerHTML = ""), (n.style.display = "none"));
          });
      },
      copyCurrentPageLink(t) {
        p(n.href, i("span", t));
      },
    });
  let productFeatures = null;
  import(new URL("socialnest-storefront-product.js", document.currentScript?.src || location.href).href)
    .then(({ createProductFeatures }) => {
      productFeatures = createProductFeatures({
        rootEl: () => A.rootEl,
        settings: () => A.settings,
        track: h,
        isDesignMode: () => r(A.rootEl),
      });
      if (A.settings) productFeatures.initProductShare(A.settings);
    })
    .catch((error) => console.error("[SocialNest] Product share module failed to load:", error));
  (
    "loading" == t.readyState
      ? t[g]("DOMContentLoaded", () => A.init())
      : A.init(),
    t[g]("shopify:section:load", () => {
      A.refresh();
      e.SocialNestWishlist?.init();
    }));
})();
