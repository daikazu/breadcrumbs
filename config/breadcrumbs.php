<?php

return [
    // Default view for <x-breadcrumbs /> — publish and override as needed
    'view' => 'breadcrumbs::tailwind',

    // Label and route for the home crumb (used by Trail::home())
    'home_label' => 'Home',
    'home_route' => 'home',

    // Where the app's breadcrumb definitions live — auto-loaded if the file exists
    'definition_file' => base_path('routes/breadcrumbs.php'),

    // Throw an exception when no breadcrumb is defined for the current route
    // Defaults to debug mode so prod stays silent
    'throw_on_missing' => env('APP_DEBUG', false),

    // Cache store for per-route crumb caching (null = app default)
    'cache_store' => env('BREADCRUMBS_CACHE_STORE'),

    // Default cache tags applied to all cached breadcrumb entries
    'cache_tags' => ['breadcrumbs'],

    // Enable Livewire wire:navigate awareness (requires livewire/livewire installed)
    'livewire' => env('BREADCRUMBS_LIVEWIRE', false),
];
