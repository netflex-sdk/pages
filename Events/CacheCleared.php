<?php

namespace Netflex\Pages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CacheCleared
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?string $key;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(?string $key = null)
    {
        $this->key = $key;
    }
}
