<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Buzz\Middleware;

use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\ResponseInterface;

class ContentLengthMiddleware implements MiddlewareInterface
{
    public function handleRequest(RequestInterface $request, callable $next)
    {
        $body = $request->getBody();

        if (!$request->hasHeader('Content-Length')) {
            $request = $request->withAddedHeader('Content-Length', (string) $body->getSize());
        }

        return $next($request);
    }

    public function handleResponse(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
