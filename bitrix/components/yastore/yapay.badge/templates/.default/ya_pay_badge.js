(function (window) {
    'use strict';
    if (window.YaPayBadge) return;
    window.YaPayBadge = function (arParams, arConfig) {
        this.price = 0;
        this.params = {};
        this.config = {};
        if (typeof arParams === 'object') {
            this.params = arParams;
        }
        if (typeof arConfig === 'object') {
            this.config = arConfig;
        }
        this.selectors = {
            badge_split: '.ya-pay-badge-split',
            badge_ultimate: '.ya-pay-badge-ultimate',
        };
        this.init();
    };
    window.YaPayBadge.prototype = {
        init: function () {
            var self = this;
            this.getProductPrice();
            BX.addCustomEvent('onCatalogElementChangeOffer', function (event) {
                self.selectOffer(event.newId);
            });
        },
        updateBadge: function () {
            document.querySelectorAll(this.selectors.badge_split).forEach((container) => {
                container.innerHTML = '';
                if (this.config.badge_split == 'Y') {
                    YaPay.mountBadge(container, {
                        type: 'bnpl',
                        amount: this.price,
                        size: this.config.badge_size,
                        variant: this.config.badge_variant,
                        color: this.config.badge_color,
                        merchantId: this.config.merchant_id,
                        align: this.config.badge_align,
                        theme: this.config.badge_theme,
                    });
                }
            });
            document.querySelectorAll(this.selectors.badge_ultimate).forEach((container) => {
                container.innerHTML = '';
                if (this.config.badge_cashback == 'Y') {
                    YaPay.mountBadge(container, {
                        type: 'ultimate',
                        amount: this.price,
                        size: this.config.badge_size,
                        variant: this.config.badge_variant,
                        source: 'item', // Пока можем только сюда вставлять в шаблоне
                        merchantId: this.config.merchant_id,
                        align: this.config.badge_align,
                        theme: this.config.badge_theme,
                    });
                }
            });
        },
        getProductPrice: function () {
            var self = this;
            BX.ajax
                .runComponentAction('yastore:yapay.badge', 'getProductPrice', {
                    mode: 'ajax',
                    data: {
                        product: this.params.PRODUCT_ID,
                    },
                })
                .then(
                    function (response) {
                        if (self.price != response.data.price.DISCOUNT_PRICE) {
                            self.price = response.data.price.DISCOUNT_PRICE;
                            self.updateBadge();
                        }
                    },
                    (response) => {
                        console.log('error');
                        console.log(response);
                    }
                );
        },
        selectOffer: function (id) {
            if (this.params.PRODUCT_ID == id) {
                return;
            }
            this.params.PRODUCT_ID = id;
            this.getProductPrice();
        },
    };
})(window);
