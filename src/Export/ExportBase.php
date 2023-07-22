<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use Wyxos\Harmonie\Export\Models\Export;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Throwable;

abstract class ExportBase
{
    public function keys($row): array
    {
        return array_keys($this->format($row));
    }

    abstract public function query(): HasMany|BelongsToMany|Builder|\Illuminate\Database\Eloquent\Builder;

    abstract public function format($row);

    abstract public function filename();

    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * @throws UnavailableStream
     * @throws Throwable
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public static function create(): void
    {
        $instance = new static;

        $instance->handle();
    }

    /**
     * @throws UnavailableStream
     * @throws Throwable
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function handle(): void
    {
        $chunkSize = $this->chunkSize();

        $filename = $this->filename();

        $count = $this->query()
            ->count();

        $path = '/exports/' . $filename;

        $model = config('export.model');

        /** @var Export $export */
        $export = $model::query()->create([
            'path' => $path,
            'max' => $count,
            'status' => 'initiated'
        ]);

        if(!Storage::exists($export->path)){
            Storage::put($export->path, '');
        }

        $writer = Writer::createFromPath(Storage::path($export->path));

        $jobs = [];

        $rows = [];

        $chunkIndex = 0;

        $chunkCount = ceil($count / $chunkSize);

        $job = config('export.job');

        /**
         * @var $index
         * @var Model $row
         */
        foreach($this->query()
                    ->cursor() as $index => $row){
            $rows[] =  $row;

            if($index == 0 && $chunkIndex == 0){
                $writer->insertOne($this->keys($row));
            }

            if(count($rows) === $chunkSize){
                $jobs[] = new $job($rows, $export, $this);

                $rows = [];

                $chunkIndex++;
            }
        };

        if(count($rows)){
            $jobs[] = new $job($rows, $export, $this);

            $rows = [];

            $chunkIndex++;
        }

        $batch = Bus::batch($jobs)->then(function (Batch $batch) use ($export) {
            // All jobs completed successfully...
            $export->update([
                'status' => 'complete'
            ]);
        })->catch(function (Batch $batch, Throwable $e) use ($export) {
            // First batch job failure detected...

            $export->update([
                'status' => 'error',
            ]);
        })->finally(function (Batch $batch) use ($export) {
            // The batch has finished executing...
        })->name($filename)->dispatch();

        $export->update([
            'batch' => $batch->id,
        ]);
    }
}