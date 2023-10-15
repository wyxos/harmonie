<?php

namespace Wyxos\Harmonie\Import;

use Wyxos\Harmonie\Import\Models\Import;

abstract class ImportBase {
    protected Import $import;

    protected int $chunkSize = 100;

    public function __construct(protected array $data = [])
    {
    }

    abstract public function rules(object $row);

    public function beforeValidation(object &$row){

    }

    abstract public function processRow($row, $index);

    public function getChunkSize() {
        return $this->chunkSize;
    }

    public static function handle(Import $import, $data = []): void
    {
        ImportSetup::dispatch($import, new static($data = []));
    }

    public function setImport(Import $import): static
    {
        $this->import = $import;

        return $this;
    }
}
