<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs;

use Closure;
use Daikazu\Breadcrumbs\Contracts\HasBreadcrumb;
use Daikazu\Breadcrumbs\Exceptions\MissingBreadcrumbException;
use Daikazu\Breadcrumbs\Exceptions\UnnamedRouteException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use ReflectionNamedType;

class BreadcrumbsManager
{
    /** @var array<string, BreadcrumbDefinition> */
    protected array $definitions = [];

    public function for(string $routeName, Closure $callback): BreadcrumbDefinition
    {
        $definition = new BreadcrumbDefinition($routeName, $callback);
        $this->definitions[$routeName] = $definition;

        return $definition;
    }

    public function has(string $routeName): bool
    {
        return isset($this->definitions[$routeName]);
    }

    public function generate(string $routeName, mixed ...$params): BreadcrumbTrail
    {
        // 1. Check registered closure
        if ($this->has($routeName)) {
            return $this->resolveDefinition($routeName, $params);
        }

        // 2. Check bound models for HasBreadcrumb
        $trail = $this->resolveFromBoundModels($params);
        if ($trail !== null) {
            return $trail;
        }

        // 3. Missing
        if (config('breadcrumbs.throw_on_missing', false)) {
            throw MissingBreadcrumbException::forRoute($routeName);
        }

        return new BreadcrumbTrail;
    }

    /**
     * Generate the breadcrumb trail for the current route.
     *
     * By default the route's bound parameters are passed to the matching
     * definition. Pass explicit parameters to override them — useful when the
     * route only binds a scalar (e.g. a slug) but a closure expects the fully
     * resolved model, avoiding a second lookup:
     *
     *     Breadcrumbs::current($post);
     *
     * Explicit parameters are matched against the closure positionally, so the
     * closure's parameter name does not matter.
     */
    public function current(mixed ...$params): BreadcrumbTrail
    {
        $route = Route::current();

        if ($route === null) {
            return new BreadcrumbTrail;
        }

        $routeName = $route->getName();

        if ($routeName === null) {
            if (config('breadcrumbs.throw_on_missing', false)) {
                throw new UnnamedRouteException;
            }

            return new BreadcrumbTrail;
        }

        if ($params === []) {
            $params = $route->parameters();
        }

        return $this->generate($routeName, ...$params);
    }

    public function flush(): void
    {
        $this->definitions = [];
    }

    protected function resolveDefinition(string $routeName, array $params): BreadcrumbTrail
    {
        $definition = $this->definitions[$routeName];

        if ($definition->isCached()) {
            return $this->resolveWithCache($definition, $params);
        }

        return $this->callDefinition($definition, $params);
    }

    /** @var array<string, true> */
    protected array $resolving = [];

    protected function callDefinition(BreadcrumbDefinition $definition, array $params): BreadcrumbTrail
    {
        if (isset($this->resolving[$definition->routeName])) {
            throw new \RuntimeException("Circular breadcrumb parent reference detected for route [{$definition->routeName}].");
        }

        $this->resolving[$definition->routeName] = true;

        try {
            $parentResolver = function (string $routeName, Trail $trail, mixed ...$params) {
                if ($this->has($routeName)) {
                    $parentTrail = $this->callDefinition($this->definitions[$routeName], $params);
                    foreach ($parentTrail->reverse() as $crumb) {
                        $trail->prepend($crumb->label, $crumb->url, $crumb->data);
                    }
                }
            };

            $trail = new Trail($parentResolver);
            $resolvedParams = $this->resolveCallbackParameters($definition, $trail, $params);
            ($definition->callback)(...$resolvedParams);

            return $trail->toTrail();
        } finally {
            unset($this->resolving[$definition->routeName]);
        }
    }

    protected function resolveCallbackParameters(BreadcrumbDefinition $definition, Trail $trail, array $params): array
    {
        $parameters = $definition->getReflection()->getParameters();
        $resolved = [];
        $positionalParams = array_values($params);
        $positionalIndex = 0;
        $usedKeys = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === Trail::class) {
                $resolved[] = $trail;

                continue;
            }

            // Try to match by parameter name against route param keys
            $name = $parameter->getName();
            if (isset($params[$name]) && ! isset($usedKeys[$name])) {
                $resolved[] = $params[$name];
                $usedKeys[$name] = true;

                continue;
            }

            // Try to match by type hint
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();
                foreach ($params as $key => $param) {
                    if ($param instanceof $typeName && ! isset($usedKeys[$key])) {
                        $resolved[] = $param;
                        $usedKeys[$key] = true;

                        continue 2;
                    }
                }
            }

            // Fall back to positional
            while ($positionalIndex < count($positionalParams)) {
                $candidate = $positionalParams[$positionalIndex];
                $positionalIndex++;
                if (! $candidate instanceof Trail) {
                    $resolved[] = $candidate;

                    continue 2;
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
            }
        }

        return $resolved;
    }

    protected function resolveFromBoundModels(array $params): ?BreadcrumbTrail
    {
        foreach ($params as $param) {
            if ($param instanceof HasBreadcrumb) {
                $trail = new Trail(function (string $routeName, Trail $trail, mixed ...$params) {
                    if ($this->has($routeName)) {
                        $parentTrail = $this->callDefinition($this->definitions[$routeName], $params);
                        foreach ($parentTrail->reverse() as $crumb) {
                            $trail->prepend($crumb->label, $crumb->url, $crumb->data);
                        }
                    }
                });

                $param->toBreadcrumb($trail);

                return $trail->toTrail();
            }
        }

        return null;
    }

    protected function buildCacheKey(string $routeName, array $params): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            if ($value instanceof Model) {
                $parts[] = $key.':'.get_class($value).':'.$value->getKey();
            } elseif (is_scalar($value)) {
                $parts[] = $key.':'.$value;
            } else {
                $parts[] = $key.':'.md5(json_encode($value) ?: '');
            }
        }

        return 'breadcrumbs:'.$routeName.':'.md5(implode('|', $parts));
    }

    protected function resolveWithCache(BreadcrumbDefinition $definition, array $params): BreadcrumbTrail
    {
        $cacheKey = $this->buildCacheKey($definition->routeName, $params);
        $store = $this->getCacheStore();

        $tags = $definition->getCacheTags();
        $cache = ! empty($tags) && method_exists($store, 'tags')
            ? $store->tags($tags)
            : $store;

        return $cache->remember($cacheKey, $definition->getCacheTtl(), function () use ($definition, $params) {
            return $this->callDefinition($definition, $params);
        });
    }

    protected function getCacheStore(): CacheRepository
    {
        $storeName = config('breadcrumbs.cache_store');

        return Cache::store($storeName);
    }
}
