<?php

namespace Wyxos\Harmonie\Resource;

use Illuminate\Testing\TestResponse;

trait ResourceTestHelpers
{
    protected function getResource($path, $params = []): TestResponse
    {
        list($resource, $handler) = explode('.', $path, 2);

        $handler = preg_replace('/\./', '/', $handler);

        $merge = array_merge([$resource, $handler], $params);

        return $this->get(route('resource', $merge));
    }

    protected function postResource($path, $data = []): TestResponse
    {
        list($resource, $handler) = explode('.', $path, 2);

        $handler = preg_replace('/\./', '/', $handler);

        return $this->post(route('resource', [$resource, $handler]), $data);
    }
}
