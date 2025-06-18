<?php

namespace App\Listeners;

use App\Events\LocationUpdate;
use Illuminate\Support\Facades\Log;

class LocationUpdateListener
{
    public function handle(LocationUpdate $event)
    {
        $data = $event->data;

        // Process or store location data
        Log::info('📍 Received Location Update:', $data);

        // Example: Save to database
        \App\Models\Driver::updateOrCreate(
            ['user_id' => $data['user_id']],
            [
                'current_lat' => $data['latitude'],
                'current_long' => $data['longitude'],
            ]
        );
    }
}
