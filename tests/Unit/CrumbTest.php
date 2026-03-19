<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\Crumb;

it('creates a crumb with all properties', function () {
    $crumb = new Crumb('Home', 'https://example.com', true, ['icon' => 'house']);

    expect($crumb->label)->toBe('Home')
        ->and($crumb->url)->toBe('https://example.com')
        ->and($crumb->active)->toBeTrue()
        ->and($crumb->data)->toBe(['icon' => 'house']);
});

it('defaults active to false and data to empty array', function () {
    $crumb = new Crumb('About', '/about');

    expect($crumb->active)->toBeFalse()
        ->and($crumb->data)->toBe([]);
});

it('is a readonly class', function () {
    expect(Crumb::class)->toBeReadonly();
});
