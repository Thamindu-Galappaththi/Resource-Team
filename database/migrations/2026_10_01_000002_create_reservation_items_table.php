<?php

use App\Enums\ReservationItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->restrictOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 30)->default(ReservationItemStatus::REQUESTED->value);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('resource_name_snapshot', 180);
            $table->json('rate_snapshot')->nullable();
            $table->timestamps();

            $table->index(['resource_id', 'starts_at', 'ends_at', 'status']);
            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_items');
    }
};
