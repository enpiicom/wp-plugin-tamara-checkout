<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\VOs;

use Enpii_Base\Foundation\Shared\Base_VO;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Enpii_Base\Foundation\Shared\Traits\Getter_Trait;

/**
 * Value Object for Tamara_WC_Payment_Gateway object setting
 *  to be able to use auto-complete faster
 * @package Tamara_Checkout\App\VOs
 * @property string $error_message The message that being processed
 * @property string $message The message from Tamara API
 * @property string $status_code The status code returned from Tamara API
 * @property array $errors in details from Tamara Api
 */
class Tamara_Api_Error_VO extends Base_VO {
	use Config_Trait;
	use Getter_Trait;

	/**
	 * Processed error message
	 * @var mixed
	 */
	protected $error_message;

	protected $message;
	protected $status_code;
	protected $errors;

	public function __construct( array $config ) {
		$this->bind_config( $config );
	}

	public function get_errors() {
		return empty( $this->errors ) ? [] : (array) $this->errors;
	}
}
