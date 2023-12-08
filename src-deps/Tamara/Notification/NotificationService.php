<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Notification;

use Tamara_Checkout\Deps\Symfony\Component\HttpFoundation\Request;
use Tamara_Checkout\Deps\Tamara\Notification\Exception\ForbiddenException;
use Tamara_Checkout\Deps\Tamara\Notification\Exception\NotificationException;
use Tamara_Checkout\Deps\Tamara\Notification\Message\AuthoriseMessage;
use Tamara_Checkout\Deps\Tamara\Notification\Message\WebhookMessage;

class NotificationService
{
    /**
     * @var string
     */
    private $tokenKey;

    public static function create(string $tokenKey): NotificationService
    {
        $self = new self();
        $self->tokenKey = $tokenKey;

        return $self;
    }

    /**
     * @return AuthoriseMessage
     *
     * @throws ForbiddenException
     * @throws NotificationException
     */
    public function processAuthoriseNotification(): AuthoriseMessage
    {
        /** @var AuthoriseMessage $response */
        $response = AuthoriseMessage::fromArray($this->process());

        return $response;
    }

    /**
     * @return WebhookMessage
     *
     * @throws ForbiddenException
     * @throws NotificationException
     */
    public function processWebhook(): WebhookMessage
    {
        /** @var WebhookMessage $response */
        $response = WebhookMessage::fromArray($this->process());

        return $response;
    }

    /**
     * @return array
     *
     * @throws ForbiddenException
     * @throws NotificationException
     */
    private function process(): array
    {
        $request = $this->createRequest();

        if ($request->getMethod() !== 'POST') {
            throw new NotificationException('Bad request.');
        }

        $this->authenticate($request);

        return json_decode($request->getContent(), true);
    }

    /**
     * @param Request $request
     *
     * @throws ForbiddenException
     */
    private function authenticate(Request $request): void
    {
        $this->createAuthenticator()->authenticate($request);
    }

    private function createAuthenticator(): Authenticator
    {
        return new Authenticator($this->tokenKey);
    }

    private function createRequest(): Request
    {
        return Request::createFromGlobals();
    }
}
