<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement("ALTER TABLE roaster_mapping MODIFY COLUMN driver_status ENUM('Online', 'Offline', 'Busy') DEFAULT 'Online'");
        DB::statement("ALTER TABLE roaster_mapping MODIFY COLUMN ride_status ENUM('Complete', 'Dropped Off', 'Picked Up', 'Arrived', 'On the way') DEFAULT 'On the way'");
    }

    public function down()
    {
        // If rollback is needed, update with previous ENUM values
        DB::statement("ALTER TABLE roaster_mapping MODIFY COLUMN driver_status ENUM('available', 'unavailable', 'on_trip') DEFAULT 'available'");
        DB::statement("ALTER TABLE roaster_mapping MODIFY COLUMN ride_status ENUM('pending', 'ongoing', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};

