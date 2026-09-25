// Промо: UMD с CHECKOUT_PROMO_SCRIPT_URL (Pay static), данные из __buttonBootstrap (server-side, без fetch).
// CHECKOUT_PROMO_SCRIPT_URL и __buttonBootstrap объявляются в handlers.php (inline перед script.js).
// Нет merchant id / bootstrap / скрипта — блок #yastore-checkout-promo пустой, кнопка работает.
function loadCheckoutPromoScript(callback) {
    if (typeof window.YandexPayCheckoutPromo !== "undefined") {
        callback();
        return;
    }
    if (!CHECKOUT_PROMO_SCRIPT_URL) {
        debugLog("warn", "checkout-promo: empty CHECKOUT_PROMO_SCRIPT_URL");
        return;
    }
    var existing = document.querySelector('script[data-yakit-checkout-promo="1"]');
    if (existing) {
        existing.addEventListener("load", callback);
        return;
    }
    var script = document.createElement("script");
    script.src = CHECKOUT_PROMO_SCRIPT_URL;
    script.async = true;
    script.setAttribute("data-yakit-checkout-promo", "1");
    script.onload = callback;
    script.onerror = function () {
        debugLog("warn", "checkout-promo script failed to load", { url: CHECKOUT_PROMO_SCRIPT_URL });
    };
    document.head.appendChild(script);
}

function mountCheckoutPromo() {
    if (typeof window.YandexPayCheckoutPromo === "undefined") {
        debugLog("warn", "checkout-promo: YandexPayCheckoutPromo is not loaded");
        return;
    }
    if (!__buttonBootstrap) {
        debugLog("warn", "checkout-promo: __buttonBootstrap is empty");
        return;
    }
    var promoEl = document.getElementById("yastore-checkout-promo");
    if (!promoEl) {
        debugLog("warn", "checkout-promo: #yastore-checkout-promo not found");
        return;
    }
    window.YandexPayCheckoutPromo.mount(promoEl, __buttonBootstrap, {
        lang: "ru",
        buttonSize: "sm",
    });
    debugLog("log", "Checkout promo mounted");
}

function scheduleCheckoutPromo() {
    if (!__buttonBootstrap) {
        debugLog("warn", "checkout-promo: skip schedule, __buttonBootstrap is empty");
        return;
    }
    loadCheckoutPromoScript(mountCheckoutPromo);
}

// Безопасное чтение inline-глобала (var на верхнем уровне -> свойство window).
function readGlobal(name, fallback) {
    try {
        if (typeof window !== "undefined" && typeof window[name] !== "undefined" && window[name] !== null) {
            return window[name];
        }
    } catch (e) {
        // ignore
    }
    return fallback;
}

