<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

class Build_Tamara_Cancel_Url extends Build_Tamara_Success_Url {
	public function handle() {
		$custom_cancel_url = $this->tamara_settings()->cancel_url;
		$cancel_url = ! empty( $this->wc_order ) ?
			esc_url_raw( $this->wc_order->get_cancel_order_url_raw() ) :
			home_url();

		return add_query_arg(
			array_merge( $this->params, [
				'cancel_order' => 'true',
				'_wpnonce' => wp_create_nonce( 'woocommerce-cancel_order' ),
			] ),
			$custom_cancel_url ? : $cancel_url
		);
	}
}
