<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Payment;

use Tamara_Checkout\Deps\Tamara\Model\Money;

class SimplifiedRefundRequest
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var Money
     */
    private $totalAmount;

    /**
     * @var string
     */
    private $comment;

    public function __construct(string $orderId, Money $totalAmount, string $comment)
    {
        $this->orderId = $orderId;
        $this->totalAmount = $totalAmount;
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return Money
     */
    public function getTotalAmount(): Money
    {
        return $this->totalAmount;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}
