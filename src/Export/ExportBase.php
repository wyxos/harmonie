<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Database\Eloquent\Builder as EloquentBUilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use Throwable;
use Wyxos\Harmonie\Export\Jobs\CalculateChunks;
use Wyxos\Harmonie\Export\Models\Export;

abstract class ExportBase
{
    protected array $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;

        $this->parameters['extension'] = $this->parameters['extension'] ?? 'csv';
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

        $path = '/exports/' . $filename . '.' . $this->parameters['extension'];

        /** @var Export $export */
        $export = $model::query()->create([
            'path' => $path,
            'status' => 'pending',
            'parameters' => $this->parameters
        ]);

        if (!Storage::exists($export->path)) {
            File::ensureDirectoryExists(Storage::path('/exports/'));

            Storage::put($export->path, '');
        }

        CalculateChunks::dispatch($export, get_class($this), $this->parameters)->onQueue(config('export.queue'));

        return $export;
    }

    abstract public function filename();

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