<?php

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->nullable()->unique();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignId('requester_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('type', 30)->default(ReservationType::STANDARD->value);
            $table->date('reservation_date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('purpose', 500)->nullable();
            $table->unsignedInteger('attendee_count')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 30)->default(ReservationStatus::DRAFT->value);
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['requester_id', 'status']);
            $table->index(['location_id', 'status', 'reservation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
