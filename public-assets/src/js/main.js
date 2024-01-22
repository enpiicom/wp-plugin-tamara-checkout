/* Main JS */
'use strict';

(function ($) {
	$(document).ready(function () {
		// Set new price value to PDP Widget
		function set_new_PDP_widget_value(new_value) {
			setTimeout(() => {
				let tamara_widget = $('tamara-widget');
				// Assigning new data
				tamara_widget.attr('amount', new_value);
				window.TamaraWidgetV2.refresh();
			}, 1000);
		}

		// Trigger when a new variation is selected on FE
		$('.variations_form').each(function () {
			$(this).on('found_variation', function (event, variation) {
				set_new_PDP_widget_value(variation.display_price);
			});
		});

		// Trigger ajax call whenever phone number is updated on checkout
		$('input[name=billing_phone]').change(function () {
			$('body').trigger('update_checkout');
		});
	});

})(jQuery);

