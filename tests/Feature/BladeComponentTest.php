<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;
use Illuminate\Support\Facades\Route;

it('renders the breadcrumbs blade component', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('component.test', function (Trail $trail) {
        $trail->push('Home', '/');
        $trail->push('Test', '/test');
    });

    Route::get('/component-test', fn () => view('breadcrumbs::components.breadcrumbs', [
        'breadcrumbs' => $manager->generate('component.test'),
    ]))->name('component.test');

    $response = $this->get('/component-test');

    $response->assertOk();
    $response->assertSee('Home');
    $response->assertSee('Test');
});
