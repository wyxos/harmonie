<?php

namespace Wyxos\Harmonie\Export\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wyxos\Harmonie\Export\Events\ExportUpdate;

/**
 * @property string path
 * @property int max
 * @property int value
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
}
