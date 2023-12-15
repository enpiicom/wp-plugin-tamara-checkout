<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\Deps\Tamara\Notification\NotificationService;

class Tamara_Notification {
	use Static_Instance_Trait;

	protected $notification_key;

	protected $notification_service;

	protected function __construct( $notification_key ) {
		$this->notification_key = $notification_key;
		$this->notification_service = $this->build_notification_service( $notification_key );
	}

	protected function reinit_notification_service( $notification_key ): void {
		$notification_service = $this->build_notification_service( $notification_key );
		static::$instance->notification_service = $notification_service;
	}

	protected function build_notification_service( $notification_key ): NotificationService {
		return NotificationService::create( $notification_key );
	}
}
