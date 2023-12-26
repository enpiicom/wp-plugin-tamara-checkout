<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\Deps\Tamara\Notification\Message\AuthoriseMessage;
use Tamara_Checkout\Deps\Tamara\Notification\Message\WebhookMessage;
use Tamara_Checkout\Deps\Tamara\Notification\NotificationService;

class Tamara_Notification {
	use Static_Instance_Trait;

	protected $notification_key;

	protected $notification_service;

	protected function __construct( $notification_key ) {
		$this->notification_key = $notification_key;
		$this->notification_service = $this->build_notification_service( (string) $notification_key );
	}

	public function process_webhook_message(): WebhookMessage {
		return $this->notification_service->processWebhook();
	}

	public function process_authorise_message(): AuthoriseMessage {
		return $this->notification_service->processAuthoriseNotification();
	}

	protected function build_notification_service( $notification_key ): NotificationService {
		return NotificationService::create( $notification_key );
	}

	protected function reinit_notification_service( $notification_key ): void {
		$notification_service = $this->build_notification_service( $notification_key );
		static::$instance->notification_service = $notification_service;
	}
}
