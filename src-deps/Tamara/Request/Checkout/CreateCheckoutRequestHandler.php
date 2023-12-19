<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Checkout;

use Tamara_Checkout\Deps\Tamara\Request\AbstractRequestHandler;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CreateCheckoutResponse;

class CreateCheckoutRequestHandler extends AbstractRequestHandler
{
    private const CHECKOUT_ENDPOINT = '/checkout';

    public function __invoke(CreateCheckoutRequest $request)
    {
        $response = $this->httpClient->post(
            self::CHECKOUT_ENDPOINT,
            $request->getOrder()->toArray()
        );

        return new CreateCheckoutResponse($response);
    }
}
