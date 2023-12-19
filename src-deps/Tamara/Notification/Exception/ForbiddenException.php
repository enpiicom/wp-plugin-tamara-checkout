<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Notification\Exception;

class ForbiddenException extends NotificationException
{
    protected $code = 401;
}
