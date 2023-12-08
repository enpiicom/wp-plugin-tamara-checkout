<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Webhook;

use Tamara_Checkout\Deps\Tamara\Request\AbstractRequestHandler;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RegisterWebhookResponse;

class RegisterWebhookRequestHandler extends AbstractRequestHandler
{
    private const REGISTER_WEBHOOK_ENDPOINT = '/webhooks';

    public function __invoke(RegisterWebhookRequest $request)
    {
        $response = $this->httpClient->post(
            self::REGISTER_WEBHOOK_ENDPOINT,
            $request->toArray()
        );

        return new RegisterWebhookResponse($response);
    }
}