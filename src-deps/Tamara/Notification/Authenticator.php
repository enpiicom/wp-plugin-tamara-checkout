<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Notification;

use Tamara_Checkout\Deps\Firebase\JWT\JWT;
use Tamara_Checkout\Deps\Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Tamara_Checkout\Deps\Tamara\Notification\Exception\ForbiddenException;
use Throwable;

class Authenticator
{
    private const
        AUTHORIZATION = 'Authorization',
        TOKEN = 'tamaraToken';
    /**
     * @var string
     */
    private $tokenKey;

    public function __construct(string $tokenKey)
    {
        $this->tokenKey = $tokenKey;
    }

    /**
     * @param Request $request
     *
     * @throws ForbiddenException
     */
    public function authenticate(Request $request): void
    {
        if (!$request->headers->has(self::AUTHORIZATION) && !$request->get(self::TOKEN)) {
            throw new ForbiddenException('Access denied.');
        }

		$bearerToken = $this->getBearerToken($request->headers->get(self::AUTHORIZATION));
        $token = $bearerToken
            ? $bearerToken
            : $request->get(self::TOKEN);

        try {
            $this->decode($token);
        } catch (Throwable $exception) {
            throw new ForbiddenException('Access denied.');
        }
    }

    protected function getBearerToken(string $authorizationHeader): string
    {
        if (!empty($authorizationHeader) && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * @param string $token
     *
     * @return object
     */
    protected function decode(string $token)
    {
        return JWT::decode($token, new Key($this->tokenKey, 'HS256'));
    }
}
