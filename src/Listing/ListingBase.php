<?php

namespace Wyxos\Harmonie\Listing;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder as ScoutBuilder;
use Illuminate\Foundation\Http\FormRequest;

abstract class ListingBase extends FormRequest
{
    public function handle(): array
    {
        $page = $this->offsetGet('page') ?: 1;

        $base = $this->baseQuery();

        $this->filters($base);

        /** @var LengthAwarePaginator $pagination */
        $perPage = $this->perPage() ?? $this->offsetGet('perPage');

        if ($base instanceof ScoutBuilder) {
            $pagination = $base->paginate($perPage);
        } else {
            $pagination = $base->paginate($perPage, ['*'], null, $page);
        }

        $this->load($pagination);

        $items = collect($pagination->items())->map(fn($item) => $this->append($item));

        $listing = [
            'listing' => [
                'items' => $items,
                'total' => $pagination->total(),
                'perPage' => $perPage,
                'showing' => $pagination->count() + (int)$perPage * max(0, $pagination->currentPage() - 1),
                'nextPage' => $pagination->hasMorePages() ? $pagination->currentPage() + 1 : null,
            ]
        ];

        $filter = $this->formatFilters($this->all());

        return [
            ...$listing,
            ...$this->customData($items),
            'filters' => $filter
        ];
    }

    abstract public function baseQuery();

    abstract public function filters(Builder|ScoutBuilder $base);

    public function perPage(): int
    {
        return 10;
    }

    public function load($base)
    {

    }

    public function append($item)
    {
        return $item;
    }

    public function formatFilters($attributes): array
    {
        $formattedFilters = [];

        foreach ($attributes as $key => $rawValue) {
            // Skip if the label for the key does not exist
            if (!array_key_exists($key, $this->filterLabels())) {
                continue;
            }

            // Determine the mapped value (if it exists)
            $value = null;
            if (array_key_exists($key, $this->filterValues())) {
                $mappedValue = $this->filterValues()[$key];

                $value = null;

                if (is_callable($mappedValue)) {
                    $value = $mappedValue($rawValue);
                }

                if (is_array($mappedValue) && (is_numeric($rawValue) || is_string($rawValue))) {
                    $value = $mappedValue[$rawValue] ?? $rawValue;
                }

                if (is_array($rawValue)) {
                    $value = array_map(function ($item) use ($mappedValue) {
                        return $mappedValue[$item] ?? $item;
                    }, $rawValue);
                }
            }

            if ($rawValue == '' || $rawValue == 'all') {
                continue;
            }

            // Build the filter object
            $formattedFilters[] = [
                'key' => $key,
                'label' => $this->filterLabels()[$key],
                'rawValue' => $rawValue,
                'value' => $value ?? $rawValue,
            ];
        }

        return $formattedFilters;
    }

    public function filterLabels(): array
    {
        return [];
    }

    public function filterValues(): array
    {
        return [];
    }

    public function customData($items): array
    {
        return [];
    }

    public function whereIn(Builder|HasMany|BelongsToMany|ScoutBuilder $base, string $key, string $column = null,
                            Closure                                    $formatter = null):
    void
    {
        $column = $column ?: $key;

        $value = $this->offsetGet($key);

        if (!$value || !count($value)) {
            return;
        }

        $base->whereIn($column, $formatter ? $formatter($value) : $value);
    }

    public function whereLike(Builder|HasMany|BelongsToMany $base, string $key, string $column = null): void
    {
        $column = $column ?: $key;

        $value = strtolower(trim($this->offsetGet($key)));

        if (!$value) {
            return;
        }

        $base->whereRaw("LOWER($column) LIKE ?", ["%$value%"]);
    }

    public function whereRange(Builder|HasMany|BelongsToMany|ScoutBuilder $base, $key, $column = null): void
    {
        $column = $column ?: $key;

        $fromKey = preg_replace('/_at/', '_from', $column);

        $from = $this->offsetGet($fromKey);

        $toKey = preg_replace('/_at/', '_to', $column);

        $to = $this->offsetGet($toKey);

        $base
            ->when($from, fn(Builder|HasMany|BelongsToMany|ScoutBuilder $query) => $query->where($column, '>=', $from))
            ->when($to, fn(Builder|HasMany|BelongsToMany|ScoutBuilder $query) => $query->where($column, '<=', $to));
    }

    public function where(Builder|HasMany|BelongsToMany|ScoutBuilder $base, string $key, string $column = null,
                          Closure                                    $formatter = null):
    void
    {
        $column = $column ?: $key;

        $value = trim($this->offsetGet($key));

        if (!$value) {
            return;
        }

        $base->where($column, $formatter ? $formatter($value) : $value);
    }
}
