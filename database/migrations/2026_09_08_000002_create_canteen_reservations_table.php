<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code')->unique();
            $table->foreignId('canteen_id')->nullable()->constrained('canteens')->nullOnDelete();
            $table->string('reservation_name');
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->string('meal_type');
            $table->integer('number_of_orders')->unsigned();
            $table->text('order_details');
            $table->text('special_requirements')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reservation_date']);
            $table->index(['meal_type']);
            $table->index(['status']);
            $table->index(['canteen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_reservations');
    }
};
