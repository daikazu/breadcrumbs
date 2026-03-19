<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Exceptions;

use RuntimeException;

class MissingBreadcrumbException extends RuntimeException
{
    public static function forRoute(string $routeName): static
    {
        return new static("No breadcrumb definition found for route [{$routeName}].");
    }
}
