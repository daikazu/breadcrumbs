<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs\Livewire;

use Daikazu\Breadcrumbs\BreadcrumbsManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class BreadcrumbsComponent extends Component
{
    public ?string $routeName = null;

    public array $params = [];

    #[On('breadcrumbs:refresh')]
    public function refreshBreadcrumbs(): void
    {
        // Triggers re-render
    }

    public function render(): View
    {
        $manager = app(BreadcrumbsManager::class);

        $breadcrumbs = $this->routeName !== null
            ? $manager->generate($this->routeName, ...$this->params)
            : $manager->current();

        return view(config('breadcrumbs.view', 'breadcrumbs::tailwind'), [
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
