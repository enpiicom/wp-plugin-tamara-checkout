<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Tamara_Widget {
	use Static_Instance_Trait;

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
		$country_code = esc_attr( esc_js( General_Helper::get_current_country_code() ) );
		$language_code = esc_attr( esc_js( General_Helper::get_current_language_code() ) );

		$js_script = <<<JS_SCRIPT
		window.tamaraWidgetConfig = {
			lang: "$language_code",
			country: "$country_code",
			publicKey: "$public_key"
		};
JS_SCRIPT;

		wp_add_inline_script( $js_url_handle_id, $js_script, 'before' );
	}

	public function get_widget_js_url() {
		return $this->is_live_mode ?
			'//cdn.tamara.co/widget-v2/tamara-widget.js' :
			'//cdn-sandbox.tamara.co/widget-v2/tamara-widget.js';
	}

	public function get_public_key() {
		return $this->public_key;
	}

	public function fetch_tamara_pdp_widget( $data = [] ) {
		extract( (array) $data );
		$widget_amount = ! empty( $price ) ? $price : General_Helper::get_displayed_product_price();
		$widget_inline_type = 2;

		if ( ! $widget_amount ) {
			return '';
		}

		return Tamara_Checkout_WP_Plugin::wp_app_instance()->view(
			'blocks/tamara-widget',
			compact( 'widget_inline_type', 'widget_amount' )
		);
	}

	public function fetch_tamara_cart_widget( $data = [] ) {
		extract( (array) $data );
		$cart_contents_total = ! empty( WC()->cart ) ? WC()->cart->get_cart_contents_total() : 0;
		$widget_amount = ! empty( $price ) ? $price : $cart_contents_total;
		$widget_inline_type = 3;

		if ( ! $widget_amount ) {
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
}
