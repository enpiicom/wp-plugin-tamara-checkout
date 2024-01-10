<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\DTOs;

use Enpii_Base\Foundation\Shared\Base_DTO;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Enpii_Base\Foundation\Shared\Traits\Getter_Trait;
use Enpii_Base\Foundation\Shared\Traits\Setter_Trait;

/**
 * @property string $tamara_order_id
 * @property string $tamara_order_number
 * @property string $tamara_payment_type
 * @property string|int $tamara_instalments
 * @property string $tamara_payment_status
 * @package Tamara_Checkout\App\DTOs
 */
class WC_Order_Tamara_Meta_DTO extends Base_DTO {
	use Config_Trait;
	use Getter_Trait;
	use Setter_Trait;

	protected $tamara_order_id;
	protected $tamara_order_number;
	protected $tamara_payment_type;
	protected $tamara_instalments;
	protected $tamara_payment_status;

	public function __construct( array $config = [] ) {
		if ( ! empty( $config ) ) {
			$this->bind_config( $config );
		}
	}
}
