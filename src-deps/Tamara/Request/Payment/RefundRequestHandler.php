<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Payment;

use Tamara_Checkout\Deps\Tamara\Request\AbstractRequestHandler;
use Tamara_Checkout\Deps\Tamara\Response\Payment\RefundResponse;

class RefundRequestHandler extends AbstractRequestHandler
{
    private const CAPTURE_ENDPOINT = '/payments/refund';

    public function __invoke(RefundRequest $request)
    {
        $response = $this->httpClient->post(
            self::CAPTURE_ENDPOINT,
            $request->toArray()
        );

        return new RefundResponse($response);
    }
}
