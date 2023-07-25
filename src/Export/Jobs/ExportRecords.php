<?php

namespace Wyxos\Harmonie\Export\Jobs;

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use Wyxos\Harmonie\Export\Events\ExportUpdate;
use Wyxos\Harmonie\Export\ExportBase;
use Wyxos\Harmonie\Export\Models\Export;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class ExportRecords implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//    protected array $chunk;
//
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

        $instance = new $this->instance;

        $this->export->update([
            'status' => 'processing'
        ]);

        $writer = Writer::createFromPath(Storage::path($this->export->path), 'a+');

        $rows = $instance->chunkQuery()
            ->whereIn('id', $this->ids)
            ->get();

        foreach($rows as $row){
            $writer->insertOne($instance->format($row));
        }

        $this->export->update([
            'value' => DB::raw('value + ' . count($this->ids))
        ]);

        $this->export->broadcastUpdate();
    }
}
