<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Checkout;

use Tamara_Checkout\Deps\Tamara\Model\Order\Order;

class CreateCheckoutRequest
{
    /**
     * @var Order
     */
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

}
