<?php

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_ref')->unique();
            $table->string('reservation_name');
            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->string('meal_type')->default(MealType::LUNCH->value);
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->unsignedInteger('number_of_orders');
            $table->text('order_details')->nullable();
            $table->text('special_remarks')->nullable();
            $table->string('status')->default(CanteenReservationStatus::PENDING->value);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->text('approval_comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_reservations');
    }
};
