<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel; // ✅ Use PrivateChannel
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmbulanceNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificationData;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->data['user_id']);
    }

    public function broadcastAs()
    {
        return 'ambulance.notification';
    }
   public function broadcastWith()
    {
        return $this->data;
    }

    public function broadcastUsing()
    {
        return new Pusher(
            '5ce11666c0482ff5931e', 
            'd81ac9209823a48842d3', 
            '1943997', 
            [
                'cluster' => 'mt1',
                'useTLS' => true
            ]
        );
    }
}
