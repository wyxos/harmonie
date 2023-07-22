<?php

return [
    'model' => \Wyxos\Harmonie\Export\Models\Export::class,
    'job' => \Wyxos\Harmonie\Export\Jobs\ExportRecords::class,
    'base' => \Wyxos\Harmonie\Export\ExportBase::class
];