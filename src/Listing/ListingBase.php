<?php

namespace Wyxos\Harmonie\Listing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

abstract class ListingBase
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    abstract public function query();

    abstract public function filters(Builder|\Laravel\Scout\Builder $base);

    public function perPage()
    {
        return 10;
    }

    public function handle()
    {
        $page = $this->request->offsetGet('page') ?: 1;

        $base = $this->query();

        $this->filters($base);

        /** @var LengthAwarePagination $pagination */
        $pagination = $base->paginate($this->perPage(), ['*'], null, $page);

        $this->load($pagination);

        $items = collect($pagination->items())->map(fn($item) => $this->format($item));

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

    public function format($item)
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

    public static function get()
    {
        /** @var ListingBase $instance */
        $instance = app(static::class);

        return $instance->handle();
    }

    public function whereIn(Builder|HasMany|BelongsToMany|\Laravel\Scout\Builder $base, string $key, string $column = null): void
    {
        $column = $column ?: $key;

        $value = $this->request->offsetGet($key);

        if (!$value || !count($value)) {
            return;
        }

        $base->whereIn($column, $value);
    }

    public function whereLike(Builder|HasMany|BelongsToMany $base, string $key, string $column = null): void
    {
        $column = $column ?: $key;

        $value = strtolower(trim($this->request->offsetGet($key)));

        if (!$value) {
            return;
        }

        $base->whereRaw("LOWER($column) LIKE ?", ["%$value%"]);
    }

    public function whereRange(Builder|HasMany|BelongsToMany|\Laravel\Scout\Builder $base, $key, $column = null): void
    {
        $column = $column ?: $key;

        $fromKey = preg_replace('/_at/', '_from', $column);

        $from = $this->request->offsetGet($fromKey);

        $toKey = preg_replace('/_at/', '_to', $column);

        $to = $this->request->offsetGet($toKey);

        $base
            ->when($from, fn(Builder $query) => $query->where($column, '>=', $from))
            ->when($to, fn(Builder $query) => $query->where($column, '<=', $to));
    }

    public function where(Builder|HasMany|BelongsToMany|\Laravel\Scout\Builder $base, string $key, string $column = null): void
    {
        $column = $column ?: $key;

        $value = trim($this->request->offsetGet($key));

        if (!$value) {
            return;
        }

        $base->where($column, $value);
    }
}
