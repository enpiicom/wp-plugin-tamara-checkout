/* Main JS */
'use strict';
import bootstrap from 'bootstrap';
import createPopper from '@popperjs/core';

// Tamara Widgets call
(function ($) {
    let getCurrentLocale = document.getElementsByTagName('html')[0].getAttribute('lang').substr(0, 2);
    let currentLocale = getCurrentLocale ? getCurrentLocale : 'en';
    let storePublicKey = tamaraCheckoutParams.publicKey;
    let storeCurrency = tamaraCheckoutParams.currency;
    let storeCountry = tamaraCheckoutParams.country;

    function triggerTamaraWidget() {
        // Prevent duplicate Pay In X widgets
        for (let i = 2; i <= 12; i++) {
            let instalmentClass = '.payment_method_tamara-gateway-pay-by-instalments-' + i + ' .tamara-pay-in-x-widget div.payment-plan'
            if (document.querySelectorAll(instalmentClass).length) {
                return;
            }
        }
        // Prevent duplicate Pay In 3 widget
        let instalmentPlanClass = '.payment_method_tamara-gateway-pay-by-instalments-3 .tamara-installment-plan-widget .tamara-logo'
        if (document.querySelectorAll(instalmentPlanClass).length) {
            return;
        }
        if (window.TamaraProductWidget) {
            window.TamaraProductWidget.init(
                {
                    lang: currentLocale,
                    publicKey: storePublicKey,
                    currency: storeCurrency
                });
            window.TamaraProductWidget.render();
        }
        if (window.TamaraInstallmentPlan) {
            window.TamaraInstallmentPlan.init(
                {
                    lang: currentLocale,
                    publicKey: storePublicKey,
                    currency: storeCurrency
                });
            window.TamaraInstallmentPlan.render();
        }
    }

    window.addEventListener('load', (event) => {
        setTimeout(() => {
            triggerTamaraWidget();
        }, 1000) // Make sure Tamara's widget is installed
    });

    $(document).ready(function () {
        // Re-generate widgets on updated checkout event
        $(document.body).on('updated_checkout', function () {
            $.post(
                tamaraCheckoutParams.ajaxUrl, {
                    action: 'update-tamara-checkout-params',
                }, function (response) {
                    if (response.message === 'success') {
                        window.tamaraWidgetConfig.country = response.country;
                        storeCurrency = response.currency;
                        storeCountry = response.country;
                        window.TamaraWidgetV2.refresh();
                    }
                }
            );
            setTimeout(() => {
                triggerTamaraWidget();
            }, 1000) // Make sure Tamara's widget is installed
        });

        $(document.body).on('removed_from_cart updated_cart_totals', function () {
            setTimeout(() => {
                triggerTamaraWidget();
            }, 1000) // Make sure Tamara's widget is installed
        });

        // Trigger ajax call whenever phone number is updated on checkout
        $('input[name=billing_phone]').change(function () {
            $('body').trigger('update_checkout');
        });

    });

    // Trigger Tamara PDP Widget function
    function triggerTamaraPDPWidget() {
        if (window.TamaraProductWidget) {
            window.TamaraProductWidget.init(
                {
                    lang: currentLocale,
                    publicKey: storePublicKey
                });
            window.TamaraProductWidget.render();
        }
    }

    // Set new data instalment plan and price to PDP Widget
    function setNewPDPWidgetValue(dataInput) {
        setTimeout(() => {
            let tamaraWidget = $('tamara-widget');
            // Assigning new data
            tamaraWidget.attr('amount', dataInput);
            window.TamaraWidgetV2.refresh();
        }, 1000);
    }

    // The Ajax call to get new value of data instalment plan from selected variation product price on FE
    // function getInstalmentPlanWithSelectedVariationPrice(selectedVariationPrice) {
    //     $.post(
    //         tamaraCheckoutParams.ajaxUrl, {
    //             action: 'tamara-get-instalment-plan',
    //             variationPrice: selectedVariationPrice
    //         }, function (response) {
    //             if (response.message === 'success') {
    //                 $('tamara-widget').show();
    //                 setNewPDPWidgetValue(response.data);
    //             } else {
    //                 $('tamara-widget').hide();
    //             }
    //         }
    //     );
    // }

    // Trigger when a new variation is selected on FE
    $(document).ready(function () {
        $('.variations_form').each(function () {
            $(this).on('found_variation', function (event, variation) {
                setNewPDPWidgetValue(variation.display_price);
            });
        });
    });

})(jQuery);

