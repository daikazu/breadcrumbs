<picture>
   <source media="(prefers-color-scheme: dark)" srcset="art/header-dark.png">
   <img alt="Logo for Breadcrumbs" src="art/header-light.png">
</picture>

# Route-aware breadcrumb management for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/breadcrumbs.svg?style=flat-square)](https://packagist.org/packages/daikazu/breadcrumbs)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/breadcrumbs/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/breadcrumbs/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/breadcrumbs/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daikazu/breadcrumbs/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/breadcrumbs.svg?style=flat-square)](https://packagist.org/packages/daikazu/breadcrumbs)

## Installation

You can install the package via composer:

```bash
composer require daikazu/breadcrumbs
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="breadcrumbs-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="breadcrumbs-views"
```

## Usage

- [Quick Start](#quick-start)
- [Defining Breadcrumbs](#defining-breadcrumbs)
- [Parent Chaining](#parent-chaining)
- [Route Model Binding](#route-model-binding)
- [HasBreadcrumb Interface](#hasbreadcrumb-interface)
- [Rendering](#rendering)
- [Trail::home()](#trailhome)
- [Trail::fromArray() / Trail::fromJson()](#trailfromarray--trailfromjson)
- [Truncation](#truncation)
- [Caching](#caching)
- [Middleware](#middleware)
- [Livewire Integration](#livewire-integration)
- [Configuration](#configuration)

---

### Quick Start

Register a breadcrumb definition in a service provider, then render with the Blade component.

**1. Register definitions** — create a `routes/breadcrumbs.php` file and the package will auto-load it. Alternatively, register definitions directly in `AppServiceProvider::boot()`. The file path is configurable via the `definition_file` config key.

```php
// app/Providers/AppServiceProvider.php
use Daikazu\Breadcrumbs\Facades\Breadcrumbs;
use Daikazu\Breadcrumbs\Trail;

public function boot(): void
{
    Breadcrumbs::for('home', function (Trail $trail) {
        $trail->push('Home', route('home'));
    });

    Breadcrumbs::for('about', function (Trail $trail) {
        $trail->parent('home')->push('About', route('about'));
    });
}
```

**2. Render in a Blade layout:**

```blade
<x-breadcrumbs />
```

The component resolves the current route automatically and renders nothing if no matching definition exists.

---

### Defining Breadcrumbs

Use `Breadcrumbs::for()` to register a definition keyed to a route name. The closure receives a `Trail` instance as its first argument.

```php
use Daikazu\Breadcrumbs\Facades\Breadcrumbs;
use Daikazu\Breadcrumbs\Trail;

Breadcrumbs::for('products.index', function (Trail $trail) {
    $trail->push('Home', route('home'))
          ->push('Products', route('products.index'));
});
```

**Trail methods:**

| Method | Description |
|--------|-------------|
| `push(string $label, string $url = '', array $data = [])` | Appends a crumb to the end of the trail |
| `prepend(string $label, string $url = '', array $data = [])` | Inserts a crumb at the beginning of the trail |
| `parent(string $routeName, mixed ...$params)` | Resolves a parent definition and prepends its crumbs |
| `home(?string $label, ?string $url)` | Shorthand for the home crumb using config defaults |

The `$data` array on `push` and `prepend` accepts arbitrary metadata that your view can use — icon names, CSS classes, flags, and so on.

```php
$trail->push('Products', route('products.index'), ['icon' => 'shopping-bag']);
```

---

### Parent Chaining

`$trail->parent()` resolves another registered definition and prepends its crumbs to the current trail. This composes full paths from independent definitions without duplication.

```php
// Home
Breadcrumbs::for('home', function (Trail $trail) {
    $trail->push('Home', route('home'));
});

// Products index
Breadcrumbs::for('products.index', function (Trail $trail) {
    $trail->parent('home')
          ->push('Products', route('products.index'));
});

// Individual product — chains three levels deep
Breadcrumbs::for('products.show', function (Trail $trail, Product $product) {
    $trail->parent('products.index')
          ->push($product->name, route('products.show', $product));
});
```

Resolving `products.show` produces: **Home > Products > [product name]**

Each level only defines its own crumb; the chain is assembled at resolve time.

---

### Route Model Binding

When `Breadcrumbs::current()` is called, the package inspects the current route's bound parameters via `Route::current()->parameters()` and injects them into the closure automatically, matching by type-hint first, then by parameter name.

```php
Breadcrumbs::for('products.show', function (Trail $trail, Product $product) {
    $trail->parent('products.index')
          ->push($product->name, route('products.show', $product));
});
```

If the route has `{product}` bound to a `Product` model instance, it is injected without any extra configuration. The resolution mirrors Laravel's own dependency injection behavior.

Routes with multiple bound models are also supported:

```php
Breadcrumbs::for('orders.items.show', function (Trail $trail, Order $order, OrderItem $item) {
    $trail->parent('orders.show', $order)
          ->push("Item #{$item->id}", route('orders.items.show', [$order, $item]));
});
```

---

### HasBreadcrumb Interface

As an alternative to registering a closure, an Eloquent model can own its own breadcrumb definition by implementing `Daikazu\Breadcrumbs\Contracts\HasBreadcrumb`.

```php
use Daikazu\Breadcrumbs\Contracts\HasBreadcrumb;
use Daikazu\Breadcrumbs\Trail;

class Product extends Model implements HasBreadcrumb
{
    public function toBreadcrumb(Trail $trail): void
    {
        $trail->parent('products.index')
              ->push($this->name, route('products.show', $this));
    }
}
```

No `Breadcrumbs::for()` call is needed. When the route binds a `Product`, the manager calls `toBreadcrumb()` automatically.

**Resolution priority** when no registered closure exists for the current route:

1. Registered closure via `Breadcrumbs::for()`
2. Bound model implementing `HasBreadcrumb`
3. `MissingBreadcrumbException` or silent empty trail

A registered closure always wins over `HasBreadcrumb`. This lets you override a model's default definition on a per-route basis.

---

### Rendering

#### Blade component

```blade
<x-breadcrumbs />
```

The component resolves the current route. It calls `shouldRender()` internally and produces no output when the trail is empty, so it is safe to include unconditionally in layouts.

To render breadcrumbs for a specific route instead of the current one:

```blade
<x-breadcrumbs route-name="products.index" />
```

Pass route parameters when the target route requires them:

```blade
<x-breadcrumbs route-name="products.show" :params="[$product]" />
```

#### JSON-LD schema directive

Place `@breadcrumbsSchema` anywhere in your layout to output a `<script type="application/ld+json">` tag with a valid `BreadcrumbList` schema. The head is the recommended location.

```blade
<head>
    <title>My Site</title>
    @breadcrumbsSchema
</head>
```

The directive renders nothing if no breadcrumb trail can be resolved, so no empty script tags are output. The schema uses `BreadcrumbTrail::toSchema()` internally and follows the schema.org `BreadcrumbList` specification.

#### Available views

Two built-in views ship with the package:

| View name | Description |
|-----------|-------------|
| `breadcrumbs::tailwind` | Tailwind CSS — default |
| `breadcrumbs::bootstrap5` | Bootstrap 5 |

Switch the active view in `config/breadcrumbs.php`:

```php
'view' => 'breadcrumbs::bootstrap5',
```

#### Publishing and customizing views

```bash
php artisan vendor:publish --tag="breadcrumbs-views"
```

This copies the views to `resources/views/vendor/breadcrumbs/`. Edit them freely. The `schema.blade.php` partial that renders the JSON-LD script tag is structural and is not published.

After publishing, set `view` in config to point to your custom view:

```php
'view' => 'breadcrumbs::tailwind', // or your own view name after customizing
```

Each view receives a `$breadcrumbs` variable — an instance of `BreadcrumbTrail`, which extends `Illuminate\Support\Collection` of `Crumb` objects.

```blade
@foreach ($breadcrumbs as $crumb)
    {{-- $crumb->label, $crumb->url, $crumb->active, $crumb->data --}}
@endforeach
```

---

### Trail::home()

`Trail::home()` is a shorthand that prepends the home crumb using `home_label` and `home_route` from the config. It is equivalent to calling `$trail->prepend(config('breadcrumbs.home_label'), route(config('breadcrumbs.home_route')))`.

```php
Breadcrumbs::for('about', function (Trail $trail) {
    $trail->home()->push('About', route('about'));
});
```

Override either value inline:

```php
$trail->home('Start', route('dashboard'));
```

Both parameters are optional. Passing `null` (or omitting them) falls back to config values.

---

### Trail::fromArray() / Trail::fromJson()

These static factory methods build a `Trail` directly from a data source, bypassing the resolver. They are intended for headless setups, CMS-driven pages, and API responses where breadcrumb data arrives pre-assembled.

```php
use Daikazu\Breadcrumbs\Trail;

$trail = Trail::fromArray([
    ['label' => 'Home', 'url' => 'https://example.com'],
    ['label' => 'Blog', 'url' => 'https://example.com/blog'],
    ['label' => 'My Post', 'url' => 'https://example.com/blog/my-post'],
]);
```

```php
$trail = Trail::fromJson('[
    {"label": "Home", "url": "https://example.com"},
    {"label": "Blog", "url": "https://example.com/blog"},
    {"label": "My Post", "url": "https://example.com/blog/my-post"}
]');
```

Each entry requires a `label` key. The `url` key is optional.

`Trail::fromJson()` throws `Daikazu\Breadcrumbs\Exceptions\InvalidTrailDataException` if the input is not valid JSON or does not match the expected `[{label, url}]` shape.

---

### Truncation

`BreadcrumbTrail::truncate()` collapses a long trail into a fixed maximum number of items by replacing the middle crumbs with an ellipsis placeholder. It always preserves the first crumb and the last crumb.

```php
// Resolves: Home > Clothing > Men > Tops > T-Shirts > Plain Tees
$trail = Breadcrumbs::generate('products.index', $category);

// Truncate to 4 items: Home > … > T-Shirts > Plain Tees
$truncated = $trail->truncate(4);
```

The ellipsis crumb has an empty URL and `['truncated' => true]` in its `data` array so views can style it differently (for example, rendering it as a non-link).

```blade
@foreach ($breadcrumbs->truncate(4) as $crumb)
    @if ($crumb->data['truncated'] ?? false)
        <span>{{ $crumb->label }}</span>
    @else
        <a href="{{ $crumb->url }}">{{ $crumb->label }}</a>
    @endif
@endforeach
```

`truncate()` returns a new `BreadcrumbTrail` instance and does not mutate the original. When the trail length is already within `$maxItems`, the original items are returned unchanged. The `$maxItems` argument must be at least 3 (first + ellipsis + last); values below 3 are treated as a no-op.

The default ellipsis label is `…`. Pass a custom string as the second argument:

```php
$trail->truncate(4, '...');
```

---

### Caching

For routes whose breadcrumb resolution involves database queries — such as loading a category ancestor chain — enable per-definition caching by chaining `->cache()` on the return value of `Breadcrumbs::for()`.

```php
Breadcrumbs::for('categories.show', function (Trail $trail, Category $category) {
    $trail->parent('categories.index')
          ->push($category->name, route('categories.show', $category));
})->cache(ttl: 3600, tags: ['breadcrumbs', 'categories']);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `ttl` | `int` | Cache lifetime in seconds. Required. |
| `tags` | `array\|null` | Cache tags for tagged drivers. Defaults to the `cache_tags` config value (`['breadcrumbs']`). |

Cache keys are scoped per route and per model instance using model class names and primary keys — not `serialize()` — so loaded relations do not affect cache hits:

```
breadcrumbs:categories.show:{md5 hash}
```

The cache store used is whatever is configured in `breadcrumbs.cache_store` (defaults to the application's default store). Cache tags require a driver that supports them (Redis, Memcached). Do not set tags on a file or database cache driver.

#### Cache invalidation with BreadcrumbCacheObserver

The package ships a `BreadcrumbCacheObserver` that flushes breadcrumb cache entries by tag when a model fires `saved` or `deleted` events. Register it yourself in a service provider — the package does not auto-register it.

```php
use Daikazu\Breadcrumbs\BreadcrumbCacheObserver;

Category::observe(new BreadcrumbCacheObserver(['breadcrumbs', 'categories']));
```

The observer only flushes by tag, so the cache driver must support tagging. The tags passed to the observer should match those used in the `->cache()` call.

---

### Middleware

`Daikazu\Breadcrumbs\Middleware\SetBreadcrumbs` resolves `Breadcrumbs::current()` after the controller has executed and shares the result as `$breadcrumbs` with all views via `View::share()`. This is a convenience; the package functions without it.

Register it in your application's middleware stack:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Daikazu\Breadcrumbs\Middleware\SetBreadcrumbs::class,
    ]);
})
```

Or apply it to specific route groups:

```php
Route::middleware(\Daikazu\Breadcrumbs\Middleware\SetBreadcrumbs::class)
    ->group(base_path('routes/web.php'));
```

With the middleware active, `$breadcrumbs` is available in every view without calling `Breadcrumbs::current()` manually.

---

### Livewire Integration

When `livewire/livewire` is installed and `breadcrumbs.livewire` is `true`, the package registers a Livewire component in place of the standard Blade component. The Livewire component re-resolves the breadcrumb trail on `wire:navigate` page transitions without a full reload.

Enable it in `config/breadcrumbs.php`:

```php
'livewire' => env('BREADCRUMBS_LIVEWIRE', true),
```

Or via environment variable:

```
BREADCRUMBS_LIVEWIRE=true
```

The `<x-breadcrumbs />` tag in your layout works without modification. When Livewire is not installed or `breadcrumbs.livewire` is `false`, the standard Blade component is used. No Livewire dependency is introduced for non-Livewire applications.

Internally, the component listens for the `breadcrumbs:refresh` browser event triggered by Livewire's `navigate` hook:

```js
document.addEventListener('livewire:navigate', () => {
    Livewire.dispatch('breadcrumbs:refresh');
});
```

This script is only emitted when `breadcrumbs.livewire` is `true`.

---

### Laravel Octane

If you run Laravel Octane (or any long-lived process), call `Breadcrumbs::flush()` between requests to clear registered definitions and prevent state leaking across requests:

```php
// app/Providers/AppServiceProvider.php
use Laravel\Octane\Events\RequestTerminated;

public function boot(): void
{
    $this->app['events']->listen(RequestTerminated::class, function () {
        app('breadcrumbs')->flush();
    });
}
```

---

### Configuration

Publish the config file to customize defaults:

```bash
php artisan vendor:publish --tag="breadcrumbs-config"
```

Full reference:

| Key | Default | Description |
|-----|---------|-------------|
| `view` | `breadcrumbs::tailwind` | Blade view used by `<x-breadcrumbs />`. Switch to `breadcrumbs::bootstrap5` or any published/custom view name. |
| `home_label` | `'Home'` | Label used by `Trail::home()`. |
| `home_route` | `'home'` | Route name used by `Trail::home()` to generate the home URL. |
| `definition_file` | `base_path('routes/breadcrumbs.php')` | Auto-loaded if the file exists. Set to `null` to disable. |
| `throw_on_missing` | `env('APP_DEBUG', false)` | Throw `MissingBreadcrumbException` when no breadcrumb can be resolved. Defaults to debug mode so production stays silent. |
| `cache_store` | `env('BREADCRUMBS_CACHE_STORE', null)` | Cache store for per-route caching. `null` uses the application default. |
| `cache_tags` | `['breadcrumbs']` | Default cache tags applied to cached breadcrumb entries. |
| `livewire` | `env('BREADCRUMBS_LIVEWIRE', false)` | Enable Livewire `wire:navigate` awareness. Requires `livewire/livewire`. |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
