<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;

it('truncates a resolved trail preserving first and last crumbs', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('deep.page', function (Trail $trail) {
        $trail->push('Home', '/');
        $trail->push('Level 1', '/1');
        $trail->push('Level 2', '/2');
        $trail->push('Level 3', '/3');
        $trail->push('Current', '/current');
    });

    $result = $manager->generate('deep.page')->truncate(2);

    expect($result)->toHaveCount(4)
        ->and($result->first()->label)->toBe('Home')
        ->and($result->get(1)->label)->toBe('…')
        ->and($result->get(1)->data)->toBe(['truncated' => true])
        ->and($result->get(2)->label)->toBe('Level 3')
        ->and($result->last()->label)->toBe('Current');
});
