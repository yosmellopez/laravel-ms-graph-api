<?php

namespace Ylplabs\LaravelMsGraphApi\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Microsoft365APISignInEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }
}