function getContextConfig(contextName) {
    if (contextName === "basket") {
        return {
            enabled: typeof YAKIT_BASKET_BUTTON_ENABLED !== "undefined" && YAKIT_BASKET_BUTTON_ENABLED === "Y",
            id: "yastore-checkout-button",
            action: "basketItems",
            anchor: typeof BUTTON_ANCHOR !== "undefined" && BUTTON_ANCHOR && BUTTON_ANCHOR.trim() ? BUTTON_ANCHOR : ".basket-checkout-section-inner",
            insertAfter: typeof YAKIT_BUTTON_INSERT_AFTER !== "undefined" ? YAKIT_BUTTON_INSERT_AFTER : "",
            hideOriginalButton: typeof YAKIT_HIDE_ORIGINAL_BASKET_BUTTON !== "undefined" && YAKIT_HIDE_ORIGINAL_BASKET_BUTTON === "Y",
            originalButtonSelector: typeof YAKIT_ORIGINAL_BASKET_BUTTON_SELECTOR !== "undefined" ? YAKIT_ORIGINAL_BASKET_BUTTON_SELECTOR : ".basket-btn-checkout",
            sdkUrl: readGlobal("YAKIT_SDK_URL", ""),
            merchantId: readGlobal("YAKIT_MERCHANT_ID", ""),
            totalAmount: readGlobal("YAKIT_TOTAL_AMOUNT", ""),
            theme: readGlobal("YAKIT_BUTTON_THEME", "gradient"),
            color: readGlobal("YAKIT_BUTTON_COLOR", ""),
            width: readGlobal("YAKIT_BUTTON_WIDTH", "max"),
            height: readGlobal("YAKIT_BUTTON_HEIGHT", "44"),
            radius: readGlobal("YAKIT_BUTTON_RADIUS", "12"),
            caption: readGlobal("YAKIT_BUTTON_CAPTION", "checkout"),
            wrapClass: readGlobal("YAKIT_BUTTON_WRAP_CLASS", ""),
        };
    }

    if (contextName === "product") {
        return {
            enabled: typeof YAKIT_PRODUCT_BUTTON_ENABLED !== "undefined" && YAKIT_PRODUCT_BUTTON_ENABLED === "Y",
            id: "yastore-checkout-product-button",
            styleId: "yastore-checkout-product-button-styles",
            action: "productToCheckout",
            text: typeof YAKIT_PRODUCT_BUTTON_TEXT !== "undefined" && YAKIT_PRODUCT_BUTTON_TEXT ? YAKIT_PRODUCT_BUTTON_TEXT : "Быстрое оформление",
            anchor: typeof YAKIT_PRODUCT_BUTTON_ANCHOR !== "undefined" ? YAKIT_PRODUCT_BUTTON_ANCHOR : "",
            insertAfter: typeof YAKIT_PRODUCT_BUTTON_INSERT_AFTER !== "undefined" ? YAKIT_PRODUCT_BUTTON_INSERT_AFTER : "",
            css: typeof YAKIT_PRODUCT_BUTTON_CSS !== "undefined" ? YAKIT_PRODUCT_BUTTON_CSS : "",
            productIdSelector: typeof YAKIT_PRODUCT_ID_SELECTOR !== "undefined" ? YAKIT_PRODUCT_ID_SELECTOR : "",
        };
    }

    return null;
}

function isDebugEnabled() {
    try {
        if (typeof window === "undefined") {
            return false;
        }
        if (window.YAKIT_DEBUG === true) {
            return true;
        }
        if (window.location && /(?:\?|&)yakitDebug=1(?:&|$)/.test(window.location.search || "")) {
            return true;
        }
        if (window.localStorage && window.localStorage.getItem("yakitDebug") === "1") {
            return true;
        }
    } catch (e) {
        // ignore
    }
    return false;
}

var YAKIT_DEBUG_ENABLED = isDebugEnabled();
var YAKIT_ACTION_PREFIXES = [
    "yandex:market.checkout.Checkout.",
    "yandex:market.Checkout.",
    "yandex.market:checkout.Checkout.",
    "yastore:checkout.Checkout.",
];

function debugLog(level, message, data) {
    if (!YAKIT_DEBUG_ENABLED || typeof console === "undefined") {
        return;
    }
    var prefix = "[YAKIT DEBUG] " + message;
    if (level === "warn" && console.warn) {
        console.warn(prefix, data || "");
    } else if (level === "error" && console.error) {
        console.error(prefix, data || "");
    } else if (console.log) {
        console.log(prefix, data || "");
    }
}

function isMissingControllerConfigError(response) {
    if (!response || !response.errors || !response.errors.length) {
        return false;
    }
    for (var i = 0; i < response.errors.length; i++) {
        var error = response.errors[i] || {};
        var code = error.code;
        var message = String(error.message || "");
        if (code === 2210204 || message.indexOf("Could not find configuration 'controllers'") !== -1) {
            return true;
        }
    }
    return false;
}

