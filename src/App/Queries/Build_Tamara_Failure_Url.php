<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;

class Build_Tamara_Failure_Url extends Build_Tamara_Success_Url {
	public function handle() {
		$custom_failure_url = $this->tamara_settings()->failure_url;
		$failure_url = ! empty( $this->wc_order ) ?
			esc_url_raw( $this->wc_order->get_cancel_order_url_raw() ) :
			home_url();

		return add_query_arg(
			array_merge( $this->params, [
				'cancel_order' => 'true',
				'_wpnonce' => wp_create_nonce( 'woocommerce-cancel_order' ),
				'tamara_payment_status' => Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_DECLINED,
			] ),
			$custom_failure_url ? : $failure_url
		);
	}
}
