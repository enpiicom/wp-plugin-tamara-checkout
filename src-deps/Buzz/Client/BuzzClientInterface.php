<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Buzz\Client;

use Tamara_Checkout\Deps\Http\Client\HttpClient;
use Tamara_Checkout\Deps\Psr\Http\Client\ClientInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\ResponseInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface BuzzClientInterface extends ClientInterface, HttpClient
{
    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request, array $options = []): ResponseInterface;
}
