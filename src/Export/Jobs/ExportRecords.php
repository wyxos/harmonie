<?php

namespace Wyxos\Harmonie\Export\Jobs;

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

    protected array $chunk;

    protected Export $export;

    protected $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chunk, Export $export, $instance)
    {
        $this->chunk = $chunk;

        $this->export = $export;

        $this->instance = $instance;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $writer = Writer::createFromPath(Storage::path($this->export->path), 'a+');

        foreach($this->chunk as $row){
            $writer->insertOne($this->instance->format($row));
        }

        $this->export->update([
            'value' => $this->export->value += count($this->chunk)
        ]);
    }
}
