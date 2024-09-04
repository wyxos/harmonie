<?php

namespace Wyxos\Harmonie\Export\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wyxos\Harmonie\Export\Events\ExportUpdate;

/**
 * @property string path
 * @property int max
 * @property int value
 * @property object parameters
 */
class Export extends Model
{
    use HasFactory;

    protected $guarded = [
        'id'
    ];

    public function broadcastUpdate(): static
    {
        $this->refresh();

        event(new ExportUpdate($this));

        return $this;
    }

    public function isCsv(): bool
    {
        return $this->parameters->extension === 'csv';
    }

    public function isExcel(): bool
    {
        return $this->parameters->extension === 'xlsx';
    }
}
