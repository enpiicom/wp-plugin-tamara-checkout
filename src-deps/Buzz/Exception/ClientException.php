<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Buzz\Exception;

use Tamara_Checkout\Deps\Http\Client\Exception as HTTPlugException;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ClientException extends \RuntimeException implements ExceptionInterface, HTTPlugException
{
}
