<?php

namespace Wyxos\Harmonie\Import\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wyxos\Harmonie\Import\Events\ImportUpdated;

/**
 * @property int id
 * @property string path
 * @property string filename
 * @property int value
 * @property int max
 * @property int user_id
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Import extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function successfulLogs(): HasMany
    {
        return $this->logs()
            ->where('status', 'success');
    }

    public function errorLogs(): HasMany
    {
        return $this->logs()
            ->where('status', 'error');
    }

    public function updateAndBroadcast(array $array = null): static
    {
        if ($array) {
            $this->update($array);
        }

        $this->refresh();

        event(new ImportUpdated($this));

        return $this;
    }
}
