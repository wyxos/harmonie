<?php

namespace Wyxos\Harmonie\Export\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Wyxos\Harmonie\Export\ExportBase;
use Wyxos\Harmonie\Export\Models\Export;

class ExportRecords implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $ids, protected Export $export, protected string $base, protected int $jobIndex)
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws UnavailableStream
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            // Determine if the batch has been cancelled...
            $this->export->update([
                'status' => 'cancelled'
            ]);

            return;
        }

        /** @var ExportBase $exportBase */
        $exportBase = new $this->base($this->export);

        $this->export->update([
            'status' => 'processing'
        ]);

        $rows = $exportBase->chunkQuery()
            ->whereIn('id', $this->ids)
            ->when(method_exists($exportBase, 'chunkWith'), fn($query) => $query->with($exportBase->chunkWith()))
            ->get();

        if ($this->export->isCsv()) {
            $writer = Writer::createFromPath(Storage::path($this->export->path), 'a+');

            foreach ($rows as $row) {
                $writer->insertOne($exportBase->format($row));
            }
        }

        if ($this->export->isExcel()) {
            $path = Storage::path($this->export->path);

            // Check if the file exists and load or create a new spreadsheet accordingly
            if (file_exists($path)) {
                try {
                    // Load the existing spreadsheet from the file path
                    $spreadsheet = IOFactory::load($path);
                } catch (ReaderException $e) {
                    throw new \Exception("Failed to load the existing Excel file: " . $e->getMessage());
                }
            } else {
                throw new \Exception("Excel file does not exist at the specified path: " . $path);
            }

            // Ensure there is at least one sheet before proceeding
            if ($spreadsheet->getSheetCount() === 0) {
                throw new \Exception("The loaded Excel file contains no sheets.");
            }

            // Retrieve the first sheet (assuming data is always written to the first sheet)
            $sheet = $spreadsheet->getActiveSheet();

//            $highestRow = $sheet->getHighestRow();

            $highestRow = ($this->jobIndex * $exportBase->chunkSize()) + 1;

            // Iterate over each row and call the formatExcel method
            foreach ($rows as $row) {
                // Increment the row number before writing new data
                $highestRow++;

                try {
                    // Pass the updated row number to the formatExcel method
                    $exportBase->formatExcel(row: $row, sheet: $sheet, index: $highestRow);
                } catch (\Exception $exception) {
                    throw $exception;
                }
            }

            // Save the updated Excel file back to the same path
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($path);

            $spreadsheet->disconnectWorksheets();

            unset($writer, $spreadsheet);
        }


        $this->export->update([
            'value' => DB::raw('value + ' . count($this->ids))
        ]);

        $this->export->broadcastUpdate();
    }
}
