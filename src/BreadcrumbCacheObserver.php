<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BreadcrumbCacheObserver
{
    /** @var string[] */
    protected array $tags;

    /**
     * @param  string[]  $tags
     */
    public function __construct(array $tags = ['breadcrumbs'])
    {
        $this->tags = $tags;
    }

    public function saved(Model $model): void
    {
        $this->flush();
    }

    public function deleted(Model $model): void
    {
        $this->flush();
    }

    protected function flush(): void
    {
        $store = Cache::store(config('breadcrumbs.cache_store'));

        if (method_exists($store, 'tags')) {
            $store->tags($this->tags)->flush();
        }
    }
}
