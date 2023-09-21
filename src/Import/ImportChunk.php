<?php

namespace Wyxos\Harmonie\Import;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Wyxos\Harmonie\Import\Events\RowImported;
use Wyxos\Harmonie\Import\Models\Import;
use Wyxos\Harmonie\Import\Models\ImportLog;

class ImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected string $path;

    protected int $index;

    protected Import $import;

    protected BaseImport $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Import $import, $instance, $path, $index)
    {
        $this->path = $path;

        $this->index = $index;

        $this->import = $import;

        $this->instance = $instance;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->import->updateAndBroadcast(['status' => 'processing']);

        // Read the file at the given path
        $csv = Reader::createFromPath($this->path, 'r');

        $headers = $csv->getHeader();

        // Skip the first line (header)
        $csv->setHeaderOffset(0);

        // Calculate starting row number based on chunk index and chunk size
        $overallStartingRowNumber = $this->index * $this->instance->getChunkSize();

        $rowNumber = $overallStartingRowNumber;

        // Loop through each record and process
        foreach ($csv->getRecords($headers) as $record) {
            $row = (object) collect($record)
                ->mapWithKeys(fn($value, $key) => [Str::snake($key) => $value])
                ->toArray();

            $this->instance->beforeValidation($row);

            // Clean up the phone number
            if (isset($row->phone)) {
                $row->phone = trim(preg_replace('/[^0-9]/', '', $row->phone));
            }

            $rowArray = (array) $row;

            $validator = Validator::make($rowArray, $this->instance->rules($row));

            if ($validator->fails()) {
                /** @var ImportLog $log */
                $log = ImportLog::query()->create([
                    'import_id' => $this->import->id,
                    'row_number' => $rowNumber,
                    'status' => 'error',
                    'validation' => $validator->errors()
                ]);

                event(new RowImported($log));
            } else {
                $this->instance->processRow($row);

                /** @var ImportLog $log */
                $log = ImportLog::query()->create([
                    'import_id' => $this->import->id,
                    'row_number' => $rowNumber,
                    'status' => 'success'
                ]);

                event(new RowImported($log));
            }

            $rowNumber++;
        }

        $this->import->updateAndBroadcast([
            'value' => $rowNumber
        ]);
    }
}