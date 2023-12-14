<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;

class Tamara_Widget {
	use Static_Instance_Trait;

	protected $working_mode;
	protected $public_key;

	protected function __construct( $public_key, $working_mode ) {
		$this->working_mode = $working_mode;
		$this->public_key = $public_key;
	}
}
