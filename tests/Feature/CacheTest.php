<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;

it('caches breadcrumb resolution and returns cached result on second call', function () {
    $manager = app(BreadcrumbsManager::class);
    $callCount = 0;

    $manager->for('cached.route', function (Trail $trail) use (&$callCount) {
        $callCount++;
        $trail->push('Cached', '/cached');
    })->cache(ttl: 3600);

    $result1 = $manager->generate('cached.route');
    $result2 = $manager->generate('cached.route');

    expect($callCount)->toBe(1)
        ->and($result1)->toHaveCount(1)
        ->and($result2)->toHaveCount(1)
        ->and($result2->first()->label)->toBe('Cached');
});
