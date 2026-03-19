<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Facades;

use Closure;
use Daikazu\Breadcrumbs\BreadcrumbDefinition;
use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\BreadcrumbTrail;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BreadcrumbDefinition for(string $routeName, Closure $callback)
 * @method static BreadcrumbTrail current()
 * @method static BreadcrumbTrail generate(string $routeName, mixed ...$params)
 * @method static bool has(string $routeName)
 * @method static void flush()
 *
 * @see BreadcrumbsManager
 */
class Breadcrumbs extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BreadcrumbsManager::class;
    }
}
