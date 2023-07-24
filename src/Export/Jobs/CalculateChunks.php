<?php

namespace Wyxos\Harmonie\Export\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use Throwable;
use Wyxos\Harmonie\Export\Events\ExportUpdate;
use Wyxos\Harmonie\Export\ExportBase;
use Wyxos\Harmonie\Export\Models\Export;

class CalculateChunks implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Export $export;

    protected array $filters;

    protected $instance;

    public function __construct(Export $export, array $filters, $instance)
    {
        $this->export = $export;
        $this->filters = $filters;
        $this->instance = $instance;
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
        $instance = new $this->instance;

        $export = $this->export;

        $export->update([
            'status' => 'calculating'
        ]);

        event(new ExportUpdate($export));

        $builder = $instance->query();

        if (method_exists($instance, 'filter')) {
            $request = request()->merge($this->filters);

            $instance->filter($builder, $request);
        }

        $chunkSize = $instance->chunkSize();

        $ids = $builder->pluck('id')->all();

        $chunks = array_chunk($ids, $chunkSize);

        $export->update([
            'max' => count($ids)
        ]);

        event(new ExportUpdate($export));

        $writer = Writer::createFromPath(Storage::path($export->path));

        $firstRecord = $instance->query()->where('id', $ids[0])->first();

        $header = $instance->keys($firstRecord);

        $writer->insertOne($header);

        $jobs = [];

        $job = config('export.job');

        foreach ($chunks as $chunkIds) {
            $jobs[] = new $job($chunkIds, $export, get_class($instance));
        }

        $batch = Bus::batch($jobs)->then(function (Batch $batch) use ($export) {
            // All jobs completed successfully...
            $export->update([
                'status' => 'complete'
            ]);

            event( new ExportUpdate($export));
        })->catch(function (Batch $batch, Throwable $e) use ($export) {
            // First batch job failure detected...
            $export->update([
                'status' => 'error',
            ]);

            event( new ExportUpdate($export));
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->name(basename($export->path))->dispatch();

        $export->update([
            'batch' => $batch->id,
        ]);

        event(new ExportUpdate($export));
    }
}