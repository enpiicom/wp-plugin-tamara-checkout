<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Order;

class GetOrderByReferenceIdRequest
{
    /**
     * @var string
     */
    private $referenceId;

    public function __construct(string $referenceId)
    {
        $this->referenceId = $referenceId;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }
}