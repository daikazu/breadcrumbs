<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;
use Illuminate\Database\Eloquent\Model;

class TestProduct extends Model
{
    protected $guarded = [];

    public $timestamps = false;
}

it('injects route-model-bound parameters into closures', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('products.show', function (Trail $trail, TestProduct $product) {
        $trail->push($product->name, '/products/'.$product->id);
    });

    $product = new TestProduct(['id' => 1, 'name' => 'Widget']);
    $result = $manager->generate('products.show', $product);

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('Widget');
});
