<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscription_masters')->onDelete('cascade');
            $table->foreignId('benefit_id')->constrained('benefit_masters')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_benefits');
    }
};
