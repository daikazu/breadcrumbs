<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Exceptions;

use RuntimeException;

class InvalidTrailDataException extends RuntimeException
{
    public static function malformedJson(string $message = ''): static
    {
        return new static("Invalid trail data: {$message}");
    }
}
