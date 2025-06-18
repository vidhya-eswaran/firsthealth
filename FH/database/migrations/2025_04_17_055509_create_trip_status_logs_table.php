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
        Schema::create('trip_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id'); // FK to your trip table
            $table->enum('status', ['On the way', 'Arrived', 'Picked Up', 'Dropped Off', 'Complete']);
            $table->timestamp('status_updated_at'); // When this status was recorded
            $table->integer('time_taken')->nullable(); // In minutes (optional, for completed trips)
            $table->timestamps();
    
            // Add foreign key constraint if needed
           // $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_status_logs');
    }
};
