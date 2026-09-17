<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->date('reservation_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('special_requirements')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['resource_id', 'reservation_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};