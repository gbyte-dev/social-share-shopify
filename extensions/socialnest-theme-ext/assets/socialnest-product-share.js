/**
 * SocialNest Storefront - Product Share Module
 * Handles product page detection, metadata extraction, Horizon side-by-side
 * placement with accelerated checkout (Buy it now), modal sharing, and inline share tracking.
 */

const getDoc = () => typeof document !== "undefined" ? document : (globalThis.document || null);
const getWin = () => typeof window !== "undefined" ? window : (globalThis.window || null);
const getNav = () => typeof navigator !== "undefined" ? navigator : (globalThis.navigator || null);
const getLoc = () => typeof location !== "undefined" ? location : (globalThis.location || null);
const enc = encodeURIComponent;

const $ = (selector, root) => (root || getDoc())?.querySelector(selector);
const $attr = (selector, attr, root) => {
  const el = $(selector, root);
  return (el && (attr ? el.getAttribute(attr) : el.textContent)) || "";
};
const createEl = (tag = "div") => getDoc()?.createElement(tag);

const copyText = (value, btn, originalLabel = btn?.textContent || "Copy") => {
  const nav = getNav();
  if (nav?.clipboard?.writeText) {
    nav.clipboard.writeText(value);
  }
  if (btn) {
    btn.textContent = "Copied!";
    setTimeout(() => {
      btn.textContent = originalLabel;
    }, 2000);
  }
};

const shareMessage = product => "Check out " + (product.title || "this product") + (product.price ? " (" + product.price + ")" : "");
const paymentSelector = ".shopify-payment-button, [data-shopify=payment-button]";
const rowSelector = ".sn-product-row-2";
const buttonSelector = ".sn-product-share-btn";
const isMobileDevice = () => {
  const win = getWin();
  const nav = getNav();
  return (win?.innerWidth || 1024) <= 768 || /Mobi|Android/i.test(nav?.userAgent || "");
};

