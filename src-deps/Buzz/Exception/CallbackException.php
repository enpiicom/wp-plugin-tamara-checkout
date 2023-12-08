<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Buzz\Exception;

use Tamara_Checkout\Deps\Psr\Http\Client\RequestExceptionInterface as PsrRequestException;
use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class CallbackException extends ClientException implements PsrRequestException
{
    private $request;

    public function __construct(RequestInterface $request, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
