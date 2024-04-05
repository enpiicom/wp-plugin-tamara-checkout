<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\App\Queries\Get_Cart_Products;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Tamara_Widget {
	use Static_Instance_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	public const DEFAULT_COUNTRY_CODE = 'sa';

	protected $is_live_mode = true;
	protected $public_key;

	protected function __construct( string $public_key, bool $is_live_mode ) {
		$this->public_key = $public_key;
		$this->is_live_mode = $is_live_mode;
	}

	public function enqueue_client_scripts(): void {
		$js_url_handle_id = 'tamara-widget';
		$enqueue_script_args = version_compare( $GLOBALS['wp_version'], '6.3.0', '>=' ) ?
			[
				'in_footer' => true,
				'strategy' => 'async',
			] :
			true;
		wp_enqueue_script( $js_url_handle_id, $this->get_widget_js_url(), [], Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version(), $enqueue_script_args );

		$public_key = esc_attr( esc_js( $this->get_public_key() ) );
		$country_code = esc_attr( esc_js( Tamara_Checkout_Helper::get_current_country_code() ) );
		$language_code = esc_attr( esc_js( Tamara_Checkout_Helper::get_current_language_code() ) );

		$js_script = <<<JS_SCRIPT
		window.tamaraWidgetConfig = {
			lang: "$language_code",
			country: "$country_code",
			publicKey: "$public_key"
		};
JS_SCRIPT;

		wp_add_inline_script( $js_url_handle_id, $js_script, 'before' );
	}

	public function get_widget_js_url(): string {
		return $this->is_live_mode ?
			'//cdn.tamara.co/widget-v2/tamara-widget.js' :
			'//cdn-sandbox.tamara.co/widget-v2/tamara-widget.js';
	}

	public function get_public_key(): string {
		return $this->public_key;
	}

	public function fetch_tamara_pdp_widget( $data = [] ) {
		extract( (array) $data );
		$widget_amount = ! empty( $price ) ? $price : Tamara_Checkout_Helper::get_displayed_product_price();

		if ( is_array( $widget_amount ) ) {
			foreach ( $widget_amount as $amount ) {
				$widget_amount = $amount;
				break;
			}
		}

		$widget_inline_type = 2;

		if ( ! $widget_amount ) {
			return '';
		}

		// Todo: we need to find a way to refactor this and the snippet in
		//  Tamara_Checkout_WP_Plugin::adjust_tamara_payment_types_on_checkout
		$product_id = wc_get_product()->get_id();
		$product_ids = [];
		$product_category_ids = [];

		if ( $product_id ) {
			$product_ids[] = $product_id;
			$product_category_ids = array_merge( $product_category_ids, wc_get_product_cat_ids( $product_id ) );
		}

		$product_valid = ( count(
			array_intersect(
				$product_ids,
				$this->tamara_settings()->excluded_products
			)
		) < 1 );
		$product_category_valid = ( count(
			array_intersect(
				$product_category_ids,
				$this->tamara_settings()->excluded_product_categories
			)
		) < 1 );

		if ( ! $product_valid || ! $product_category_valid ) {
			return '';
		}

		return Tamara_Checkout_WP_Plugin::wp_app_instance()->view(
			'blocks/tamara-widget',
			compact( 'widget_inline_type', 'widget_amount' )
		);
	}

	public function fetch_tamara_cart_widget( $data = [] ) {
		extract( (array) $data );
		$cart_total = ! empty( WC()->cart ) ? WC()->cart->get_total( null ) : 0;
		$widget_amount = ! empty( $price ) ? $price : $cart_total;
		$widget_inline_type = 3;

		if ( ! $widget_amount ) {
			return '';
		}

		// Todo: we need to find a way to refactor this and the snippet in
		//  Tamara_Checkout_WP_Plugin::adjust_tamara_payment_types_on_checkout
		list($cart_product_ids, $cart_product_category_ids) = Get_Cart_Products::execute_now();

		$product_valid = ( count(
			array_intersect(
				$cart_product_ids,
				$this->tamara_settings()->excluded_products
			)
		) < 1 );
		$product_category_valid = ( count(
			array_intersect(
				$cart_product_category_ids,
				$this->tamara_settings()->excluded_product_categories
			)
		) < 1 );

		if ( ! $product_valid || ! $product_category_valid ) {
			return '';
		}

		return Tamara_Checkout_WP_Plugin::wp_app_instance()->view(
			'blocks/tamara-widget',
			[
				'widget_inline_type' => $widget_inline_type,
				'widget_amount' => $widget_amount,
			]
		);
	}

	/**
	 * @throws \Exception
	 */
	public function fetch_tamara_checkout_widget( $description, $id ): string {
		if ( Tamara_Checkout_Helper::is_tamara_payment_option( $id ) ) {
			$widget_inline_type = 3;
			$cart_amount = Tamara_Checkout_Helper::define_total_amount_to_calculate( WC()->cart->total );
			$description = Tamara_Checkout_WP_Plugin::wp_app_instance()->view(
				'blocks/tamara-widget',
				[
					'widget_inline_type' => $widget_inline_type,
					'widget_amount' => $cart_amount,
				]
			);

			$description .= $this->populate_default_description_text_on_checkout();
		}

		return $description;
	}

	/**
	 * @param $order_note
	 * @param $wc_order
	 *
	 * @return \Enpii_Base\App\WP\WP_Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed
	 */
	public function fetch_tamara_order_received_note( $order_note, $wc_order ) {
		if ( empty( $wc_order ) ) {
			return $order_note;
		}

		$payment_method = $wc_order->get_payment_method();
		$view_and_pay_url = $this->get_view_and_pay_url();

		if ( ! empty( $payment_method ) && Tamara_Checkout_Helper::is_tamara_gateway( $payment_method ) ) {
			$order_note = Tamara_Checkout_WP_Plugin::wp_app_instance()->view(
				'blocks/tamara-order-received',
				[
					'view_and_pay_url' => $view_and_pay_url,
				]
			);
		}

		return $order_note;
	}

	/**
	 * @return string
	 */
	protected function get_view_and_pay_url(): string {
		$base_url = $this->tamara_gateway()->get_settings_vo()->is_live_mode ? 'https://app.tamara.co/payments' : 'https://app-sandbox.tamara.co/payments';
		$locale_suffix = Tamara_Checkout_Helper::get_current_language_code() === 'ar' ? '?locale=ar_SA' : '?locale=en_US';

		return $base_url . $locale_suffix;
	}


	/**
	 * Populate tamara default description text on checkout
	 *
	 * @throws \Exception
	 */
	protected function populate_default_description_text_on_checkout(): string {
		$description = $this->_t( '*Exclusive for shoppers in Saudi Arabia, UAE, Kuwait and Qatar only.<br>' );
		if ( ! $this->tamara_gateway()->get_settings_vo()->is_live_mode ) {
			$description .= '<br/>' . $this->_t(
				'SANDBOX ENABLED.
							See the <a target="_blank" href="https://app-sandbox.tamara.co">Tamara Sandbox Testing Guide
							</a> for more details.'
			);
		}

		return trim( $description );
	}
}
