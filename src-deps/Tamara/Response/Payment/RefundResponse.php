<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Response\Payment;

use Tamara_Checkout\Deps\Tamara\Model\Order\Order;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Capture;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Refund;
use Tamara_Checkout\Deps\Tamara\Response\ClientResponse;

class RefundResponse extends ClientResponse
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var RefundItemResponse[]
     */
    private $refunds;

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @return RefundItemResponse[]
     */
    public function getRefunds(): array
    {
        return $this->refunds;
    }

    protected function parse(array $responseData): void
    {
        $this->orderId = $responseData[Order::ORDER_ID];
        $this->toRefunds($responseData[Refund::REFUND_COLLECTION]);
    }

    private function toRefunds(array $refunds): void
    {
        foreach ($refunds as $refund) {
            $this->refunds[] = new RefundItemResponse($refund[Capture::CAPTURE_ID], $refund[Refund::REFUND_ID]);
        }
    }
}
