<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbDefinition;
use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;

it('registers and checks a breadcrumb definition', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('home', function (Trail $trail) {
        $trail->push('Home', '/');
    });

    expect($manager->has('home'))->toBeTrue()
        ->and($manager->has('nonexistent'))->toBeFalse();
});

it('resolves a registered breadcrumb by route name', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('home', function (Trail $trail) {
        $trail->push('Home', '/');
    });

    $result = $manager->generate('home');

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('Home')
        ->and($result->first()->active)->toBeTrue();
});

it('for() returns a BreadcrumbDefinition for fluent cache config', function () {
    $manager = app(BreadcrumbsManager::class);

    $definition = $manager->for('home', function (Trail $trail) {
        $trail->push('Home', '/');
    });

    expect($definition)->toBeInstanceOf(BreadcrumbDefinition::class);
});
