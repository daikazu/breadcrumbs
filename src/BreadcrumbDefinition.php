<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs;

use Closure;
use ReflectionFunction;

class BreadcrumbDefinition
{
    protected ?int $cacheTtl = null;

    /** @var string[] */
    protected array $cacheTags = [];

    protected ?ReflectionFunction $reflection = null;

    public function __construct(
        public readonly string $routeName,
        public readonly Closure $callback,
    ) {}

    public function getReflection(): ReflectionFunction
    {
        return $this->reflection ??= new ReflectionFunction($this->callback);
    }

    public function cache(int $ttl, ?array $tags = null): static
    {
        $this->cacheTtl = $ttl;
        $this->cacheTags = $tags ?? config('breadcrumbs.cache_tags', ['breadcrumbs']);

        return $this;
    }

    public function getCacheTtl(): ?int
    {
        return $this->cacheTtl;
    }

    /**
     * @return string[]
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    public function isCached(): bool
    {
        return $this->cacheTtl !== null;
    }
}
