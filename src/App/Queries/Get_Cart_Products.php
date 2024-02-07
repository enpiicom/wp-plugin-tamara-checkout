<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;

class Get_Cart_Products {
	use Executable_Trait;
	use Tamara_Trans_Trait;

	public function handle() {
		$cart_items = WC()->cart->get_cart();
		$product_ids = [];
		$product_category_ids = [];

		foreach ( $cart_items as $hash_key => $cart_item ) {
			$item_id = ! empty( $cart_item['data'] ) ? $cart_item['data']->get_id() : null;

			$product = wc_get_product( $item_id );
			// Check if a product is a variation add add its parent id to the list.
			$product_id = null;
			if ( $product instanceof \WC_Product_Variation ) {
				$product_parent_id = $product->get_parent_id() ?? null;
				if ( ! in_array( $product_parent_id, $product_ids ) ) {
					$product_id = $product_parent_id;
				}
			} else {
				$product_id = $item_id;
			}

			if ( $product_id ) {
				$product_ids[] = $product_id;
				$product_category_ids = array_merge( $product_category_ids, wc_get_product_cat_ids( $product_id ) );
			}
		}

		return [ $product_ids, $product_category_ids ];
	}
}
