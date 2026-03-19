<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Exceptions\MissingBreadcrumbException;
use Daikazu\Breadcrumbs\Exceptions\UnnamedRouteException;
use Daikazu\Breadcrumbs\Trail;
use Illuminate\Support\Facades\Route;

it('throws MissingBreadcrumbException when throw_on_missing is true', function () {
    config()->set('breadcrumbs.throw_on_missing', true);

    $manager = app(BreadcrumbsManager::class);
    $manager->generate('nonexistent');
})->throws(MissingBreadcrumbException::class, 'nonexistent');

it('returns empty trail when throw_on_missing is false', function () {
    config()->set('breadcrumbs.throw_on_missing', false);

    $manager = app(BreadcrumbsManager::class);
    $result = $manager->generate('nonexistent');

    expect($result)->toHaveCount(0);
});

it('throws UnnamedRouteException on unnamed route when throw_on_missing is true', function () {
    config()->set('breadcrumbs.throw_on_missing', true);

    Route::get('/unnamed', fn () => 'ok');

    $this->get('/unnamed');

    $manager = app(BreadcrumbsManager::class);
    $manager->current();
})->throws(UnnamedRouteException::class);

it('resolves current route breadcrumbs', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('test.page', function (Trail $trail) {
        $trail->push('Test Page', '/test');
    });

    Route::get('/test', fn () => 'ok')->name('test.page');

    $this->get('/test');

    $result = $manager->current();

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('Test Page');
});

it('Trail::home() uses config defaults', function () {
    config()->set('breadcrumbs.home_label', 'Dashboard');
    config()->set('breadcrumbs.home_route', 'dashboard');

    Route::get('/dashboard', fn () => 'ok')->name('dashboard');

    $manager = app(BreadcrumbsManager::class);

    $manager->for('test.home', function (Trail $trail) {
        $trail->home();
        $trail->push('Page', '/page');
    });

    $result = $manager->generate('test.home');

    expect($result)->toHaveCount(2)
        ->and($result->first()->label)->toBe('Dashboard');
});
