<?php

declare(strict_types=1);

namespace Daikazu\Breadcrumbs;

use Closure;
use Daikazu\Breadcrumbs\Exceptions\InvalidTrailDataException;

class Trail
{
    /** @var list<array{label: string, url: string, data: array<string, mixed>}> */
    protected array $crumbs = [];

    /** @var (Closure(string, Trail, mixed...): void)|null */
    protected ?Closure $parentResolver;

    public function __construct(?Closure $parentResolver = null)
    {
        $this->parentResolver = $parentResolver;
    }

    public function push(string $label, string $url = '', array $data = []): static
    {
        $this->crumbs[] = ['label' => $label, 'url' => $url, 'data' => $data];

        return $this;
    }

    public function prepend(string $label, string $url = '', array $data = []): static
    {
        array_unshift($this->crumbs, ['label' => $label, 'url' => $url, 'data' => $data]);

        return $this;
    }

    public function parent(string $routeName, mixed ...$params): static
    {
        if ($this->parentResolver !== null) {
            ($this->parentResolver)($routeName, $this, ...$params);
        }

        return $this;
    }

    public function home(?string $label = null, ?string $url = null): static
    {
        $label ??= config('breadcrumbs.home_label', 'Home');
        $url ??= route(config('breadcrumbs.home_route', 'home'));

        $this->prepend($label, $url);

        return $this;
    }

    public static function fromArray(array $crumbs): static
    {
        self::validateArrayData($crumbs);

        $trail = new static;

        foreach ($crumbs as $crumb) {
            $trail->push($crumb['label'], $crumb['url'] ?? '', $crumb['data'] ?? []);
        }

        return $trail;
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidTrailDataException::malformedJson('Invalid JSON: '.json_last_error_msg());
        }

        if (! is_array($data) || ! array_is_list($data)) {
            throw InvalidTrailDataException::malformedJson('Expected a JSON array of objects.');
        }

        self::validateArrayData($data);

        return self::fromArray($data);
    }

    public function toTrail(): BreadcrumbTrail
    {
        $items = [];
        $count = count($this->crumbs);

        foreach ($this->crumbs as $index => $crumb) {
            $items[] = new Crumb(
                label: $crumb['label'],
                url: $crumb['url'],
                active: $index === $count - 1,
                data: $crumb['data'],
            );
        }

        return new BreadcrumbTrail($items);
    }

    protected static function validateArrayData(array $data): void
    {
        foreach ($data as $index => $entry) {
            if (! is_array($entry)) {
                throw InvalidTrailDataException::malformedJson("Entry at index {$index} is not an array.");
            }

            if (! isset($entry['label'])) {
                throw InvalidTrailDataException::malformedJson("Entry at index {$index} is missing 'label'.");
            }
        }
    }
}
