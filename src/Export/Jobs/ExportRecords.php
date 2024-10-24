<?php

namespace Wyxos\Harmonie\Export\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Wyxos\Harmonie\Export\Models\Export;

class ExportRecords implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Export $export;

    protected string $instance;

    protected array $ids;

    public function __construct(array $ids, Export $export, string $instance)
    {
        $this->ids = $ids;
        $this->export = $export;
        $this->instance = $instance;
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

        $instance = new $this->instance((array)$this->export->parameters);

        $this->export->update([
            'status' => 'processing'
        ]);

        $rows = $instance->chunkQuery()
            ->whereIn('id', $this->ids)
            ->get();

        /* TODO writer logic for csv file */
        if ($this->export->isCsv()) {
            $writer = Writer::createFromPath(Storage::path($this->export->path), 'a+');

            foreach ($rows as $row) {
                $writer->insertOne($instance->format($row));
            }
        }

        if ($this->export->isExcel()) {
            // Reload the existing Excel located at Storage::path($this->export->path)
            $spreadsheet = IOFactory::load(Storage::path($this->export->path));
            $sheet = $spreadsheet->getActiveSheet(); // Optionally, you can select a specific sheet

            // Get the highest row number that has data
            $highestRow = $sheet->getHighestRow();

            // Iterate over each row and call the formatExcel method
            foreach ($rows as $row) {
                // Increment the row number before writing new data
                $highestRow++;

                // Pass the updated row number to the formatExcel method
                $instance->formatExcel(row: $row, sheet: $sheet, index: $highestRow);
            }

            // Save the updated Excel file back to the same path
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save(Storage::path($this->export->path));
        }

        $this->export->update([
            'value' => DB::raw('value + ' . count($this->ids))
        ]);

        $this->export->broadcastUpdate();
    }
}
