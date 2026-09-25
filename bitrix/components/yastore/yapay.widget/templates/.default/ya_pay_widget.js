(function (window) {
    'use strict';
    if (window.YaPayWidget) return;
    var activeSession = null;
    window.YaPayWidget = function (arParams, arConfig) {
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
            widget: '#ya-pay-widget',
        };
        this.init();
    };
    window.YaPayWidget.prototype = {
        init: function () {
            var self = this;
            if (typeof YaPay === 'undefined') {
                setTimeout(self.init, 500);
                return;
            }
            this.getProductPrice();
            BX.addCustomEvent('onCatalogElementChangeOffer', function (event) {
                self.selectOffer(event.newId);
            });
        },
        updateWidget: function () {
            var self = this;
            if (activeSession) {
                activeSession.update(async function () {
                    return {
                        totalAmount: self.price,
                    };
                });
                return;
            }
            let selector = document.querySelector(this.selectors.widget);
            let methods = ['SPLIT'];
            if (this.config.widget_split != 1) {
                methods.push('CARD');
            }
            const paymentData = {
                env: YaPay.PaymentEnv.PRODUCTION,
                version: 4,
                currencyCode: YaPay.CurrencyCode.Rub,
                merchantId: this.config.merchant_id,
                totalAmount: this.price,
                availablePaymentMethods: methods,
            };
            YaPay.createSession(paymentData, {
                source: 'cms',
                onPayButtonClick: () => {},
            })
                .then(function (paymentSession) {
                    activeSession = paymentSession;
                    paymentSession.mountWidget(selector, {
                        widgetType: YaPay.WidgetType.Ultimate,
                        padding: YaPay.WidgetPaddingType[self.config.widget_padding],
                        widgetTheme: YaPay.WidgetTheme[self.config.widget_theme],
                        borderRadius: self.config.widget_radius,
                        withOutline: self.config.widget_outline,
                        widgetBackground: YaPay.WidgetBackgroundType[self.config.widget_background],
                        hideWidgetHeader: self.config.widget_hide_header,
                        widgetSize: YaPay.WidgetSize[self.config.widget_size],
                    });
                })
                .catch(function (err) {
                    console.log(err);
                });
        },
        getProductPrice: function () {
            var self = this;
            BX.ajax
                .runComponentAction('yastore:yapay.widget', 'getProductPrice', {
                    mode: 'ajax',
                    data: {
                        product: this.params.PRODUCT_ID,
                    },
                })
                .then(
                    function (response) {
                        if (self.price != response.data.price.DISCOUNT_PRICE) {
                            self.price = response.data.price.DISCOUNT_PRICE;
                            self.updateWidget();
                        }
                    },
                    (response) => {
                        console.log('error');
                        console.log(response);
                    }
                );
        },
        selectOffer: function (id) {
            this.params.PRODUCT_ID = id;
            this.getProductPrice();
        },
    };
})(window);
