<?php

namespace Tamara_Checkout\Deps\Tamara\HttpClient;

use Tamara_Checkout\Deps\GuzzleHttp\Psr7\Request;
use Tamara_Checkout\Deps\Psr\Log\LoggerInterface;

class AdapterFactory
{
    public static function create(int $requestTimeout, LoggerInterface $logger = null): ClientInterface
    {
        // have an issue with psr7 stream (empty request body)
        if (class_exists(Request::class)) {
            return new GuzzleHttpAdapter($requestTimeout, $logger);
        }

        return new NyholmHttpAdapter($requestTimeout, $logger);
    }
}