<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Webhook;

use Tamara_Checkout\Deps\Tamara\Request\AbstractRequestHandler;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RetrieveWebhookResponse;

class RetrieveWebhookRequestHandler extends AbstractRequestHandler
{
    private const RETRIEVE_WEBHOOK_ENDPOINT = '/webhooks/%s';

    public function __invoke(RetrieveWebhookRequest $request)
    {
        $response = $this->httpClient->get(
            sprintf(self::RETRIEVE_WEBHOOK_ENDPOINT, $request->getWebhookId())
        );

        return new RetrieveWebhookResponse($response);
    }
}