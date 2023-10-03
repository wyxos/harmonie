<?php

namespace Wyxos\Harmonie\Resource;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;

/**
 * @property int id
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

    /**
     * @return int
     */
    public function getPrimaryKey(): int
    {
        return $this->id;
    }

    public function getPrimaryColumns(): string
    {
        return 'id';
    }

    /**
     * @param $class
     * @return Builder
     */
    public function queryByClass($class): Builder
    {
        return $class::query()->where($this->getPrimaryColumns(), $this->getPrimaryKey());
    }

    /**
     * @param $class
     * @return Model
     */
    public function findByClass($class): Model
    {
        return $class::query()->find($this->getPrimaryKey());
    }

    /**
     * @throws Exception
     */
    public function deleteByClass($class)
    {
        if (!$this->queryByClass($class)->delete()) {
            throw new Exception("Failed to delete resource $class with primary key {$this->getPrimaryKey()}");
        }
    }

    /**
     * @throws Exception
     */
    public function deleteModel(Model $model)
    {
        if (!$model->delete()) {
            $class = class_basename($model);
            throw new Exception("Failed to delete model $class with primary key {$this->getPrimaryKey()}");
        }
    }
}
