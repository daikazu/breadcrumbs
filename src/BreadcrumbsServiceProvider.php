<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs;

use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BreadcrumbsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('breadcrumbs')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BreadcrumbsManager::class);
        $this->app->alias(BreadcrumbsManager::class, 'breadcrumbs');
    }

    public function packageBooted(): void
    {
        Blade::component('breadcrumbs', View\Components\Breadcrumbs::class);

        Blade::directive('breadcrumbsSchema', function () {
            return "<?php
                \$__breadcrumbTrail = app(\\Daikazu\\Breadcrumbs\\BreadcrumbsManager::class)->current();
                if (\$__breadcrumbTrail->isNotEmpty()) {
                    echo view('breadcrumbs::schema', ['trail' => \$__breadcrumbTrail])->render();
                }
            ?>";
        });

        $definitionFile = config('breadcrumbs.definition_file');
        if ($definitionFile && file_exists($definitionFile)) {
            require $definitionFile;
        }

        if (
            config('breadcrumbs.livewire', false)
            && class_exists(\Livewire\Livewire::class)
        ) {
            \Livewire\Livewire::component('breadcrumbs', Livewire\BreadcrumbsComponent::class);
        }
    }
}
