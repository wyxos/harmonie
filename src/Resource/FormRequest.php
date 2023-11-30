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
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function __invoke(...$routeParameters): mixed
    {
        return app()->call([$this, 'handle'], $routeParameters);
    }

    public function getPrimaryKey(): string|int|null
    {
        return $this->route($this->getPrimaryColumn()) | $this->id;
    }

    public function getPrimaryColumn(): string
    {
        return 'id';
    }

    public function queryByClass($class): Builder
    {
        return $class::query()->where($this->getPrimaryColumn(), $this->getPrimaryKey());
    }

    public function findByClass($class): Model|null
    {
        return $class::query()->findOrFail($this->getPrimaryKey());
    }

    /**
     * @throws Exception
     */
    public function deleteByClass($class): void
    {
        if (!$this->queryByClass($class)->delete()) {
            throw new Exception("Failed to delete resource $class with primary key {$this->getPrimaryKey()}");
        }
    }

    /**
     * @throws Exception
     */
    public function deleteModel(Model $model): void
    {
        if (!$model->delete()) {
            $class = class_basename($model);
            throw new Exception("Failed to delete model $class with primary key {$this->getPrimaryKey()}");
        }
    }

    protected function vision(): VisionData
    {
        return new VisionData();
    }

    protected function visionForm($form = [], $data = []): VisionData
    {
        return (new VisionData())->form($form)->data($data);
    }

    protected function visionListing($list = [], $data = []): VisionData
    {
        return (new VisionData())->list($list)->data($data);
    }
}
