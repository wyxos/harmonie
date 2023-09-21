<?php

namespace Wyxos\Harmonie\Import\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wyxos\Harmonie\Import\Models\ImportLog;

class RowImported implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ImportLog $log;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ImportLog $log)
    {
        $this->log = $log;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return [new PrivateChannel('imports')];
    }
}
