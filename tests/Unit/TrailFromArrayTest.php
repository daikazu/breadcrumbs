<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\Exceptions\InvalidTrailDataException;
use Daikazu\Breadcrumbs\Trail;

it('hydrates from a valid array', function () {
    $trail = Trail::fromArray([
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'About', 'url' => '/about'],
    ]);

    $result = $trail->toTrail();

    expect($result)->toHaveCount(2)
        ->and($result->first()->label)->toBe('Home')
        ->and($result->last()->label)->toBe('About');
});

it('hydrates from valid JSON string', function () {
    $json = json_encode([
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'Contact', 'url' => '/contact'],
    ]);

    $trail = Trail::fromJson($json);
    $result = $trail->toTrail();

    expect($result)->toHaveCount(2)
        ->and($result->last()->label)->toBe('Contact');
});

it('throws InvalidTrailDataException on malformed JSON', function () {
    Trail::fromJson('not valid json');
})->throws(InvalidTrailDataException::class);

it('throws InvalidTrailDataException when entries missing label', function () {
    $json = json_encode([
        ['url' => '/'],
    ]);

    Trail::fromJson($json);
})->throws(InvalidTrailDataException::class);

it('throws InvalidTrailDataException when input is not an array of arrays', function () {
    $json = json_encode('just a string');

    Trail::fromJson($json);
})->throws(InvalidTrailDataException::class);
