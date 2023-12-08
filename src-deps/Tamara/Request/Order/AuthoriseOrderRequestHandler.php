<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Order;

use Tamara_Checkout\Deps\Tamara\Request\AbstractRequestHandler;
use Tamara_Checkout\Deps\Tamara\Response\Order\AuthoriseOrderResponse;

class AuthoriseOrderRequestHandler extends AbstractRequestHandler
{
    private const AUTHORISE_ORDER_ENDPOINT = '/orders/%s/authorise';

    public function __invoke(AuthoriseOrderRequest $request)
    {
        $response = $this->httpClient->post(
            sprintf(self::AUTHORISE_ORDER_ENDPOINT, $request->getOrderId()),
            [
                'order_id' => $request->getOrderId(),
            ]
        );

        return new AuthoriseOrderResponse($response);
    }
}
