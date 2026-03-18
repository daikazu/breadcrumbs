<?php

namespace Daikazu\Breadcrumbs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Daikazu\Breadcrumbs\Breadcrumbs
 */
class Breadcrumbs extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Daikazu\Breadcrumbs\Breadcrumbs::class;
    }
}