export function createProductFeatures(api) {
  const win = getWin();
  const doc = getDoc();
  const loc = getLoc();
  const nav = getNav();
  const oAnalytics = win?.ShopifyAnalytics;

  const trackShare = (platform, product, type = "product_share") => api.track({
    platform,
    link_type: type,
    product_id: product?.id,
    product_title: product?.title,
  });

  const checkIsProductPage = () => {
    const pageType = win.__sn_page_type || api.rootEl()?.dataset.pageType || oAnalytics?.meta?.page?.pageType;
    if (pageType === "product") return true;
    if (pageType && pageType !== "product") return false;
    return /\/products\/[^\/\?]+/.test(loc.pathname);
  };

  const getProductInfo = () => {
    const product = oAnalytics?.meta?.product || win.__sn_product || {};
    const meta = key => $attr(`meta[property*='${key}'], meta[name*='${key}']`, "content");
    const canonical = $attr("link[rel='canonical']", "href");
    const title = meta("og:title") || meta("title") || $attr(".product__title, h1.product-title, .product-title, h1") || product.title || doc.title || "";
    const image = meta("og:image") || meta("image") || $attr(".product__media img, .product-media img, .product-single__media img", "src");
    const priceMeta = meta("price:amount") || meta("price");
    const price = priceMeta ? "$" + priceMeta : $attr(".price-item--regular, .product__price, .price, .product-price");
    const variantInput = $attr("form [name=id]", "value") || product.variants?.[0]?.id || "";

    let baseUrl = canonical || (loc.origin + loc.pathname);
    if (!baseUrl.startsWith("http")) baseUrl = loc.href;

    let cleanUrl = baseUrl;
    try {
      const parsed = new URL(baseUrl, loc.href);
      if (variantInput && !parsed.searchParams.has("variant")) {
        parsed.searchParams.set("variant", variantInput);
      }
      cleanUrl = parsed.href;
    } catch {
      cleanUrl = loc.href;
    }

    return {
      id: (product.id || "") + "",
      title: title.trim(),
      url: cleanUrl,
      img: image,
      price: price ? price.trim() : "",
    };
  };

  const openShareModal = product => {
    let modal = $(".sn-share-modal");
    if (!modal) {
      modal = createEl("div");
      modal.className = "sn-share-modal";
      modal.setAttribute("role", "dialog");
      modal.setAttribute("aria-modal", "true");
      modal.setAttribute("aria-label", "Share this product");
      doc.body.append(modal);
    }
    const message = shareMessage(product);
    const url = enc(product.url);
    const encodedMsg = enc(message);
    const image = enc(product.img || "");
    const prefix = "sn-modal-";
    const selector = "." + prefix;

    const platformList = ["whatsapp", "facebook", "pinterest", "x", "telegram"];
    if (nav.share) platformList.push("native");

    const options = platformList.map(platform =>
      `<button class="${prefix}option" data-k="${platform}" aria-label="Share via ${platform}"><i class="${prefix}icon sn-ico-${platform}"></i><span>${platform === "native" ? "More" : (platform === "x" ? "X / Twitter" : platform.charAt(0).toUpperCase() + platform.slice(1))}</span></button>`
    ).join("");

    const div = '<div class="' + prefix;
    modal.innerHTML = `${div}backdrop"></div>${div}card">${div}header"><b>Share this product</b><button class="${prefix}close" aria-label="Close modal">×</button></div>${product.img || product.title ? `${div}product">${product.img ? `<img src="${product.img}" alt="${product.title}">` : ""}<div><b>${product.title}</b>${product.price ? `<small>${product.price}</small>` : ""}</div></div>` : ""}${div}grid">${options}</div>${div}copy-box"><input value="${product.url}" readonly aria-label="Product link"><button type="button">Copy</button></div>${div}feedback"></div></div>`;
    modal.style.display = "flex";

    let keyHandler;
    const close = () => {
      modal.style.display = "none";
      doc.removeEventListener("keydown", keyHandler);
    };
    doc.addEventListener("keydown", (keyHandler = event => {
      if (event.key === "Escape") close();
    }));

    const feedback = $(selector + "feedback", modal);
    modal.onclick = event => {
      if (event.target.matches(selector + "backdrop, " + selector + "close")) return close();
      const option = event.target.closest("[data-k]");
      if (option) {
        const platform = option.dataset.k;
        trackShare(platform, product);
        if ((platform === "native" || platform === "instagram") && nav.share) {
          return nav.share({ title: product.title, text: message, url: product.url }).catch(() => {});
        }
        if (platform === "instagram") {
          return copyText(product.url, feedback, "Copied!");
        }
        const target = platform === "whatsapp" ? `wa.me/?text=${encodedMsg}%20${url}`
          : platform === "facebook" ? `facebook.com/sharer/sharer.php?u=${url}`
          : platform === "x" ? `x.com/intent/tweet?url=${url}&text=${encodedMsg}`
          : platform === "telegram" ? `t.me/share/url?url=${url}&text=${encodedMsg}`
          : platform === "pinterest" ? `pinterest.com/pin/create/button/?url=${url}${image ? "&media=" + image : ""}&description=${encodedMsg}`
          : "";
        if (target) {
          open("https://" + target, "_blank", "noopener,noreferrer");
        }
      }
      const copyBtn = event.target.closest(selector + "copy-box button");
      if (copyBtn) {
        const input = $("input", modal);
        input?.select();
        trackShare("copy_link", product);
        copyText(product.url, copyBtn);
      }
    };
  };

  const initProductShare = settings => {
    let timer;
    const removeButton = () => {
      const row = $(rowSelector);
      if (row) {
        const buyNow = $(".accelerated-checkout-block, " + paymentSelector, row);
        if (buyNow && row.parentNode) {
          row.before(buyNow);
        }
        row.remove();
      }
      $(buttonSelector)?.remove();
    };
    const stopObserver = () => {
      clearTimeout(timer);
      if (win._snShareObs) {
        win._snShareObs.disconnect();
        win._snShareObs = null;
      }
    };

    const {
      product_share_button_text: label = "Share Product",
      product_share_button_style: style = "solid",
      product_share_button_background_color: background = "rgba(0, 81, 255, 1)",
      product_share_enabled: enabled,
    } = settings || {};

    if (!checkIsProductPage() || !enabled) {
      stopObserver();
      removeButton();
      return;
    }

    function render() {
      if (!api.settings()?.product_share_enabled || !checkIsProductPage()) {
        stopObserver();
        removeButton();
        return;
      }

      const submit = $("[name=add], .product-form__submit");
      const form = submit?.closest("form") || $("product-info form, product-form form, form[action*='/cart/add']");
      if (!form || form.closest(".card, .card-wrapper, .product-card")) return;

      const buttonsContainer = submit?.closest(".product-form-buttons, .product-form__buttons");
      const payment = $(paymentSelector, form) || $(paymentSelector);
      const accelBlock = payment?.closest(".accelerated-checkout-block") || $(".accelerated-checkout-block", form);
      const buyNowContainer = accelBlock || payment;

      let row = $(rowSelector, form) || (buttonsContainer?.parentElement && $(rowSelector, buttonsContainer.parentElement)) || $(rowSelector);
      let button = $(buttonSelector, row || form);

      const textClass = "sn-share-text-";
      if (button) {
        const full = $("." + textClass + "full", button);
        if (full && full.textContent !== label) full.textContent = label;
      } else {
        button = createEl("button");
        button.type = "button";
        button.setAttribute("aria-label", label);
        button.innerHTML = `<i class="sn-product-share-icon"></i><span class="${textClass}full">${label}</span><span class="${textClass}short">Share</span>`;
        button.onclick = event => {
          event.preventDefault();
          event.stopPropagation();
          const product = getProductInfo();
          if (isMobileDevice() && nav.share) {
            nav.share({ title: product.title, text: shareMessage(product), url: product.url })
              .then(() => trackShare("native", product))
              .catch(err => {
                if (err?.name !== "AbortError") openShareModal(product);
              });
          } else {
            openShareModal(product);
          }
        };
        button.className = `${buttonSelector.slice(1)} sn-btn-${style}`;

        let red = 17, green = 24, blue = 39, alpha = 1, parsed;
        if (background && background[0] === "#") {
          parsed = parseInt(background.slice(1), 16);
          if (background.length === 7) {
            red = (parsed >> 16) & 255;
            green = (parsed >> 8) & 255;
            blue = parsed & 255;
          } else if (background.length === 9) {
            red = (parsed >> 24) & 255;
            green = (parsed >> 16) & 255;
            blue = (parsed >> 8) & 255;
            alpha = (parsed & 255) / 255;
          }
        } else if (background && (parsed = background.match(/[\d.]+/g))) {
          red = +parsed[0];
          green = +parsed[1];
          blue = +parsed[2];
          alpha = +parsed[3] || 1;
        }
        const isDark = (alpha * (0.299 * red + 0.587 * green + 0.114 * blue) + 255 * (1 - alpha)) < 140;
        button.style.cssText = `--sn-share-bg:${background};--sn-share-border:${background};--sn-share-color:${background};--sn-share-text:${isDark ? "#ffffff" : "#111827"};--sn-share-hover-bg:${style === "solid" ? "transparent" : `rgba(${red},${green},${blue},0.08)`}`;
      }

      if (!row) {
        row = createEl("div");
        row.className = rowSelector.slice(1);
      }

      // Placement: Buy it now + Share Product side-by-side
      if (buyNowContainer) {
        row.classList.remove("sn-product-share-full");
        if (!row.contains(buyNowContainer)) {
          if (buyNowContainer.parentElement && !buyNowContainer.parentElement.contains(row)) {
            buyNowContainer.before(row);
          }
          row.prepend(buyNowContainer);
        }
        if (!row.contains(button)) {
          row.append(button);
        }
        const terms = $(".shopify-payment-terms", row) || $(".shopify-payment-terms", form);
        if (terms && row.nextElementSibling !== terms) row.after(terms);
      } else if (submit) {
        const target = buttonsContainer || submit;
        row.classList.add("sn-product-share-full");
        if (row.parentElement !== target.parentElement || row.previousElementSibling !== target) {
          target.after(row);
        }
        if (!row.contains(button)) row.append(button);
      } else if (form) {
        row.classList.add("sn-product-share-full");
        if (!form.contains(row)) form.append(row);
        if (!row.contains(button)) row.append(button);
      }
    }

    render();

    if (!win._snShareObs) {
      win._snShareObs = new MutationObserver(() => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          render();
          win.SocialNestWishlist?.init?.();
        }, 60);
      });
      win._snShareObs.observe(doc.body, { childList: true, subtree: true });
    }
  };

  doc.addEventListener("click", event => {
    const btn = event.target.closest(".socialnest-inline-share .sn-share-btn");
    if (!btn) return;
    if (api.isDesignMode() && !btn.matches(".sn-share-copy")) event.preventDefault();
    const platform = (btn.className.match(/sn-share-([\w-]+)/)?.[1] || "").replace("copy", "copy_link");
    if (platform) trackShare(platform, checkIsProductPage() ? getProductInfo() : null, "inline_share");
  }, true);

  return {
    isProductPage: checkIsProductPage,
    getProductInfo,
    openShareModal,
    initProductShare,
  };
}

export function initProductShare(context) {
  return createProductFeatures(context);
}
