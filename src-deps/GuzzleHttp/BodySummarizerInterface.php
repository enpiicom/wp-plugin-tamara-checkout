<?php

namespace Tamara_Checkout\Deps\GuzzleHttp;

use Tamara_Checkout\Deps\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
