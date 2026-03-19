<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Tests;

use Daikazu\Breadcrumbs\BreadcrumbsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BreadcrumbsServiceProvider::class,
        ];
    }
}