function runCheckoutAction(actionName, parameters) {
    var prefixIndex = 0;

    function attempt() {
        var fullActionName = YAKIT_ACTION_PREFIXES[prefixIndex] + actionName;
        debugLog("log", "Run action", { action: fullActionName, parameters: parameters, prefixIndex: prefixIndex });
        return BX.ajax.runAction(fullActionName, { getParameters: parameters }).then(
            function (response) {
                debugLog("log", "Action request success", { action: fullActionName, status: response && response.status });
                return response;
            },
            function (response) {
                var canRetry = prefixIndex + 1 < YAKIT_ACTION_PREFIXES.length && isMissingControllerConfigError(response);
                debugLog(canRetry ? "warn" : "error", "Action request failed", {
                    action: fullActionName,
                    retryWithLegacyPrefix: canRetry,
                    response: response,
                });
                if (canRetry) {
                    prefixIndex++;
                    return attempt();
                }
                return Promise.reject(response);
            }
        );
    }

    return attempt();
}

function normalizeCss(css, buttonId) {
    if (!css) {
        debugLog("log", "CSS is empty, skip style injection", { buttonId: buttonId });
        return "";
    }
    var normalized = css.replace(/\\n/g, "\n").replace(/\\r/g, "\r").replace(/\\\\/g, "\\").trim();
    if (normalized.indexOf("{") === -1) {
        normalized = "#" + buttonId + " { " + normalized + " }";
    }
    return normalized;
}

function injectStyles(context) {
    var css = normalizeCss(context.css, context.id);
    if (!css) {
        return;
    }
    var styleNode = document.getElementById(context.styleId);
    if (styleNode) {
        debugLog("log", "Style already exists", { styleId: context.styleId });
        return;
    }
    styleNode = document.createElement("style");
    styleNode.id = context.styleId;
    styleNode.textContent = css;
    document.head.appendChild(styleNode);
    debugLog("log", "Style injected", { styleId: context.styleId, buttonId: context.id });
}

function buttonExists(context) {
    return document.getElementById(context.id) !== null;
}

function splitSelectors(rawSelectors) {
    if (!rawSelectors || typeof rawSelectors !== "string") {
        return [];
    }
    return rawSelectors
        .split(",")
        .map(function (selector) { return selector.trim(); })
        .filter(function (selector) { return !!selector; });
}

function isElementVisible(element) {
    if (!element) {
        return false;
    }
    if (element.getClientRects && element.getClientRects().length === 0) {
        return false;
    }
    if (typeof window !== "undefined" && window.getComputedStyle) {
        var styles = window.getComputedStyle(element);
        if (!styles || styles.display === "none" || styles.visibility === "hidden" || styles.opacity === "0") {
            return false;
        }
    }
    return true;
}

function findVisibleElement(selector) {
    if (!selector || !selector.trim()) {
        return null;
    }
    var elements = document.querySelectorAll(selector);
    if (!elements || elements.length === 0) {
        debugLog("warn", "No elements found by selector", { selector: selector });
        return null;
    }
    for (var i = 0; i < elements.length; i++) {
        if (isElementVisible(elements[i])) {
            debugLog("log", "Visible element found by selector", { selector: selector, index: i, total: elements.length });
            return elements[i];
        }
    }
    debugLog("warn", "No visible elements found, fallback to first match", { selector: selector, total: elements.length });
    return elements[0];
}

function firstNumber(value) {
    if (value === undefined || value === null) {
        return null;
    }
    var raw = String(value).trim();
    if (!raw) {
        return null;
    }
    var match = raw.match(/\d+/);
    if (!match) {
        return null;
    }
    var intValue = parseInt(match[0], 10);
    return isNaN(intValue) ? null : intValue;
}

