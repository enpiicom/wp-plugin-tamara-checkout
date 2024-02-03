<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

use Tamara_Checkout\App\DTOs\WC_Order_Tamara_Meta_DTO;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Services\Tamara_Notification;
use Tamara_Checkout\App\Services\Tamara_Widget;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\VOs\Tamara_WC_Payment_Gateway_Settings_VO;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use WC_Order;

trait Tamara_Checkout_Trait {
	protected function wp_plugin(): Tamara_Checkout_WP_Plugin {
		return Tamara_Checkout_WP_Plugin::wp_app_instance();
	}

	protected function tamara_gateway(): Tamara_WC_Payment_Gateway {
		return $this->wp_plugin()->get_tamara_gateway_service();
	}

	protected function tamara_client(): Tamara_Client {
		return $this->wp_plugin()->get_tamara_client_service();
	}

	protected function tamara_widget(): Tamara_Widget {
		return $this->wp_plugin()->get_tamara_widget_service();
	}

	protected function tamara_notification(): Tamara_Notification {
		return $this->wp_plugin()->get_tamara_notification_service();
	}

	protected function tamara_settings(): Tamara_WC_Payment_Gateway_Settings_VO {
		return $this->tamara_gateway()->get_settings_vo();
	}

	protected function default_payment_gateway_id(): string {
		return Tamara_Checkout_Helper::DEFAULT_TAMARA_GATEWAY_ID;
	}

	/**
	 * @param int|\WP_Post|\WC_Order
	 */
	protected function build_tamara_wc_order( $wc_order_id ): Tamara_WC_Order {
		if ( $wc_order_id instanceof WC_Order ) {
			return new Tamara_WC_Order( $wc_order_id );
		}

		return new Tamara_WC_Order( wc_get_order( $wc_order_id ) );
	}

	/**
	 * Update Tamara meta data to the order's meta data
	 *  We want to have a public data for the Admin to see the value and
	 *  a private value for retrieving when needed as the public one may be deleted
	 */
	protected function update_tamara_meta_data( int $wc_order_id, WC_Order_Tamara_Meta_DTO $tamara_meta_dto ) {
		update_post_meta( $wc_order_id, 'tamara_order_id', $tamara_meta_dto->tamara_order_id );
		update_post_meta( $wc_order_id, '_tamara_order_id', $tamara_meta_dto->tamara_order_id );
		update_post_meta( $wc_order_id, 'tamara_order_number', $tamara_meta_dto->tamara_order_number );
		update_post_meta( $wc_order_id, '_tamara_order_number', $tamara_meta_dto->tamara_order_number );
		update_post_meta( $wc_order_id, 'tamara_payment_type', $tamara_meta_dto->tamara_payment_type );
		update_post_meta( $wc_order_id, '_tamara_payment_type', $tamara_meta_dto->tamara_payment_type );
		update_post_meta( $wc_order_id, 'tamara_instalments', $tamara_meta_dto->tamara_instalments );
		update_post_meta( $wc_order_id, '_tamara_instalments', $tamara_meta_dto->tamara_instalments );
		update_post_meta( $wc_order_id, 'tamara_payment_status', $tamara_meta_dto->tamara_payment_status );
		update_post_meta( $wc_order_id, '_tamara_payment_status', $tamara_meta_dto->tamara_payment_status );
	}
}
