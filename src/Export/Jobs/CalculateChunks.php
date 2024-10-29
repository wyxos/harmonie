<?php

namespace Wyxos\Harmonie\Export\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;
use Wyxos\Harmonie\Export\ExportBase;
use Wyxos\Harmonie\Export\Models\Export;

class CalculateChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $parameters;

    protected Export $export;

    protected string $base;

    public function __construct(Export $export, string $base)
    {
        $this->export = $export;
        $this->base = $base; // Instance is the export class which extends ExportBase
        $this->parameters = $export->parameters;
    }

    /**
     * @throws UnavailableStream
     * @throws Throwable
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            $export = $this->export;

            /** @var ExportBase $exportBase */
            $exportBase = new $this->base($export);

            $export->update([
                'status' => 'calculating'
            ]);

            $export->broadcastUpdate();

            $builder = $exportBase->query($this->parameters);

            if (method_exists($exportBase, 'filter')) {
                $exportBase->filter($builder);
            }

            $chunkSize = $exportBase->chunkSize();

            $ids = $builder->pluck('id')->all();

            $chunks = array_chunk($ids, $chunkSize);

            $export->update([
                'max' => count($ids)
            ]);

            $export->broadcastUpdate();

            if ($this->export->isCsv()) {
                // Ensure the file exists and is writable before creating the writer
                $relativePath = $export->path; // Relative path used by the storage
                Storage::put($relativePath, ''); // Create an empty file if it doesn't exist

                // Use the correct path for the League\Csv\Writer
                $writer = Writer::createFromPath(Storage::path($relativePath), 'a+');

                $firstRecord = $exportBase->chunkQuery()->find($ids[0]);

                $header = $exportBase->keys($firstRecord);

                $writer->insertOne($header);
            }

            if ($this->export->isExcel()) {
                // Create a new Spreadsheet object
                $spreadsheet = new Spreadsheet();
                // Add a new sheet to the spreadsheet
                $spreadsheet->createSheet();
                // Set the first sheet as active
                $spreadsheet->setActiveSheetIndex(0);
                // Get the active sheet
                $sheet = $spreadsheet->getActiveSheet();

                // Fetch the first record to extract the header keys
                $firstRecord = $exportBase->chunkQuery()->find($ids[0]);

                // Get the headers from the first record using the keys method
                $header = $exportBase->keys($firstRecord);

                // Write the header row to the first row of the sheet (A1)
                $sheet->fromArray([$header], null, 'A1');

                // Optionally call the beforeSaveExcel method for any pre-save adjustments
                if (method_exists($exportBase, 'beforeSaveExcel')) {
                    $exportBase->beforeSaveExcel($sheet, $spreadsheet);
                }

                // Save the spreadsheet to the specified path
                $writer = new Xlsx($spreadsheet);
                $writer->save(Storage::path($this->export->path));

                $spreadsheet->disconnectWorksheets();

                unset($writer, $spreadsheet);
            }


            $jobs = [];

            $job = config('export.job');

            foreach ($chunks as $index => $chunkIds) {
                /** @var ExportRecords $exportRecords */
                $exportRecords = new $job($chunkIds, $export, get_class($exportBase), $index);

                if (method_exists($exportBase, 'chunkDelay')) {
                    $exportRecords->delay($exportBase->chunkDelay($index));
                }

                $jobs[] = $exportRecords;
            }

            $parameters = $this->parameters;

            $batch = Bus::batch($jobs)->then(function (Batch $batch) use ($export, $parameters) {
                // All jobs completed successfully...
                $export->update([
                    'status' => 'complete',
                    'expires_at' => Carbon::now()->addDays(2)
                ]);

                if (method_exists($export, 'onComplete')) {
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
        } catch (\Exception $exception) {
            $export->update([
                'status' => 'error'
            ]);

            $export->broadcastUpdate();

            throw $exception;
        }
    }
}