<?php

namespace Netflex\Pages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when external cache clearing request is received
 */
class CacheStoreClearRequest
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?string $key;
    public Request $request;

    public function __construct(string $key, Request $request)
    {
        $this->key = $key;
        $this->request = $request;
    }
}
