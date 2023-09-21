<?php

namespace Wyxos\Harmonie\Export\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use Throwable;
use Wyxos\Harmonie\Export\ExportBase;
use Wyxos\Harmonie\Export\Models\Export;

class CalculateChunks implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $parameters;

    protected Export $export;

    protected string $instance;

    public function __construct(Export $export, string $instance, array $parameters = [])
    {
        $this->export = $export;
        $this->instance = $instance;
        $this->parameters = $parameters;
    }

    /**
     * @throws UnavailableStream
     * @throws Throwable
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function handle(): void
    {
        /** @var ExportBase $instance */
        $instance = new $this->instance($this->parameters);

        $export = $this->export;

        $export->update([
            'status' => 'calculating'
        ]);

        $export->broadcastUpdate();

        $builder = $instance->query($this->parameters);

        if (method_exists($instance, 'filter')) {
            $instance->filter($builder);
        }

        $chunkSize = $instance->chunkSize();

        $ids = $builder->pluck('id')->all();

        $chunks = array_chunk($ids, $chunkSize);

        $export->update([
            'max' => count($ids)
        ]);

        $export->broadcastUpdate();

        $writer = Writer::createFromPath(Storage::path($export->path));

        $firstRecord = $instance->chunkQuery()->find($ids[0]);

        $header = $instance->keys($firstRecord);

        $writer->insertOne($header);

        $jobs = [];

        $job = config('export.job');

        foreach ($chunks as $chunkIds) {
            $jobs[] = new $job($chunkIds, $export, get_class($instance));
        }

        $parameters = $this->parameters;

        $batch = Bus::batch($jobs)->then(function (Batch $batch) use ($export, $parameters) {
            // All jobs completed successfully...
            $export->update([
                'status' => 'complete'
            ]);

            if(method_exists($export, 'onComplete')){
                $export->onComplete($parameters, $batch);
            }

            $export->broadcastUpdate();
        })->catch(function (Batch $batch, Throwable $e) use ($export) {
            // First batch job failure detected...
            $export->update([
                'status' => 'error',
            ]);

            $export->broadcastUpdate();
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->name(basename($export->path))->onQueue(config('export.queue'))->dispatch();

        $export->update([
            'batch' => $batch->id,
        ]);

        $export->broadcastUpdate();
    }
}