<?php

namespace App\Listeners;

use App\Events\UserActionLogged;
use App\Models\ActionLog;

class LogUserAction
{
    /**
     * Handle the event.
     *
     * @param  UserActionLogged  $event
     * @return void
     */
    public function handle(UserActionLogged $event)
    {
        ActionLog::create([
            'user_id' => $event->userId,
            'action' => $event->action,
            'details' => json_encode($event->details),
            'ip_address' => $event->ipAddress,
        ]);
    }
}
