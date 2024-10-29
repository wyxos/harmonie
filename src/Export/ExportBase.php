<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Database\Eloquent\Builder as EloquentBUilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use Throwable;
use Wyxos\Harmonie\Export\Models\Export;

abstract class ExportBase
{
    public function __construct(protected Export|null $export = null)
    {
    }

    /**
     * @throws UnavailableStream
     * @throws CannotInsertRecord
     * @throws Throwable
     * @throws Exception
     */
    public function store($parameters): Export
    {
        return ExportStore::create($parameters, $this->filename($parameters), get_class($this));
    }

    abstract public function filename($parameters = []);

    abstract public function query(array $parameters = []): HasMany|BelongsToMany|Builder|EloquentBUilder;

    public function keys($row): array
    {
        return array_keys($this->format($row));
    }

    abstract public function format($row);

    abstract public function chunkQuery(): HasMany|BelongsToMany|Builder|EloquentBUilder;

    public function chunkSize(): int
    {
        return 100;
    }
}