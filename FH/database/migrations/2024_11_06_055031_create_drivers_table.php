<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number');
            $table->string('aadhar_number')->nullable();
            $table->string('license_number');
            $table->string('guarantor_name');
            $table->string('guarantor_phone_number');
            $table->timestamp('license_issue_date');
            $table->timestamp('license_valid_from');
            $table->timestamp('license_valid_upto');
            $table->string('driver_country_code');
            $table->string('guarantor_country_code');
            $table->string('rfid_tracking_id')->nullable();
            $table->boolean('active')->default(true);
            $table->string('vehicle_number');
            $table->string('shift')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
