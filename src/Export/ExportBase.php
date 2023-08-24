<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder as EloquentBUilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use Throwable;
use Wyxos\Harmonie\Export\Jobs\CalculateChunks;
use Wyxos\Harmonie\Export\Models\Export;

abstract class ExportBase
{
    protected array $parameters;

    public function keys($row): array
    {
        return array_keys($this->format($row));
    }

    abstract public function query(array $parameters = []): HasMany|BelongsToMany|Builder|EloquentBUilder;

    abstract public function chunkQuery(): HasMany|BelongsToMany|Builder|EloquentBUilder;

    abstract public function format($row);

    abstract public function filename();

    public function chunkSize(): int
    {
        return 100;
    }

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * @throws UnavailableStream
     * @throws Throwable
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public static function create(array $parameters = []): Export
    {
        $instance = new static($parameters);

        return $instance->handle();
    }

    /**
     * @throws UnavailableStream
     * @throws Throwable
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function handle(): Export
    {
        $model = config('export.model');

        $filename = $this->filename();

        $path = '/exports/' . $filename;

        /** @var Export $export */
        $export = $model::query()->create([
            'path' => $path,
            'status' => 'pending'
        ]);

        if (!Storage::exists($export->path)) {
            File::ensureDirectoryExists(Storage::path('/exports/'));

            Storage::put($export->path, '');
        }

        CalculateChunks::dispatch($export, get_class($this), $this->parameters);

        return $export;
    }
}