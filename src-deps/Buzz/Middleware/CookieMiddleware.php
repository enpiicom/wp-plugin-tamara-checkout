<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Buzz\Middleware;

use Tamara_Checkout\Deps\Buzz\Middleware\Cookie\Cookie;
use Tamara_Checkout\Deps\Buzz\Middleware\Cookie\CookieJar;
use Tamara_Checkout\Deps\Psr\Http\Message\RequestInterface;
use Tamara_Checkout\Deps\Psr\Http\Message\ResponseInterface;

class CookieMiddleware implements MiddlewareInterface
{
    private $cookieJar;

    public function __construct()
    {
        $this->cookieJar = new CookieJar();
    }

    public function setCookies(array $cookies): void
    {
        $this->cookieJar->setCookies($cookies);
    }

    /**
     * @return Cookie[]
     */
    public function getCookies(): array
    {
        return $this->cookieJar->getCookies();
    }

    /**
     * Adds a cookie to the current cookie jar.
     *
     * @param Cookie $cookie A cookie object
     */
    public function addCookie(Cookie $cookie): void
    {
        $this->cookieJar->addCookie($cookie);
    }

    public function handleRequest(RequestInterface $request, callable $next)
    {
        $this->cookieJar->clearExpiredCookies();
        $request = $this->cookieJar->addCookieHeaders($request);

        return $next($request);
    }

    public function handleResponse(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->cookieJar->processSetCookieHeaders($request, $response);

        return $next($request, $response);
    }
}
