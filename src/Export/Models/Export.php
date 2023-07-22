<?php

namespace Wyxos\Harmonie\Export\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
