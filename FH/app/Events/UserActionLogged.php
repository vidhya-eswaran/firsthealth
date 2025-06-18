<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActionLogged
{
    use Dispatchable, SerializesModels;

    public $userId;
    public $action;
    public $details;
    public $ipAddress;

    /**
     * Create a new event instance.
     *
     * @param int|null $userId
     * @param string $action
     * @param array|string|null $details
     * @param string|null $ipAddress
     */
    public function __construct($userId, $action, $details = null, $ipAddress = null)
    {
        $this->userId = $userId;
        $this->action = $action;
        $this->details = $details;
        $this->ipAddress = $ipAddress;
    }
}
