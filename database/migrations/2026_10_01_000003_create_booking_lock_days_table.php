<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_lock_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->date('lock_date');
            $table->timestamps();
            $table->unique(['resource_id', 'lock_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_lock_days');
    }
};
