<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tamara_Checkout\Deps\Symfony\Component\HttpFoundation\RequestMatcher;

use Tamara_Checkout\Deps\Symfony\Component\HttpFoundation\Request;
use Tamara_Checkout\Deps\Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Checks the HTTP port of a Request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class PortRequestMatcher implements RequestMatcherInterface
{
    public function __construct(private int $port)
    {
    }

    public function matches(Request $request): bool
    {
        return $request->getPort() === $this->port;
    }
}
