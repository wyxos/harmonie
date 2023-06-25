<?php

namespace Wyxos\Harmonie\Resource;

use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;

/**
 *
 */
class FormRequest extends LaravelFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return app()->call([$this, 'handle']);
    }
}