function readNumericValueFromElement(element) {
    if (!element) {
        return null;
    }
    var candidates = [];
    if (typeof element.value !== "undefined") {
        candidates.push(element.value);
    }
    if (element.dataset) {
        candidates.push(element.dataset.offerId);
        candidates.push(element.dataset.productId);
        candidates.push(element.dataset.itemId);
        candidates.push(element.dataset.id);
        candidates.push(element.dataset.basketItemId);
        candidates.push(element.dataset.entityId);
    }
    candidates.push(element.getAttribute("data-product-id"));
    candidates.push(element.getAttribute("data-offer-id"));
    candidates.push(element.getAttribute("data-item-id"));
    candidates.push(element.getAttribute("data-id"));
    candidates.push(element.getAttribute("value"));
    candidates.push(element.id);

    for (var i = 0; i < candidates.length; i++) {
        var parsed = firstNumber(candidates[i]);
        if (parsed !== null && parsed > 0) {
            return parsed;
        }
    }

    var attrs = element.attributes || [];
    for (var j = 0; j < attrs.length; j++) {
        var attr = attrs[j];
        if (!attr || !attr.name) {
            continue;
        }
        var attrName = String(attr.name).toLowerCase();
        if ((attrName.indexOf("product") !== -1 || attrName.indexOf("offer") !== -1 || attrName.indexOf("item") !== -1) && attrName.indexOf("id") !== -1) {
            var attrParsed = firstNumber(attr.value);
            if (attrParsed !== null && attrParsed > 0) {
                return attrParsed;
            }
        }
    }

    return null;
}

function defaultProductIdSelectors() {
    return [
        "[data-product-id]",
        "[data-offer-id]",
        "input[name='PRODUCT_ID']",
        "input[name='PRODUCT_ID_INPUT']",
        "input[name='PRODUCT_ID[]']",
        "input[name='ID']",
        "input[name='OFFER_ID']",
        "[name='item_id']",
        "[data-basket-item-id]",
    ];
}

function pushUnique(list, value) {
    if (!value) {
        return;
    }
    if (list.indexOf(value) === -1) {
        list.push(value);
    }
}

function collectProductIdSelectors(context) {
    var result = [];
    var customSelectors = splitSelectors(context.productIdSelector);
    for (var i = 0; i < customSelectors.length; i++) {
        pushUnique(result, customSelectors[i]);
    }
    var fallbackSelectors = defaultProductIdSelectors();
    for (var j = 0; j < fallbackSelectors.length; j++) {
        pushUnique(result, fallbackSelectors[j]);
    }
    return result;
}

function resolveProductIdBySelectors(selectors) {
    for (var i = 0; i < selectors.length; i++) {
        var selector = selectors[i];
        var elements = document.querySelectorAll(selector);
        if (!elements || elements.length === 0) {
            continue;
        }

        for (var j = 0; j < elements.length; j++) {
            var element = elements[j];
            if (!isElementVisible(element)) {
                continue;
            }
            var productId = readNumericValueFromElement(element);
            if (productId !== null && productId > 0) {
                debugLog("log", "Product id resolved", { selector: selector, productId: productId, elementIndex: j });
                return productId;
            }
        }

        for (var k = 0; k < elements.length; k++) {
            var fallbackElement = elements[k];
            var fallbackProductId = readNumericValueFromElement(fallbackElement);
            if (fallbackProductId !== null && fallbackProductId > 0) {
                debugLog("log", "Product id resolved from hidden element", { selector: selector, productId: fallbackProductId, elementIndex: k });
                return fallbackProductId;
            }
        }
    }

    return null;
}

function resolveProductIdFromMeta() {
    var metaSelectors = [
        "meta[itemprop='productID']",
        "meta[itemprop='productId']",
        "meta[itemprop='id']",
    ];
    for (var i = 0; i < metaSelectors.length; i++) {
        var meta = document.querySelector(metaSelectors[i]);
        if (!meta) {
            continue;
        }
        var metaId = firstNumber(meta.getAttribute("content"));
        if (metaId !== null && metaId > 0) {
            debugLog("log", "Product id resolved from meta", { selector: metaSelectors[i], productId: metaId });
            return metaId;
        }
    }
    return null;
}

