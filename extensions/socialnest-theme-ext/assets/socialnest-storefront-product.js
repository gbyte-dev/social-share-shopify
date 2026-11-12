
export function createProductFeatures(api) {
  const t = document, e = window, a = navigator, n = location;
  const s = encodeURIComponent, o = e.ShopifyAnalytics;
  const i = (selector, root = t) => root?.querySelector(selector);
  const l = (selector, attr, root = t) => {
    const el = i(selector, root);
    return (el && (attr ? el.getAttribute(attr) : el.textContent)) || "";
  };
  const c = (tag = "div") => t.createElement(tag);
  const p = (value, button, label = button?.textContent || "Copy") => (
    a.clipboard?.writeText(value),
    button && ((button.textContent = "Copied!"), setTimeout(() => button.textContent = label, 2e3))
  );
  const u = (platform, product, type = "product_share") => api.track({
    platform, link_type: type, product_id: product?.id, product_title: product?.title
  });
  const m = product => "Check out " + product.title + (product.price ? " (" + product.price + ")" : "");
  const paymentSelector = ".shopify-payment-button,[data-shopify=payment-button]";
  const rowSelector = ".sn-product-row-2";
  const buttonSelector = ".sn-product-share-btn";
  const isMobile = () => 768 >= e.innerWidth || /Mobi|Android/i.test(a.userAgent);
  const isProductPage = () =>
    "product" == (e.__sn_page_type || api.rootEl()?.dataset.pageType || o?.meta?.page?.pageType) ||
    /\/products\//.test(n.pathname);
  const getProductInfo = () => {
    const product = o?.meta?.product || {};
    const meta = key => l(`meta[property*=${key}]`, "content");
    const title = meta("title") || l(".product__title,h1") || product.title || "";
    const image = meta("image") || l(".product__media img", "src");
    const priceMeta = meta("price");
    const price = priceMeta ? "$" + priceMeta : l(".price-item--regular,.product__price");
    const variant = l("form [name=id]", "value") || product.variants?.[0]?.id || "";
    const url = n.href + (variant && !n.search.includes("variant=")
      ? (n.search ? "&" : "?") + "variant=" + variant : "");
    return { id: (product.id || "") + "", title: title.trim(), url, img: image, price: price?.trim() || "" };
  };
  const openShareModal = product => {
    let modal = i(".sn-share-modal");
    if (!modal) {
      modal = c();
      modal.className = "sn-share-modal";
      modal.setAttribute("role", "dialog");
      t.body.append(modal);
    }
    const message = m(product), url = s(product.url), encodedMessage = s(message), image = s(product.img);
    const shareText = url + "&text=" + encodedMessage, prefix = "sn-modal-", selector = "." + prefix;
    const options = ("whatsapp,facebook,pinterest,telegram,x,instagram" + (a.share ? ",native" : ""))
      .split(",").map(platform =>
        `<button class="${prefix}option" data-k=${platform}><i class="${prefix}icon sn-ico-${platform}"></i><span>${"native" == platform ? "More" : platform}</span></button>`
      ).join("");
    const div = '<div class="' + prefix;
    modal.innerHTML = `${div}backdrop"></div>${div}card">${div}header"><b>Share</b><button class="${prefix}close">×</button></div>${product.img ? `${div}product"><img src="${product.img}"><div><b>${product.title}</b><small>${product.price}</small></div></div>` : ""}${div}grid">${options}</div>${div}copy-box"><input value="${product.url}" readonly><button>Copy</button></div>${div}feedback"></div></div>`;
    modal.style.display = "flex";
    let keyHandler;
    const close = () => (modal.style.display = "none", t.removeEventListener("keydown", keyHandler));
    t.addEventListener("keydown", (keyHandler = event => "Escape" == event.key && close()));
    const feedback = i(selector + "feedback", modal);
    modal.onclick = event => {
      if (event.target.matches(selector + "backdrop," + selector + "close")) return close();
      const option = event.target.closest("[data-k]");
      if (option) {
        const platform = option.dataset.k;
        if ((u(platform, product), /instagram|native/.test(platform) && a.share))
          return a.share({ title: product.title, text: message, url: product.url }).catch(() => {});
        if ("instagram" == platform) return p(product.url, feedback, "Copied!");
        const target = "whatsapp" == platform ? `wa.me/?text=${encodedMessage} ${url}`
          : "facebook" == platform ? "facebook.com/sharer.php?u=" + url
          : "x" == platform ? "x.com/intent/tweet?url=" + shareText
          : "telegram" == platform ? "t.me/share/url?url=" + shareText
          : "pinterest" == platform ? `pinterest.com/pin/create/button/?url=${url}${image ? "&media=" + image : ""}&description=${encodedMessage}` : "";
        if (target) open("https://" + target, "_blank", "noopener");
      }
      const copy = event.target.closest(selector + "copy-box button");
      copy && (i("input", modal)?.select(), u("copy_link", product), p(product.url, copy));
    };
  };
  const initProductShare = settings => {
    let timer;
    const removeButton = () => {
      const row = i(rowSelector), payment = i(paymentSelector, row);
      payment && row.before(payment);
      row?.remove();
      i(buttonSelector)?.remove();
    };
    const stopObserver = () => {
      clearTimeout(timer);
      if (e._snShareObs) (e._snShareObs.disconnect(), e._snShareObs = 0);
    };
    const { product_share_button_text: label = "Share Product",
      product_share_button_style: style = "outline",
      product_share_button_background_color: background = "rgba(17, 24, 39, 1)",
      product_share_enabled: enabled } = settings || {};
    if (!isProductPage() || !enabled) return (stopObserver(), removeButton());
    function render() {
      if (!api.settings()?.product_share_enabled || !isProductPage()) return (stopObserver(), removeButton());
      const submit = i("[name=add],.product-form__submit");
      const form = submit?.closest("form") || i("product-info form,form[action*='/cart/add']");
      if (!form || form.closest(".card,.card-wrapper")) return;
      let row = i(rowSelector), button = i(buttonSelector);
      const payment = i(paymentSelector, form) || i(paymentSelector);
      const textClass = "sn-share-text-";
      if (button) {
        const full = i("." + textClass + "full", button);
        if (full) full.textContent = label;
      } else {
        button = c("button");
        button.type = "button";
        button.innerHTML = `<i class="sn-product-share-icon"></i><span class="${textClass}full">${label}</span><span class="${textClass}short">Share</span>`;
        button.onclick = event => {
          event.preventDefault(); event.stopPropagation();
          const product = getProductInfo();
          isMobile() && a.share
            ? a.share({ title: product.title, text: m(product), url: product.url })
              .then(() => u("native", product))
              .catch(error => "AbortError" !== error?.name && openShareModal(product))
            : openShareModal(product);
        };
        button.className = `${buttonSelector.slice(1)} sn-btn-${style}`;
        let red = 17, green = 24, blue = 39, alpha = 1, parsed;
        if ("#" == background[0])
          (red = ((parsed = parseInt(background.slice(1), 16)) >> 16) & 255,
           green = (parsed >> 8) & 255, blue = 255 & parsed);
        else if ((parsed = background.match(/[\d.]+/g)))
          (red = +parsed[0], green = +parsed[1], blue = +parsed[2], alpha = +parsed[3] || 1);
        button.style.cssText = `--sn-share-bg:${background};--sn-share-border:${background};--sn-share-color:${background};--sn-share-text:${140 > alpha * (.299 * red + .587 * green + .114 * blue) + 255 * (1 - alpha) ? "#fff" : "#111"};--sn-share-hover-bg:${"solid" === style ? "transparent" : `rgba(${red},${green},${blue},.08)`}`;
        if (payment || submit) {
          row || ((row = c()), row.className = rowSelector.slice(1), payment ? payment.before(row) : submit.after(row));
          payment && !row.contains(payment) && row.prepend(payment);
          row.contains(button) || row.append(button);
          const terms = payment && i(".shopify-payment-terms", payment);
          terms && row.after(terms);
        } else form.contains(button) || form.append(button);
      }
    }
    render();
    if (!e._snShareObs)
      
        e._snShareObs = new MutationObserver(() => {
        clearTimeout(timer);

        timer = setTimeout(() => {
        render();
        }, 60);
    }), e._snShareObs.observe(t.body, { childList: 1, subtree: 1 }));
  };
  t.addEventListener("click", event => {
    const button = event.target.closest(".socialnest-inline-share .sn-share-btn");
    if (!button) return;
    if (api.isDesignMode() && !button.matches(".sn-share-copy")) event.preventDefault();
    const platform = (button.className.match(/sn-share-([\w-]+)/)?.[1] || "").replace("copy", "copy_link");
    platform && u(platform, isProductPage() ? getProductInfo() : null, "inline_share");
  }, 1);
  return { isProductPage, getProductInfo, openShareModal, initProductShare };
}
