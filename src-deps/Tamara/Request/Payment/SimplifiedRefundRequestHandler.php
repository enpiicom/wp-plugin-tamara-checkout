<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Payment;

use Tamara_Checkout\Deps\Tamara\Request\AbstractRequestHandler;
use Tamara_Checkout\Deps\Tamara\Response\Payment\SimplifiedRefundResponse;

class SimplifiedRefundRequestHandler extends AbstractRequestHandler
{
    private const SIMPLIFIED_REFUND_ENDPOINT = '/payments/simplified-refund/%s';

    public function __invoke(SimplifiedRefundRequest $request)
    {
        $response = $this->httpClient->post(
            sprintf(self::SIMPLIFIED_REFUND_ENDPOINT, $request->getOrderId()),
            [
                'total_amount' => $request->getTotalAmount()->toArray(),
                'comment'      => $request->getComment()
            ]
        );

        return new SimplifiedRefundResponse($response);
    }
}
