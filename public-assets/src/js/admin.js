import bootstrap from 'bootstrap';
import createPopper from '@popperjs/core';

(function ($) {
    $('document').ready(function () {
		let tamara_env_toggle = $('#woocommerce_tamara-gateway_environment');
		let webhook_enabled = $('#woocommerce_tamara-gateway_webhook_enabled')
		webhook_enabled.closest('tr').hide()

        /*
         Hide Help texts field and show/hide on toggle
        */
        document.addEventListener('click', function (e) {
            // loop parent nodes from the target to the delegation node
            for (let target = e.target; target && target !== this; target = target.parentNode) {
                if (target.matches('.tamara-settings-help-texts__manage')) {
                    $('.tamara-settings-help-texts__manage').toggleClass('tamara-opened');
                    $('.tamara-settings-help-texts__manage').parent().find('.tamara-settings-help-texts__content').slideToggle();
                    break;
                }
            }
        }, false);

        /*
         Trigger buttons in Admin
        */
        trigger_manage_button('tamara-order-statuses-mappings-manage');
        trigger_manage_button('tamara-order-statuses-trigger-manage');
        trigger_manage_button('tamara-advanced-settings-manage');
        trigger_manage_button('tamara-custom-settings-manage');
        trigger_debug_button('debug-info-manage');
        tamara_env_toggle.closest('table').addClass('widefat tamara-setting-table')
        $('.tamara-settings-help-texts__manage').parent().find('.tamara-settings-help-texts__content').addClass('tamara-widefat');
    });

    /*
     Function to trigger genetal setting option buttons
     */
    function trigger_manage_button(className) {
        // Show/hide trigger events order statuses options on toggle
        let button_trigger = $('.' + className).next('p')
        button_trigger.next('table').addClass('widefat tamara-setting-table');
        button_trigger.next('table').find('tbody').addClass('tamara-display-block');
		button_trigger.next('table').find('tbody').hide();

		// Global event listener
        document.addEventListener('click', function (e) {
            // Loop parent nodes from the target to the delegation node
            for (let target = e.target; target && target !== this; target = target.parentNode) {
                if (target.matches('.' + className)) {
                    $('.' + className).toggleClass('tamara-opened');
                    button_trigger.next('table').find('tbody').slideToggle();
                    break;
                }
            }
        }, false);
    }

    /*
     Function to trigger genetal setting option buttons
     */
    function trigger_debug_button(className) {
        // Show/hide trigger events order statuses options on toggle
        let button_trigger = $('.' + className).next('p')
        button_trigger.next('table').addClass('widefat tamara-setting-table');
        button_trigger.next('table').find('tbody').addClass('tamara-display-block');
        button_trigger.next('table').find('tbody').hide();
        $('#woocommerce_tamara-gateway_debug_info_text').hide();

        // Global event listener
        document.addEventListener('click', function (e) {
            // Loop parent nodes from the target to the delegation node
            for (let target = e.target; target && target !== this; target = target.parentNode) {
                if (target.matches('.' + className)) {
                    $('.' + className).toggleClass('tamara-opened');
                    button_trigger.next('table').find('tbody').slideToggle();
                    break;
                }
            }
        }, false);
    }

})(jQuery);
