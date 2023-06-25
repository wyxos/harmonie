<?php

return [
    'middleware' => [],
    'handler' => Wyxos\Harmonie\Resource\ResourceHandler::class,
    'extend' => [
        'resource' => Wyxos\Harmonie\Resource\ResourceRequest::class,
        'route' => Wyxos\Harmonie\Resource\FormRequest::class,
    ]
];
