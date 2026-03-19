<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs;

use Illuminate\Support\Collection;

/**
 * @extends Collection<int, Crumb>
 */
class BreadcrumbTrail extends Collection
{
    public function toSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $this->values()->map(function (Crumb $crumb, int $index) {
                $item = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb->label,
                ];

                if ($crumb->url !== '') {
                    $item['item'] = $crumb->url;
                }

                return $item;
            })->all(),
        ];
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool, data: array<string, mixed>}>
     */
    public function jsonSerialize(): array
    {
        return $this->map(fn (Crumb $crumb) => [
            'label' => $crumb->label,
            'url' => $crumb->url,
            'active' => $crumb->active,
            'data' => $crumb->data,
        ])->values()->all();
    }

    public function truncate(int $threshold, string $ellipsisLabel = '…'): static
    {
        $afterFirst = $this->count() - 1;

        if ($afterFirst <= $threshold) {
            return new static($this->items);
        }

        $first = $this->first();
        $ellipsis = new Crumb($ellipsisLabel, '', false, ['truncated' => true]);
        $lastTwo = $this->slice(-2)->values();

        return new static(collect([$first, $ellipsis])->merge($lastTwo));
    }
}
