<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Contracts;

use Daikazu\Breadcrumbs\Trail;

interface HasBreadcrumb
{
    public function toBreadcrumb(Trail $trail): void;
}
