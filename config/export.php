<?php

return [
    'load_migrations' => false,
    'model' => \Wyxos\Harmonie\Export\Models\Export::class,
    'job' => \Wyxos\Harmonie\Export\Jobs\ExportRecords::class,
    'calculate' => \Wyxos\Harmonie\Export\Jobs\CalculateChunks::class,
    'event' => \Wyxos\Harmonie\Export\Events\ExportUpdate::class,
    'base' => \Wyxos\Harmonie\Export\ExportBase::class,
    'queue' => 'export'
];
