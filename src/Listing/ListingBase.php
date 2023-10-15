<?php

namespace Wyxos\Harmonie\Listing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wyxos\Harmonie\Resource\FormRequest;

abstract class ListingBase extends FormRequest
{
    abstract public function baseQuery();

    abstract public function filters(Builder|\Laravel\Scout\Builder $base);

    public function perPage()
    {
        return 10;
    }

    public function handle(): array
    {
        $page = $this->offsetGet('page') ?: 1;

        $base = $this->baseQuery();

        $this->filters($base);

        /** @var LengthAwarePagination $pagination */
        $pagination = $base->paginate($this->perPage(), ['*'], null, $page);

        $this->load($pagination);

        $items = collect($pagination->items())->map(fn($item) => $this->append($item));

        $query = [
            'query' => [
                'items' => $items,
                'total' => $pagination->total(),
                'perPage' => $this->perPage(),
                'showing' => $pagination->count() + $pagination->perPage() * max(0, $pagination->currentPage() - 1)
            ]
        ];

        return [
            ...$query,
            ...$this->data($items)
        ];
    }

    public function append($item)
    {
        return $item;
    }

    public function data($items): array
    {
        return [];
    }

    public function load($base)
    {

    }

    public function whereIn(Builder|HasMany|BelongsToMany|\Laravel\Scout\Builder $base, string $key, string $column = null): void
    {
        $column = $column ?: $key;

        $value = $this->offsetGet($key);

        if (!$value || !count($value)) {
            return;
        }

        $base->whereIn($column, $value);
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

    public function whereRange(Builder|HasMany|BelongsToMany|\Laravel\Scout\Builder $base, $key, $column = null): void
    {
        $column = $column ?: $key;

        $fromKey = preg_replace('/_at/', '_from', $column);

        $from = $this->offsetGet($fromKey);

        $toKey = preg_replace('/_at/', '_to', $column);

        $to = $this->offsetGet($toKey);

        $base
            ->when($from, fn(Builder $query) => $query->where($column, '>=', $from))
            ->when($to, fn(Builder $query) => $query->where($column, '<=', $to));
    }

    public function where(Builder|HasMany|BelongsToMany|\Laravel\Scout\Builder $base, string $key, string $column = null): void
    {
        $column = $column ?: $key;

        $value = trim($this->offsetGet($key));

        if (!$value) {
            return;
        }

        $base->where($column, $value);
    }
}
