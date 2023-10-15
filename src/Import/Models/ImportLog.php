<?php

namespace Wyxos\Harmonie\Import\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relationships
 * @property Import import
 */
class ImportLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'validation' => 'json'
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(config('import.models.base'));
    }
}
