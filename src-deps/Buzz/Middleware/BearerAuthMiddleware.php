<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Buzz\Middleware;

use Tamara_Checkout\Deps\Buzz\Exception\InvalidArgumentException;
use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\ResponseInterface;

class BearerAuthMiddleware implements MiddlewareInterface
{
    private $accessToken;

    public function __construct(string $accessToken)
    {
        if (empty($accessToken)) {
            throw new InvalidArgumentException('You must supply a non empty accessToken');
        }

        $this->accessToken = $accessToken;
    }

    public function handleRequest(RequestInterface $request, callable $next)
    {
        $request = $request->withAddedHeader('Authorization', sprintf('Bearer %s', $this->accessToken));

        return $next($request);
    }

    public function handleResponse(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
