<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->text('requirements')->nullable();
            $table->timestamps();

            $table->unique(['reservation_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_add_ons');
    }
};