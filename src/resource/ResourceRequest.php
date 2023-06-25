<?php

namespace Wyxos\Harmonie\Resource;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 */
abstract class ResourceRequest extends FormRequest
{
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
