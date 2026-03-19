<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Exceptions;

use RuntimeException;

class UnnamedRouteException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The current route does not have a name. Breadcrumbs require named routes.');
    }
}
