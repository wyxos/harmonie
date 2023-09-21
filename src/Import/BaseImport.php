<?php

namespace Wyxos\Harmonie\Import;

use Wyxos\Harmonie\Import\Models\Import;

abstract class BaseImport {
    protected $chunkSize = 100;

    abstract public function rules(object $row);

    public function beforeValidation(object &$row){

    }

    abstract public function processRow($row);

    public function getChunkSize() {
        return $this->chunkSize;
    }

    public static function handle(Import $import): void
    {
        ImportSetup::dispatch($import, new static);
    }
}