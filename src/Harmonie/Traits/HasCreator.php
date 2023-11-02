<?php

namespace Wyxos\Harmonie\Harmonie\Traits;

/**
 * @property int creator_id
 *
 * @property App\Models\User creator
 */
trait HasCreator
{
    public function creator()
    {
        return $this->belongsTo(App\Models\User::class, 'creator_id');
    }
}
