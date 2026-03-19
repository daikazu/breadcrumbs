<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

it('renders a valid JSON-LD script tag via @breadcrumbsSchema', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('schema.test', function (Trail $trail) {
        $trail->push('Home', 'https://example.com');
        $trail->push('Page', 'https://example.com/page');
    });

    Route::get('/schema-test', fn () => Blade::render('@breadcrumbsSchema'))->name('schema.test');

    $response = $this->get('/schema-test');

    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('<script type="application/ld+json">')
        ->and($content)->toContain('"@type": "BreadcrumbList"')
        ->and($content)->toContain('"name": "Home"')
        ->and($content)->toContain('"name": "Page"');
});

it('renders nothing when no breadcrumb trail can be resolved', function () {
    config()->set('breadcrumbs.throw_on_missing', false);

    Route::get('/no-crumbs', fn () => Blade::render('@breadcrumbsSchema'))->name('no.crumbs');

    $response = $this->get('/no-crumbs');

    $response->assertOk();
    $content = trim($response->getContent());

    expect($content)->not->toContain('<script');
});
