<?php

return [
    'load_migration' => false,
    'setup' => \Wyxos\Harmonie\Import\ImportSetup::class,
    'chunk' => \Wyxos\Harmonie\Import\ImportChunk::class,
    'models' => [
        'base' => \Wyxos\Harmonie\Import\Models\Import::class,
        'log' => \Wyxos\Harmonie\Import\Models\ImportLog::class,
    ],
    'base' => \Wyxos\Harmonie\Import\ImportBase::class,
    'events' => [
        'import' => \Wyxos\Harmonie\Import\Events\ImportUpdated::class,
        'row' => \Wyxos\Harmonie\Import\Events\RowImported::class,
    ],
    'queue' => [
        'setup' => 'import',
        'chunk' => 'import',
    ]
];
