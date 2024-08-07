<?php

namespace Netflex\Pages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an externally requested existing cache key has been cleared.
 */
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
