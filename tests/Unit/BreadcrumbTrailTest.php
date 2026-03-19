<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbTrail;
use Daikazu\Breadcrumbs\Crumb;
use Illuminate\Support\Collection;

it('extends Illuminate Collection', function () {
    $trail = new BreadcrumbTrail;

    expect($trail)->toBeInstanceOf(Collection::class);
});

it('generates valid JSON-LD schema from toSchema', function () {
    $trail = new BreadcrumbTrail([
        new Crumb('Home', 'https://example.com'),
        new Crumb('Products', 'https://example.com/products'),
        new Crumb('Widget', '', true),
    ]);

    $schema = $trail->toSchema();

    expect($schema['@context'])->toBe('https://schema.org')
        ->and($schema['@type'])->toBe('BreadcrumbList')
        ->and($schema['itemListElement'])->toHaveCount(3)
        ->and($schema['itemListElement'][0])->toBe([
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => 'https://example.com',
        ])
        ->and($schema['itemListElement'][2])->toBe([
            '@type' => 'ListItem',
            'position' => 3,
            'name' => 'Widget',
        ]);
});

it('serializes cleanly to JSON', function () {
    $trail = new BreadcrumbTrail([
        new Crumb('Home', '/'),
    ]);

    $json = $trail->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toBeArray()
        ->and($decoded[0]['label'])->toBe('Home');
});

it('truncates middle crumbs when trail exceeds maxItems', function () {
    $trail = new BreadcrumbTrail([
        new Crumb('Home', '/'),
        new Crumb('A', '/a'),
        new Crumb('B', '/b'),
        new Crumb('C', '/c'),
        new Crumb('Current', '/current', true),
    ]);

    $truncated = $trail->truncate(2);

    expect($truncated)->toHaveCount(4)
        ->and($truncated->first()->label)->toBe('Home')
        ->and($truncated->get(1)->label)->toBe('…')
        ->and($truncated->get(1)->url)->toBe('')
        ->and($truncated->get(1)->data)->toBe(['truncated' => true])
        ->and($truncated->get(2)->label)->toBe('C')
        ->and($truncated->last()->label)->toBe('Current');
});

it('truncate is a no-op when trail length is within threshold', function () {
    $trail = new BreadcrumbTrail([
        new Crumb('Home', '/'),
        new Crumb('Current', '/current', true),
    ]);

    $truncated = $trail->truncate(5);

    expect($truncated)->toHaveCount(2)
        ->and($truncated->first()->label)->toBe('Home');
});

it('truncate returns a new instance and does not mutate original', function () {
    $trail = new BreadcrumbTrail([
        new Crumb('Home', '/'),
        new Crumb('A', '/a'),
        new Crumb('B', '/b'),
        new Crumb('C', '/c'),
        new Crumb('Current', '/current', true),
    ]);

    $truncated = $trail->truncate(2);

    expect($trail)->toHaveCount(5)
        ->and($truncated)->toHaveCount(4)
        ->and($truncated)->not->toBe($trail);
});

it('truncate accepts a custom ellipsis label', function () {
    $trail = new BreadcrumbTrail([
        new Crumb('Home', '/'),
        new Crumb('A', '/a'),
        new Crumb('B', '/b'),
        new Crumb('Current', '/current', true),
    ]);

    $truncated = $trail->truncate(1, '...');

    expect($truncated->get(1)->label)->toBe('...');
});
