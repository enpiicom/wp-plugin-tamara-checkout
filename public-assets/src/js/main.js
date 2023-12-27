/* Main JS */
'use strict';

// Tamara Widgets call
(function ($) {

	// Set new data instalment plan and price to PDP Widget
	function set_new_PDP_widget_value(new_value) {
		console.log(new_value)
		setTimeout(() => {
			let tamara_widget = $('tamara-widget');
			// Assigning new data
			tamara_widget.attr('amount', new_value);
			window.TamaraWidgetV2.refresh();
		}, 1000);
	}

	// Trigger when a new variation is selected on FE
	$(document).ready(function () {
		$('.variations_form').each(function () {
			$(this).on('found_variation', function (event, variation) {
				set_new_PDP_widget_value(variation.display_price);
			});
		});
	});

})(jQuery);

