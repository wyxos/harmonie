<?php

namespace Wyxos\Harmonie\Export;

use Illuminate\Database\Eloquent\Model;
use Wyxos\Harmonie\Export\Jobs\ExportRecords;
use Wyxos\Harmonie\Export\Models\Export;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Throwable;

abstract class ExportBase
{
    public function keys($row)
    {
        return array_keys($this->format($row));
    }

    public static function create()
    {
        $instance = new static;

        $chunkSize = 100;

        $filename = $instance->filename();

        $count = $instance->query()
            ->count();

        $path = '/exports/' . $filename;

        $model = config('export.model');

        /** @var Export $export */
        $export = $model::query()->create([
            'path' => $path,
            'max' => $count,
            'status' => 'initiated'
        ]);

        if(!Storage::exists($export->path)){
            Storage::put($export->path, '');
        }

        $writer = Writer::createFromPath(Storage::path($export->path));

        $jobs = [];

        $rows = [];

        $chunkIndex = 0;

        $chunkCount = ceil($count / $chunkSize);

        $job = config('export.job');

        /**
         * @var $index
         * @var Model $row
         */
        foreach($instance->query()
                    ->cursor() as $index => $row){
            $rows[] =  $row;

            if($index == 0 && $chunkIndex == 0){
                $writer->insertOne($instance->keys($row));
            }

            if(count($rows) === $chunkSize){
                $jobs[] = new $job($rows, $export, $instance);

                $rows = [];

                $chunkIndex++;
            }
        };

        if(count($rows)){
            $jobs[] = new $job($rows, $export, $instance);

            $rows = [];

            $chunkIndex++;
        }

        $batch = Bus::batch($jobs)->then(function (Batch $batch) use ($export) {
            // All jobs completed successfully...
            $export->update([
                'status' => 'complete'
            ]);
        })->catch(function (Batch $batch, Throwable $e) use ($export) {
            // First batch job failure detected...

            $export->update([
                'status' => 'error',
            ]);
        })->finally(function (Batch $batch) use ($export) {
            // The batch has finished executing...
        })->name($filename)->dispatch();

        $export->update([
            'batch' => $batch->id,
        ]);
    }
}