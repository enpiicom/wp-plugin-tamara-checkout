<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Payment;

use Tamara_Checkout\Deps\Tamara\Model\Payment\Capture;

class CaptureRequest
{
    /**
     * @var Capture
     */
    private $capture;

    public function __construct(Capture $capture)
    {
        $this->capture = $capture;
    }

    public function getCapture(): Capture
    {
        return $this->capture;
    }
}
