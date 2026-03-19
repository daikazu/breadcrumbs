<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Contracts\HasBreadcrumb;
use Daikazu\Breadcrumbs\Trail;
use Illuminate\Database\Eloquent\Model;

class BreadcrumbProduct extends Model implements HasBreadcrumb
{
    protected $guarded = [];

    public $timestamps = false;

    public function toBreadcrumb(Trail $trail): void
    {
        $trail->push($this->name, '/products/'.$this->id);
    }
}

it('resolves breadcrumb from model implementing HasBreadcrumb', function () {
    $manager = app(BreadcrumbsManager::class);

    $product = new BreadcrumbProduct(['id' => 1, 'name' => 'Gadget']);
    $result = $manager->generate('products.show', $product);

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('Gadget');
});

it('registered closure takes priority over HasBreadcrumb model', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('products.show', function (Trail $trail, BreadcrumbProduct $product) {
        $trail->push('Closure: '.$product->name, '/products/'.$product->id);
    });

    $product = new BreadcrumbProduct(['id' => 1, 'name' => 'Gadget']);
    $result = $manager->generate('products.show', $product);

    expect($result->first()->label)->toBe('Closure: Gadget');
});
