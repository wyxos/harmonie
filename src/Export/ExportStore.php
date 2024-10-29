<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;
use Wyxos\Harmonie\Export\Jobs\CalculateChunks;
use Wyxos\Harmonie\Export\Models\Export;

class ExportStore
{

    public function __construct(protected array $parameters, protected string $filename, protected string $base)
    {
        $this->parameters['extension'] = $this->parameters['extension'] ?? 'csv';
    }

    /**
     * @throws UnavailableStream
     * @throws Throwable
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public static function create(array $parameters, string $filename, string $base): Export
    {
        $instance = new static($parameters, $filename, $base);

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

        $filename = $this->filename;

        $extension = $this->parameters['extension'];  // Capture the extension

        $path = '/exports/' . $filename . '.' . $extension;

        /** @var Export $export */
        $export = $model::query()->create([
            'path' => $path,
            'status' => 'pending',
            'parameters' => $this->parameters
        ]);

        // Ensure directory exists
        File::ensureDirectoryExists(Storage::path('/exports/'));

        // $this will be the Export class instance extending ExportBase
        CalculateChunks::dispatch($export, $this->base)->onQueue(config('export.queue'));

        return $export;
    }
}