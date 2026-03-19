<?php

declare(strict_types=1);

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\Trail;

it('chains parent crumbs three levels deep', function () {
    $manager = app(BreadcrumbsManager::class);

    $manager->for('home', function (Trail $trail) {
        $trail->push('Home', '/');
    });

    $manager->for('blog', function (Trail $trail) {
        $trail->parent('home');
        $trail->push('Blog', '/blog');
    });

    $manager->for('blog.post', function (Trail $trail) {
        $trail->parent('blog');
        $trail->push('My Post', '/blog/my-post');
    });

    $result = $manager->generate('blog.post');

    expect($result)->toHaveCount(3)
        ->and($result->get(0)->label)->toBe('Home')
        ->and($result->get(1)->label)->toBe('Blog')
        ->and($result->get(2)->label)->toBe('My Post')
        ->and($result->get(0)->active)->toBeFalse()
        ->and($result->get(1)->active)->toBeFalse()
        ->and($result->get(2)->active)->toBeTrue();
});
