<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Buzz\Exception;

/**
 * Thrown whenever a required call-flow is not respected.
 */
class LogicException extends \LogicException implements ExceptionInterface
{
}
