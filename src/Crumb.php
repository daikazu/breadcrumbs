<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs;

readonly class Crumb
{
    public function __construct(
        public string $label,
        public string $url = '',
        public bool $active = false,
        public array $data = [],
    ) {}
}
