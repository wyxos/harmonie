<?php

namespace Wyxos\Harmonie\Resource;

use Illuminate\Support\Str;
use function app;

class ResourceHandler extends FormRequest
{
    public function handle()
    {
        $resource = Str::studly($this->route('resource'));

        $route = $this->route('handler');

        [$path, $method] = array_merge(explode('@', $route), ['handle']);

        $method = Str::camel($method);

        $handler = collect(explode('/', $path))->map(function ($segment) {
            return Str::studly($segment);
        })->join('\\');

        $class = "App\\Resources\\$resource\\$handler";

        if (!class_exists($class)) {
            throw new \Exception("$class is not defined.");
        }

        return app()->call("$class@$method");
    }
}
