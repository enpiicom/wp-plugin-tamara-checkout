<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request;

use Tamara_Checkout\Deps\Tamara\HttpClient\HttpClient;

abstract class AbstractRequestHandler
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }
}
