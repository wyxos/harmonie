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
use Wyxos\Harmonie\Export\ExportBase;
use Wyxos\Harmonie\Export\Models\Export;

class CalculateChunksBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes per batch

    public function __construct(
        protected Export $export,
        protected string $base,
        protected int $offset,
        protected int $limit,
        protected int $chunkSize,
        protected int $batchNumber
    ) {
    }

    public function handle(): void
    {
        try {
            /** @var ExportBase $exportBase */
            $exportBase = new $this->base($this->export);

            $builder = $exportBase->query($this->export->parameters);

            if (method_exists($exportBase, 'filter')) {
                $exportBase->filter($builder);
            }

            $jobs = [];
            $job = config('export.job');

            // Process this batch of records
            $builder->skip($this->offset)
                ->take($this->limit)
                ->chunkById($this->chunkSize, function ($chunk) use (&$jobs, $job, $exportBase) {
                    $chunkIds = $chunk->pluck('id')->all();

                    /** @var ExportRecords $exportRecords */
                    $exportRecords = new $job($chunkIds, $this->export, get_class($exportBase), count($jobs));

                    if (method_exists($exportBase, 'chunkDelay')) {
                        $exportRecords->delay($exportBase->chunkDelay(count($jobs)));
                    }

                    $jobs[] = $exportRecords;
                });

            // If this is the last batch, create the final batch with completion handlers
            if ($this->isLastBatch()) {
                $parameters = $this->export->parameters;

                $batch = Bus::batch($jobs)->then(function (Batch $batch) use ($parameters) {
                    // All jobs completed successfully...
                    $this->export->update([
                        'status' => 'complete',
                    ]);

                    if (method_exists($this->export, 'onComplete')) {
                        $this->export->onComplete($parameters, $batch);
                    }

                    $this->export->broadcastUpdate();
                })->catch(function (Batch $batch, \Throwable $e) {
                    // First batch job failure detected...
                    $this->export->update([
                        'status' => 'error',
                    ]);

                    $this->export->broadcastUpdate();
                })->finally(function (Batch $batch) {
                    // The batch has finished executing...
                })->name(basename($this->export->path) . '_batch_' . $this->batchNumber)
                ->onQueue(config('export.queue'))
                ->dispatch();
            } else {
                // For intermediate batches, just dispatch the jobs
                $batch = Bus::batch($jobs)
                    ->name(basename($this->export->path) . '_batch_' . $this->batchNumber)
                    ->onQueue(config('export.queue'))
                    ->dispatch();
            }

            // Update the export with the batch information
            $this->export->update([
                'batch' => $batch->id,
            ]);

            $this->export->broadcastUpdate();

        } catch (\Exception $exception) {
            $this->export->update([
                'status' => 'error'
            ]);

            $this->export->broadcastUpdate();

            throw $exception;
        }
    }

    private function isLastBatch(): bool
    {
        return ($this->offset + $this->limit) >= $this->export->max;
    }
}
