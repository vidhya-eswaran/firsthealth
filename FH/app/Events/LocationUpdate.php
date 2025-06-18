<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
        
        //Log::info('🚨 LocationUpdate Event Triggered:', $data);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('location.' . $this->data['user_id']);
    }

    public function broadcastAs()
    {
        return 'location.update';
    }

    public function broadcastWith()
    {
        return $this->data;
    }
}
