<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;
use Daikazu\Breadcrumbs\View\Components\Breadcrumbs;
use Illuminate\Support\Facades\Route;

it('passes explicit parameters to the definition instead of the route parameters', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('blog.post', function (Trail $trail, $post) {
        $trail->push($post->title, '/blog/'.$post->slug);
    });

    Route::get('/blog/{slug}', fn () => 'ok')->name('blog.post');

    $this->get('/blog/example-slug');

    $post = (object) ['title' => 'Real Title', 'slug' => 'example-slug'];

    $result = $manager->current($post);

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('Real Title')
        ->and($result->first()->url)->toBe('/blog/example-slug');
});

it('matches explicit parameters positionally regardless of the closure parameter name', function () {
    $manager = app(BreadcrumbsManager::class);

    // Closure parameter is named $model, not $product.
    $manager->for('product.show', function (Trail $trail, $model) {
        $trail->push($model->name, '/products/'.$model->id);
    });

    Route::get('/products/{product}', fn () => 'ok')->name('product.show');

    $this->get('/products/123');

    $product = (object) ['name' => 'Widget', 'id' => 123];

    $result = $manager->current($product);

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('Widget')
        ->and($result->first()->url)->toBe('/products/123');
});

it('passes multiple explicit parameters in order', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('catalog.product', function (Trail $trail, $category, $product) {
        $trail->push($category->name, '/'.$category->slug);
        $trail->push($product->name, '/'.$category->slug.'/'.$product->slug);
    });

    Route::get('/{category}/{product}', fn () => 'ok')->name('catalog.product');

    $this->get('/coins/challenge-coin');

    $category = (object) ['name' => 'Coins', 'slug' => 'coins'];
    $product = (object) ['name' => 'Challenge Coin', 'slug' => 'challenge-coin'];

    $result = $manager->current($category, $product);

    expect($result)->toHaveCount(2)
        ->and($result->first()->label)->toBe('Coins')
        ->and($result->last()->label)->toBe('Challenge Coin');
});

it('still falls back to route parameters when called with no arguments', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('docs.page', function (Trail $trail, $slug) {
        $trail->push("Doc: {$slug}", '/docs/'.$slug);
    });

    Route::get('/docs/{slug}', fn () => 'ok')->name('docs.page');

    $this->get('/docs/getting-started');

    $result = $manager->current();

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('Doc: getting-started')
        ->and($result->first()->url)->toBe('/docs/getting-started');
});

it('returns an empty trail with explicit parameters when there is no current route', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('blog.post', function (Trail $trail, $post) {
        $trail->push($post->title, '/blog/'.$post->slug);
    });

    $post = (object) ['title' => 'Real Title', 'slug' => 'example-slug'];

    // No request made, so there is no current route.
    $result = $manager->current($post);

    expect($result)->toHaveCount(0);
});

it('forwards component params to current() so closures receive the resolved object', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('blog.post', function (Trail $trail, $post) {
        $trail->push($post->title, '/blog/'.$post->slug);
    });

    Route::get('/blog/{slug}', function () {
        $post = (object) ['title' => 'Component Title', 'slug' => 'from-component'];

        return view('breadcrumbs::components.breadcrumbs', [
            'breadcrumbs' => app(Breadcrumbs::class, [
                'params' => [$post],
            ])->breadcrumbs,
        ]);
    })->name('blog.post');

    $response = $this->get('/blog/some-slug');

    $response->assertOk();
    $response->assertSee('Component Title');
});