function resolveProductIdFromScripts() {
    var scriptNodes = document.querySelectorAll("script");
    if (!scriptNodes || scriptNodes.length === 0) {
        return null;
    }

    var preferredPatterns = [
        /offerId\s*:\s*(\d+)/gi,
        /["']offerId["']\s*:\s*(\d+)/gi,
        /["']OFFER_ID["']\s*:\s*(\d+)/gi,
    ];
    var fallbackPatterns = [
        /productId\s*:\s*(\d+)/gi,
        /["']productId["']\s*:\s*(\d+)/gi,
        /currentProductId\s*:\s*(\d+)/gi,
        /["']PRODUCT_ID["']\s*:\s*(\d+)/gi,
    ];

    for (var i = 0; i < scriptNodes.length; i++) {
        var content = scriptNodes[i].textContent || "";
        if (!content) {
            continue;
        }

        for (var p = 0; p < preferredPatterns.length; p++) {
            preferredPatterns[p].lastIndex = 0;
            var preferredMatch = preferredPatterns[p].exec(content);
            if (!preferredMatch || !preferredMatch[1]) {
                continue;
            }
            var preferredParsed = firstNumber(preferredMatch[1]);
            if (preferredParsed !== null && preferredParsed > 0) {
                debugLog("log", "Product id resolved from script (offer preferred)", { productId: preferredParsed, scriptIndex: i, patternIndex: p });
                return preferredParsed;
            }
        }

        for (var f = 0; f < fallbackPatterns.length; f++) {
            fallbackPatterns[f].lastIndex = 0;
            var match = fallbackPatterns[f].exec(content);
            if (!match || !match[1]) {
                continue;
            }
            var parsed = firstNumber(match[1]);
            if (parsed !== null && parsed > 0) {
                debugLog("log", "Product id resolved from script", { productId: parsed, scriptIndex: i, patternIndex: f });
                return parsed;
            }
        }
    }

    return null;
}

function resolveProductPayload(context) {
    var selectors = splitSelectors(context.productIdSelector);
    var allSelectors = collectProductIdSelectors(context);
    debugLog("log", "Resolve product payload", { selectors: selectors, fallbackSelectors: allSelectors });
    var productId = resolveProductIdBySelectors(allSelectors);
    if (!productId || productId <= 0) {
        productId = resolveProductIdFromScripts();
    }
    if (!productId || productId <= 0) {
        productId = resolveProductIdFromMeta();
    }

    if (!productId || productId <= 0) {
        debugLog("warn", "Failed to resolve product id", { selectors: selectors, fallbackSelectors: allSelectors });
        return null;
    }

    return {
        productId: productId,
        quantity: 1,
    };
}

function createButton(contextName, context) {
    var button = document.createElement("a");
    button.setAttribute("id", context.id);
    button.setAttribute("href", "javascript:void(0)");
    button.setAttribute("role", "button");
    button.classList.add("btn-ya-checkout");
    button.classList.add("btn-ya-checkout-" + contextName);

    var mainRow = document.createElement("div");
    mainRow.classList.add("yastore-checkout-button__main");

    var buttonText = document.createElement("div");
    buttonText.innerText = context.text;

    mainRow.append(buttonText);
    button.append(mainRow);

    if (contextName === "basket") {
        var promo = document.createElement("div");
        promo.setAttribute("id", "yastore-checkout-promo");
        promo.classList.add("yastore-checkout-promo");
        button.append(promo);
    }

    button.onclick = function () {
        debugLog("log", "Button clicked", { context: contextName, action: context.action });
        var clientID = getMetricaClientID();
        var parameters = { metricaClientId: clientID };

        if (contextName === "product") {
            var payload = resolveProductPayload(context);
            if (!payload) {
                debugLog("error", "Cannot resolve product id on product page", { selector: context.productIdSelector });
                return;
            }
            parameters.productId = payload.productId;
            parameters.quantity = payload.quantity;
        }

        button.disabled = true;
        runCheckoutAction(context.action, parameters).then(
                function (response) {
                    button.disabled = false;
                    if (response.status === "success" && response.data && response.data.status === "success") {
                        debugLog("log", "Action success, redirect", { url: response.data.url });
                        window.location.href = response.data.url + buildMetricExtraParams(clientID);
                    } else if (response.status === "success" && response.data) {
                        debugLog("warn", "Action returned non-success payload", response.data);
                    }
                },
                function (response) {
                    button.disabled = false;
                    debugLog("error", "Action request failed", response);
                }
            );
    };

    debugLog("log", "Button created", { context: contextName, id: context.id });
    return button;
}

function resolveContainer(context) {
    var container = null;
    var insertAfter = context.insertAfter && context.insertAfter.trim ? context.insertAfter.trim() : "";
    if (insertAfter) {
        var insertAfterEl = findVisibleElement(insertAfter);
        if (insertAfterEl && insertAfterEl.parentNode) {
            container = insertAfterEl.parentNode;
            debugLog("log", "Container resolved by insertAfter parent", { insertAfter: insertAfter });
        }
    }
    if (!container && context.anchor && context.anchor.trim()) {
        container = findVisibleElement(context.anchor);
        if (container) {
            debugLog("log", "Container resolved by anchor", { anchor: context.anchor });
        }
    }
    if (!container) {
        debugLog("warn", "Container not found", { anchor: context.anchor, insertAfter: insertAfter });
    }
    return container;
}

function hideOriginalButton(contextName, context) {
    if (contextName !== "basket" || !context.hideOriginalButton) {
        return;
    }
    var selector = context.originalButtonSelector && context.originalButtonSelector.trim
        ? context.originalButtonSelector.trim()
        : "";
    if (!selector) {
        debugLog("warn", "Original button hide enabled but selector is empty");
        return;
    }
    var elements = document.querySelectorAll(selector);
    if (!elements || elements.length === 0) {
        debugLog("warn", "Original button not found for hide", { selector: selector });
        return;
    }
    for (var i = 0; i < elements.length; i++) {
        elements[i].style.display = "none";
    }
    debugLog("log", "Original button hidden", { selector: selector, count: elements.length });
}

function placeButton(contextName, context) {
    if (buttonExists(context)) {
        debugLog("log", "Button already exists, skip insertion", { context: contextName, id: context.id });
        return;
    }

    var container = resolveContainer(context);
    if (!container) {
        return;
    }

    var button = createButton(contextName, context);
    var insertAfter = context.insertAfter && context.insertAfter.trim ? context.insertAfter.trim() : "";
    if (insertAfter) {
        var sibling = findVisibleElement(insertAfter);
        if (sibling && sibling.parentNode) {
            sibling.parentNode.insertBefore(button, sibling.nextSibling);
            debugLog("log", "Button inserted after sibling", { context: contextName, insertAfter: insertAfter });
            if (contextName === "basket") {
                scheduleCheckoutPromo();
            }
            return;
        }
    }
    container.appendChild(button);
    debugLog("log", "Button appended to container", { context: contextName, anchor: context.anchor });
    if (contextName === "basket") {
        scheduleCheckoutPromo();
    }
}

// Хендл смонтированной SDK-кнопки корзины (для unmount при перерисовке корзины).
var sdkBasketHandle = null;

// Грузит IIFE-бандл checkout-button-sdk (глобал YaCheckout) один раз.
function ensureSdkLoaded(url, cb) {
    if (typeof window.YaCheckout !== "undefined") {
        cb();
        return;
    }
    if (!url) {
        debugLog("warn", "sdk: empty YAKIT_SDK_URL");
        return;
    }
    var existing = document.querySelector('script[data-ya-checkout-sdk="1"]');
    if (existing) {
        existing.addEventListener("load", cb);
        return;
    }
    var script = document.createElement("script");
    script.src = url;
    script.async = true;
    script.setAttribute("data-ya-checkout-sdk", "1");
    script.onload = cb;
    script.onerror = function () {
        debugLog("warn", "sdk script failed to load", { url: url });
    };
    document.head.appendChild(script);
}

function placeBasketSlot(context, slot) {
    var insertAfter = context.insertAfter && context.insertAfter.trim ? context.insertAfter.trim() : "";
    if (insertAfter) {
        var sibling = findVisibleElement(insertAfter);
        if (sibling && sibling.parentNode) {
            sibling.parentNode.insertBefore(slot, sibling.nextSibling);
            return true;
        }
    }
    var container = resolveContainer(context);
    if (!container) {
        return false;
    }
    container.appendChild(slot);
    return true;
}

function resolveSdkWidth(raw) {
    if (raw === "max" || raw === "auto") {
        return raw;
    }
    var n = parseInt(raw, 10);
    return isNaN(n) ? "max" : n;
}

function buildSdkMountOptions(context) {
    var options = {
        getCart: function () {
            return runCheckoutAction(context.action, {}).then(function (response) {
                var data = response && response.data ? response.data : {};
                return { items: data.items || [] };
            });
        },
        theme: context.theme,
        caption: context.caption,
        width: resolveSdkWidth(context.width),
        onError: function (error) {
            debugLog("warn", "sdk button error", error);
        },
    };
    if (context.color) {
        options.customColor = context.color;
    }
    var height = parseInt(context.height, 10);
    if (!isNaN(height)) {
        options.height = height;
    }
    var radius = parseInt(context.radius, 10);
    if (!isNaN(radius)) {
        options.borderRadius = radius;
    }
    if (context.totalAmount) {
        options.totalAmount = context.totalAmount;
    }
    return options;
}

// Идемпотентный маунт SDK-кнопки корзины: держит один слот, перемонтирует при
// вырывании слота из DOM (перерисовка корзины аяксом).
function mountSdkBasketButton(context) {
    var existingSlot = document.querySelector("[data-yakit-checkout-slot]");

    // Слот жив и кнопка смонтирована — делать нечего.
    if (existingSlot && sdkBasketHandle) {
        return;
    }
    // Слот исчез из DOM, а хендл остался — корзина перерисовалась: снимаем старое.
    if (sdkBasketHandle && !existingSlot) {
        try {
            sdkBasketHandle.unmount();
        } catch (e) {
            // ignore
        }
        sdkBasketHandle = null;
    }
    // Слот уже стоит, но маунт ещё идёт (async-загрузка бандла) — ждём следующего тика.
    if (existingSlot) {
        return;
    }

    var slot = document.createElement("div");
    slot.setAttribute("data-yakit-checkout-slot", "1");

    if (context.wrapClass) {
        slot.className = context.wrapClass;
    } else {
        // аналогично старой кнопке, но без отступов - отступы на стороне мерча
        slot.style.width = "100%";
        slot.style.boxSizing = "border-box";
    }
    if (!placeBasketSlot(context, slot)) {
        debugLog("warn", "sdk: basket slot container not found", { anchor: context.anchor });
        return;
    }

    ensureSdkLoaded(context.sdkUrl, function () {
        if (typeof window.YaCheckout === "undefined") {
            debugLog("warn", "sdk: YaCheckout is not available after load");
            return;
        }
        // Пока грузился бандл, слот мог быть удалён либо уже смонтирован другим тиком.
        if (!slot.parentNode || sdkBasketHandle) {
            return;
        }
        try {
            var initOptions = { platform: "bitrix" };
            if (context.merchantId) {
                initOptions.merchantId = context.merchantId;
            }
            var session = window.YaCheckout.init(initOptions);
            sdkBasketHandle = session.mountButton(slot, buildSdkMountOptions(context));
            // id вешаем на саму кнопку (не на обёртку): чтобы внешние интеграции,
            // кликающие #yastore-checkout-button программно, попадали в кнопку.
            var mountedButton = slot.querySelector(".ya-checkout-button");
            if (mountedButton) {
                mountedButton.id = context.id;
            }
            debugLog("log", "sdk basket button mounted", { id: context.id });
        } catch (e) {
            debugLog("error", "sdk mount failed", e);
        }
    });
}

function processContext(contextName) {
    var context = getContextConfig(contextName);
    if (!context || !context.enabled) {
        debugLog("log", "Context disabled or missing", { context: contextName, config: context || null });
        return;
    }
    debugLog("log", "Process context", { context: contextName, config: context });

    // Корзину монтирует SDK; product обрабатывается старым путём ниже.
    if (contextName === "basket") {
        hideOriginalButton(contextName, context);
        mountSdkBasketButton(context);
        return;
    }

    injectStyles(context);
    hideOriginalButton(contextName, context);
    placeButton(contextName, context);
}

function onReady(callback) {
    if (typeof BX !== "undefined" && BX && typeof BX.ready === "function") {
        debugLog("log", "Init via BX.ready");
        BX.ready(callback);
        return;
    }
    if (document.readyState === "loading") {
        debugLog("log", "Init via DOMContentLoaded listener");
        document.addEventListener("DOMContentLoaded", callback);
    } else {
        debugLog("log", "Init immediately (document already ready)");
        callback();
    }
}

onReady(function () {
    debugLog("log", "YAKIT script started", {
        debug: YAKIT_DEBUG_ENABLED,
        contexts: {
            basket: getContextConfig("basket"),
            product: getContextConfig("product"),
        },
    });
    processContext("basket");
    processContext("product");

    var observer = new MutationObserver(function () {
        debugLog("log", "MutationObserver tick");
        processContext("basket");
        processContext("product");
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    var retryCount = 0;
    var retryTimer = setInterval(function () {
        debugLog("log", "Retry tick", { retryCount: retryCount });
        processContext("basket");
        processContext("product");
        retryCount++;
        var basketContext = getContextConfig("basket");
        var productContext = getContextConfig("product");
        var basketDone = !basketContext || !basketContext.enabled || buttonExists(basketContext);
        var productDone = !productContext || !productContext.enabled || buttonExists(productContext);
        if (retryCount >= 20 || (basketDone && productDone)) {
            clearInterval(retryTimer);
        }
    }, 500);
});

function findYandexMetrikaCounters() {
    var counters = [];
    for (var key in window) {
        if (/^yaCounter\d+$/.test(key)) {
            var id = parseInt(key.replace("yaCounter", ""), 10);
            if (!isNaN(id)) counters.push(id);
        }
    }
    return counters;
}

function findYandexMetrikaInScripts() {
    var ids = {};
    var scripts = document.querySelectorAll("script");
    for (var i = 0; i < scripts.length; i++) {
        var script = scripts[i];
        var html = script.innerHTML || script.outerHTML;
        var matches = html.match(/id\s*[:=]\s*(\d{6,})/g);
        if (matches) {
            for (var j = 0; j < matches.length; j++) {
                var m = matches[j].match(/(\d{6,})/);
                if (m) ids[m[1]] = true;
            }
        }
    }
    return Object.keys(ids).map(function (id) { return parseInt(id, 10); });
}

function findYandexMetrikaCounterID() {
    var counterIDs = findYandexMetrikaCounters();
    if (!counterIDs || counterIDs.length === 0) {
        counterIDs = findYandexMetrikaInScripts();
    }
    if (counterIDs && counterIDs.length > 0) {
        return counterIDs[0];
    }
    return null;
}

function generateClientID() {
    var getRandom = function (min, max) {
        return Math.floor(Math.random() * (max - min)) + min;
    };
    var generateNewUid = function () {
        return [Math.round(Date.now() / 1000), getRandom(1000000, 999999999)].join("");
    };
    return generateNewUid();
}

function getMetricaClientID() {
    var counterID = findYandexMetrikaCounterID();
    if (!counterID) {
        return generateClientID();
    }
    var yaID;
    try {
        if (typeof ym === "function") {
            ym(counterID, "getClientID", function (clientID) {
                yaID = clientID;
            });
        }
    } catch (e) {
        // ignore
    }
    if (!yaID) {
        return generateClientID();
    }
    return yaID;
}

function buildMetricExtraParams(clientID) {
    var domain = window.location.hostname;
    var result = "&from=button&src=" + encodeURIComponent(domain);
    if (clientID) {
        result += "&metricClientId=" + encodeURIComponent(clientID);
    }
    return result;
}
