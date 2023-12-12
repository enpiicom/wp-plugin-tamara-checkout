<?php

namespace Tamara_Checkout\Deps\Tamara\HttpClient;

use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\StreamInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\UriInterface;

interface ClientInterface extends \Tamara_Checkout\Deps\Psr\Http\Client\ClientInterface
{
    /**
     * @param string                               $method HTTP method
     * @param string|UriInterface                  $uri URI
     * @param array                                $headers Request headers
     * @param string|null|resource|StreamInterface $body Request body
     * @param string                               $version Protocol version
     *
     * @return RequestInterface
     */
    public function createRequest(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = '1.1'
    ): RequestInterface;
}