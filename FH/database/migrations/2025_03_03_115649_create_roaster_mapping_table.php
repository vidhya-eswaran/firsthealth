<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('roaster_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('hospital');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('paramedic_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('driver_name');
            $table->string('vehicle');
            $table->enum('driver_status', ['Online', 'Offline', 'Busy'])->default('Online');
            $table->enum('ride_status', ['Complete', 'Dropped Off', 'Picked Up', 'Arrived', 'On the way'])->default('On the way');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('roaster_mapping');
    }
};
