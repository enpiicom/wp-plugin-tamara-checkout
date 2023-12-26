import bootstrap from 'bootstrap';
import createPopper from '@popperjs/core';

(function ($) {
    $('document').ready(function () {
		const live_api_url = 'https://api.tamara.co';
		const sandbox_api_url = 'https://api-sandbox.tamara.co';

        let tamara_env_toggle = $('#woocommerce_tamara-gateway_environment');
        let value_selected;

		let webhook_enabled = $('#woocommerce_tamara-gateway_webhook_enabled')
		webhook_enabled.closest('tr').hide()

		let live_api_url_el = $('#woocommerce_tamara-gateway_live_api_url');
		let live_api_token_el = $('#woocommerce_tamara-gateway_live_api_token');
		let live_notif_token_el = $('#woocommerce_tamara-gateway_live_notification_token');
		let live_public_key_el = $('#woocommerce_tamara-gateway_live_public_key');
		let sandbox_api_url_el = $('#woocommerce_tamara-gateway_sandbox_api_url');
		let sandbox_api_token_el = $('#woocommerce_tamara-gateway_sandbox_api_token');
		let sandbox_notif_token_el = $('#woocommerce_tamara-gateway_sandbox_notification_token');
		let sandbox_public_key_el = $('#woocommerce_tamara-gateway_sandbox_public_key');

        // Display/Hide fields on selected
        function get_credential_value_selected() {
            value_selected = tamara_env_toggle.val();

            if ('live_mode' === value_selected) {
                live_api_url_el.closest('tr').css('display', 'table-row');
                live_api_url_el.attr('required', 'required');
                live_api_token_el.closest('tr').css('display', 'table-row');
                live_api_token_el.attr('required', 'required');
                live_notif_token_el.closest('tr').css('display', 'table-row');
                live_notif_token_el.attr('required', 'required');
				live_public_key_el.closest('tr').css('display', 'table-row');

                sandbox_api_url_el.closest('tr').css('display', 'none');
                sandbox_api_url_el.attr('required', false);
                sandbox_api_token_el.closest('tr').css('display', 'none');
                sandbox_api_token_el.attr('required', false);
                sandbox_notif_token_el.closest('tr').css('display', 'none');
                sandbox_notif_token_el.attr('required', false);
				sandbox_public_key_el.closest('tr').css('display', 'none');
				sandbox_public_key_el.attr('required', false);

                if (!live_api_url_el.val()) {
                    live_api_url_el.val(live_api_url);
                }

            } else if ('sandbox_mode' === value_selected) {
                live_api_url_el.closest('tr').css('display', 'none');
                live_api_url_el.attr('required', false);
                live_api_token_el.closest('tr').css('display', 'none');
                live_api_token_el.attr('required', false);
                live_notif_token_el.closest('tr').css('display', 'none');
                live_notif_token_el.attr('required', false);
				live_public_key_el.closest('tr').css('display', 'none');
				live_public_key_el.attr('required', false);

                sandbox_api_url_el.closest('tr').css('display', 'table-row');
                sandbox_api_url_el.attr('required', 'required');
                sandbox_api_token_el.closest('tr').css('display', 'table-row');
                sandbox_api_token_el.attr('required', 'required');
                sandbox_notif_token_el.closest('tr').css('display', 'table-row');
                sandbox_notif_token_el.attr('required', 'required');
				sandbox_public_key_el.closest('tr').css('display', 'table-row');

                if (!sandbox_api_url_el.val()) {
                    sandbox_api_url_el.val(sandbox_api_url);
                }
            }
        }

        // Get the selected value on change
        tamara_env_toggle.change(get_credential_value_selected);

        // Get the selected value on save
        $(window).load(get_credential_value_selected);

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
