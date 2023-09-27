<?php

namespace Wyxos\Harmonie\Import;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use LimitIterator;
use Throwable;
use Wyxos\Harmonie\Import\Models\Import;

class ImportSetup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Import $import;

    protected ImportBase $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Import $import, ImportBase $instance)
    {
        $this->import = $import;

        $this->instance = $instance;

        $this->onQueue(config('import.queue.setup'));
    }

    public function handle(): string
    {
        // Define chunk size
        $this->import->updateAndBroadcast(['status' => 'calculating']);

        $chunkSize = 100;

        // Create a CSV Reader instance
        $filePath = Storage::path($this->import->path);

        $lineCount = intval(shell_exec('wc -l < ' . escapeshellarg($filePath)));

        $this->import->update([
            'max' => $lineCount - 1
        ]);

        $this->import->updateAndBroadcast([
            'status' => 'queued'
        ]);

        $csv = Reader::createFromPath($filePath, 'r');

        $csv->setHeaderOffset(0);

        // Count records and calculate total chunks
        $totalRecords = count($csv);
        $totalChunks = ceil($totalRecords / $chunkSize);

        $jobs = [];

        (new Filesystem())->ensureDirectoryExists(Storage::path('imports/chunks/'));

        // Extract the filename from the original file path
        $originalFilename = pathinfo($this->import->path, PATHINFO_FILENAME);

        for ($i = 0; $i < $totalChunks; $i++) {
            $offset = $i * $chunkSize;
            $length = min($chunkSize, $totalRecords - $offset);

            // Create a new chunk file with the original filename included
            $chunkFile = Storage::path('imports/chunks/' . $originalFilename . '_' . $i . '.csv');

            $writer = Writer::createFromPath($chunkFile, 'w+');

            $writer->insertOne($csv->getHeader());

            // Get records for this chunk and write to the new CSV file
            $records = $csv->getRecords();

            $writer->insertAll(new LimitIterator($records, $offset, $length));

            // Create a job and add it to the jobs array
            $jobs[] = new ImportChunk($this->import, $this->instance, $chunkFile, $i);
        }

        $id = $this->import->id;

        // Dispatch batch
        $batch = Bus::batch($jobs)
            ->then(function () use ($id) {
                $import = Import::query()->withCount(['successfulRows', 'failedRows'])->find($id);

                $import->updateAndBroadcast([
                    'status' => 'completed'
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($id) {
                $import = Import::query()->withCount(['successfulRows', 'failedRows'])->find($id);

                $import->updateAndBroadcast([
                    'status' => 'failed',
                    'validation' => [
                        'error' => $e->getMessage()
                    ]
                ]);

                throw $e;
            })
            ->onQueue(config('import.queue.setup'))
            ->dispatch();

        return $batch->id;
    }
}
