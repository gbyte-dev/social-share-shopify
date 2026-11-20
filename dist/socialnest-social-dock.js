/**
 * SocialNest Storefront - Floating Social Dock Module
 * Handles floating dock rendering, custom platform logos, 9-point alignment,
 * custom offsets, hover animations, tooltips, and floating CTA.
 */

const getDoc = () => typeof document !== "undefined" ? document : (globalThis.document || null);
const getWin = () => typeof window !== "undefined" ? window : (globalThis.window || null);
const getNav = () => typeof navigator !== "undefined" ? navigator : (globalThis.navigator || null);
const enc = encodeURIComponent;

const $ = (selector, root) => (root || getDoc())?.querySelector(selector);

/**
 * Render Floating CTA button
 */
export function initFloatingCta(settings, trackClick) {
  const doc = getDoc();
  const win = getWin();
  const nav = getNav();
  const className = "socialnest-floating-cta";
  $("." + className)?.remove();

  const isMobile = ((win?.innerWidth || 1024) <= 768 || /Mobi|Android/i.test(nav?.userAgent || ""));
  const isCtaAllowed = isMobile ? settings?.cta_mobile_enabled : settings?.cta_desktop_enabled;

  if (!settings?.cta_enabled || !settings?.cta_text || !settings?.cta_url || !isCtaAllowed) {
    return;
  }

  const ctaEl = doc.createElement("a");
  ctaEl.className = `${className} ${className}--${settings.cta_size || "medium"}${settings.cta_mobile_enabled ? "" : " sn-cta-hide-mobile"}${settings.cta_desktop_enabled ? "" : " sn-cta-hide-desktop"}`;
  ctaEl.href = settings.cta_url;
  ctaEl.target = "_blank";
  ctaEl.rel = "noopener";
  ctaEl.style.cssText = `background:${settings.cta_background_color || "#111827"};color:${settings.cta_text_color || "#fff"}`;
  const prefix = `<div class="${className}__`;
  ctaEl.innerHTML =
    (settings.cta_show_icon && settings.cta_icon_url
      ? `${prefix}icon"><img src="${settings.cta_icon_url}"></div>`
      : "") +
    `${prefix}content"><span class="${className}__text">${settings.cta_text}</span></div>`;
  ctaEl.onclick = () => trackClick?.({ platform: "floating_cta", link_type: "floating_cta" });
  doc.body.append(ctaEl);
}

/**
 * Render Floating Social Dock with links, positioning, and animations
 */
export function renderDock(links, settings, root, trackClick) {
  if (!root || !links?.length) {
    if (root) root.innerHTML = "";
    return;
  }

  const position = settings?.position || "bottom_right";
  const isCustom = position === "custom";
  let hAlign = settings?.custom_horizontal_align || "right";
  let vAlign = settings?.custom_vertical_align || "bottom";
  if (!/^(left|center)$/.test(hAlign)) hAlign = "right";
  if (!/^(top|center)$/.test(vAlign)) vAlign = "bottom";

  const getOffset = (key, fallback) => {
    const normKey = key.toLowerCase();
    const prop = "custom_offset_" + normKey;
    const propAlt = "custom_offset_" + (normKey.includes("_") ? normKey : (normKey.startsWith("x") ? "x_" + normKey.slice(1) : "y_" + normKey.slice(1)));
    const val = settings?.[prop] ?? settings?.[propAlt];
    return isNaN(val) || val === null || val === "" ? fallback : Math.max(0, Math.min(200, +val));
  };

  const [xDesktop, yDesktop] = [getOffset("x_desktop", 24), getOffset("y_desktop", 24)];
  const [xMobile, yMobile] = [getOffset("x_mobile", 16), getOffset("y_mobile", 16)];
  const iconSize = settings?.icon_size || "medium";
  const iconShape = settings?.icon_shape || "circle";
  const hoverAnim = settings?.hover_animation || "lift";
  const whatsappMsg = settings?.whatsapp_message || "";
  const showTooltips = settings?.show_tooltips !== false;

  root.className = `socialnest-dock-container sn-pos-${position.replace(/_/g, "-")} sn-size-${iconSize} sn-shape-${iconShape} sn-anim-${hoverAnim}`;
  root.dataset.hAlign = hAlign;
  root.dataset.vAlign = vAlign;
  root.style.cssText = "";

  if (isCustom) {
    const t = "--sn-custom-";
    const left = hAlign === "left" ? `calc(env(safe-area-inset-left,0px) + var(${t}x))` : hAlign === "right" ? "auto" : `calc(50% + var(${t}x))`;
    const right = hAlign === "right" ? `calc(env(safe-area-inset-right,0px) + var(${t}x))` : "auto";
    const top = vAlign === "top" ? `calc(env(safe-area-inset-top,0px) + var(${t}y))` : vAlign === "bottom" ? "auto" : `calc(50% + var(${t}y))`;
    const bottom = vAlign === "bottom" ? `calc(env(safe-area-inset-bottom,0px) + var(${t}y))` : "auto";
    const transform = hAlign === "center" && vAlign === "center" ? "translate(-50%,-50%)" : hAlign === "center" ? "translateX(-50%)" : vAlign === "center" ? "translateY(-50%)" : "none";
    root.style.cssText = `${t}x-desktop:${xDesktop}px;${t}y-desktop:${yDesktop}px;${t}x-mobile:${xMobile}px;${t}y-mobile:${yMobile}px;left:${left};right:${right};top:${top};bottom:${bottom};transform:${transform};flex-direction:${vAlign === "center" && hAlign !== "center" ? "column" : "row"}`;
  }

  root.innerHTML = links
    .map(item => {
      const platform = (item.platform || "").toLowerCase().trim();
      const label = item.custom_name || item.name || item.label || platform;
      let url = item.url || "#";
      if (platform === "whatsapp" && whatsappMsg && !url.includes("text=")) {
        url += (url.includes("?") ? "&" : "?") + "text=" + enc(whatsappMsg);
      }
      const logo = item["logo_" + iconSize] || item.logo_medium;
      return `<a href="${url}" target="_blank" rel="noopener" class="sn-dock-item" style="background:${item.brand_color || "#4f46e5"}" aria-label="${label}" data-platform="${platform}"${item.id ? ` data-link-id="${item.id}"` : ""}>${logo ? `<img src="${logo}" class="sn-custom-logo" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiNmZmZmZmYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIj48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSIxMCIvPjxwYXRoIGQ9Ik0xMiA4djhNOCAxMmg4Ii8+PC9zdmc+';">` : (item.icon_svg || "")}${showTooltips ? `<span class="sn-dock-tooltip">${label}</span>` : ""}</a>`;
    })
    .join("");
}

/**
 * Initialize Social Dock click tracking delegation on root element
 */
export function initSocialDock(context) {
  const root = context.getRootEl();
  if (root && !root.dataset.snBound) {
    root.dataset.snBound = "1";
    root.onclick = event => {
      const item = event.target.closest(".sn-dock-item");
      if (item) {
        if (context.isDesignMode()) {
          event.preventDefault();
        } else {
          context.trackClick({
            link_id: item.dataset.linkId,
            platform: item.dataset.platform,
            link_type: "floating_dock",
          });
        }
      }
    };
  }
}
