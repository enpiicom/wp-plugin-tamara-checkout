<?php

namespace Tamara_Checkout\Deps\GuzzleHttp;

use Tamara_Checkout\Deps\Psr\Http\Message\MessageInterface;

final class BodySummarizer implements BodySummarizerInterface
{
    /**
     * @var int|null
     */
    private $truncateAt;

    public function __construct(int $truncateAt = null)
    {
        $this->truncateAt = $truncateAt;
    }

    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string
    {
        return $this->truncateAt === null
            ? \Tamara_Checkout\Deps\GuzzleHttp\Psr7\Message::bodySummary($message)
            : \Tamara_Checkout\Deps\GuzzleHttp\Psr7\Message::bodySummary($message, $this->truncateAt);
    }
}
