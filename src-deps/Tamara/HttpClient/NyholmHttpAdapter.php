<?php

namespace Tamara_Checkout\Deps\Tamara\HttpClient;

use Tamara_Checkout\Deps\Buzz\Client\Curl;
use Tamara_Checkout\Deps\Nyholm\Psr7\Factory\Psr17Factory;
use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\ResponseInterface;
use Tamara_Checkout\Deps\Psr\Log\LoggerInterface;
use Tamara_Checkout\Deps\Tamara\Exception\RequestException;

class NyholmHttpAdapter implements ClientInterface
{
    private $client;

    /**
     * @var int
     */
    protected $requestTimeout;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(int $requestTimeout, LoggerInterface $logger = null)
    {
        $this->requestTimeout = $requestTimeout;
        $this->logger = $logger;
        $this->client = new Curl(new Psr17Factory());
    }

    /** {@inheritDoc} */
    public function createRequest(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = '1.1'
    ): RequestInterface {
        return new \Tamara_Checkout\Deps\Nyholm\Psr7\Request(
            $method,
            $uri,
            $headers,
            $body
        );
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->sendRequest(
                $request,
                [
                    'timeout' => $this->requestTimeout,
                ]
            );
        } catch (\Throwable $exception) {
            if (null !== $this->logger) {
                $this->logger->error($exception->getMessage(), $exception->getTrace());
            }

            throw new RequestException(
                $exception->getMessage(),
                $exception->getCode(),
                $request,
                null,
                $exception->getPrevious()
            );
        }
    }
}