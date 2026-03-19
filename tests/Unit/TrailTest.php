<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbTrail;
use Daikazu\Breadcrumbs\Trail;

it('pushes crumbs onto the trail', function () {
    $trail = new Trail;
    $trail->push('Home', '/');
    $trail->push('About', '/about');

    $result = $trail->toTrail();

    expect($result)->toBeInstanceOf(BreadcrumbTrail::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first()->label)->toBe('Home')
        ->and($result->last()->label)->toBe('About');
});

it('marks the last crumb as active', function () {
    $trail = new Trail;
    $trail->push('Home', '/');
    $trail->push('Current', '/current');

    $result = $trail->toTrail();

    expect($result->first()->active)->toBeFalse()
        ->and($result->last()->active)->toBeTrue();
});

it('push returns $this for fluent chaining', function () {
    $trail = new Trail;
    $result = $trail->push('Home', '/');

    expect($result)->toBe($trail);
});

it('prepends crumbs to the beginning', function () {
    $trail = new Trail;
    $trail->push('About', '/about');
    $trail->prepend('Home', '/');

    $result = $trail->toTrail();

    expect($result->first()->label)->toBe('Home')
        ->and($result->last()->label)->toBe('About');
});

it('push accepts arbitrary data', function () {
    $trail = new Trail;
    $trail->push('Home', '/', ['icon' => 'house']);

    $result = $trail->toTrail();

    expect($result->first()->data)->toBe(['icon' => 'house']);
});

it('home uses provided label and url', function () {
    $trail = new Trail;
    $trail->home('Start', '/start');

    $result = $trail->toTrail();

    expect($result->first()->label)->toBe('Start')
        ->and($result->first()->url)->toBe('/start');
});

it('resolves parent crumbs via resolver callback', function () {
    $resolver = function (string $routeName, Trail $trail, mixed ...$params) {
        $trail->push('Parent', '/parent');
    };

    $trail = new Trail($resolver);
    $trail->parent('some.parent');
    $trail->push('Child', '/child');

    $result = $trail->toTrail();

    expect($result)->toHaveCount(2)
        ->and($result->first()->label)->toBe('Parent')
        ->and($result->last()->label)->toBe('Child');
});
