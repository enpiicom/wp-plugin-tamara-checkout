<?php

namespace Tamara_Checkout\Deps\Tamara\HttpClient;

use Tamara_Checkout\Deps\GuzzleHttp\Client;
use Tamara_Checkout\Deps\GuzzleHttp\ClientInterface as GuzzleHttpClient;
use Tamara_Checkout\Deps\GuzzleHttp\Exception\GuzzleException;
use Tamara_Checkout\Deps\GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Tamara_Checkout\Deps\GuzzleHttp\Psr7\Request;
use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\ResponseInterface;
use Tamara_Checkout\Deps\Psr\Log\LoggerInterface;
use Tamara_Checkout\Deps\Tamara\Exception\RequestException;
use Throwable;

class GuzzleHttpAdapter implements ClientInterface
{
    /**
     * @var GuzzleHttpClient
     */
    protected $client;

    /**
     * @var int
     */
    protected $requestTimeout;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param int $requestTimeout
     * @param LoggerInterface|null $logger
     */
    public function __construct(int $requestTimeout, LoggerInterface $logger = null)
    {
        $this->client = new Client();
        $this->requestTimeout = $requestTimeout;
        $this->logger = $logger;
    }

    /** {@inheritDoc} */
    public function createRequest(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = '1.1'
    ): RequestInterface {
        return new Request(
            $method,
            $uri,
            $headers,
            $body
        );
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws RequestException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->send(
                $request,
                [
                    'timeout' => $this->requestTimeout,
                ]
            );
        } catch (Throwable | GuzzleException | GuzzleRequestException $exception) {
            if (null !== $this->logger) {
                $this->logger->error($exception->getMessage(), $exception->getTrace());
            }

            $exceptionResponse = null;
            if (method_exists($exception, 'getResponse')) {
                $exceptionResponse = $exception->getResponse();
            }

            throw new RequestException(
                $exception->getMessage(),
                $exception->getCode(),
                $request,
                $exceptionResponse,
                $exception->getPrevious()
            );
        }
    }
}
