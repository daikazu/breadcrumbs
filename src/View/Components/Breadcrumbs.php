<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\View\Components;

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Daikazu\Breadcrumbs\BreadcrumbTrail;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    public BreadcrumbTrail $breadcrumbs;

    public function __construct(
        ?string $routeName = null,
        array $params = [],
    ) {
        $manager = app(BreadcrumbsManager::class);

        $this->breadcrumbs = $routeName !== null
            ? $manager->generate($routeName, ...$params)
            : $manager->current(...$params);
    }

    public function render(): View
    {
        return view(config('breadcrumbs.view', 'breadcrumbs::tailwind'));
    }

    public function shouldRender(): bool
    {
        return $this->breadcrumbs->isNotEmpty();
    }
}
