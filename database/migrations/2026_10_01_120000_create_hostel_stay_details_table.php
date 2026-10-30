<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_stay_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained('reservations')->restrictOnDelete();
            $table->string('guest_title', 10)->nullable();
            $table->string('guest_name', 150);
            $table->json('employee_ids')->nullable();
            $table->text('guest_phone_encrypted')->nullable();
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at');
            $table->unsignedInteger('number_of_guests')->default(1);
            $table->text('special_requirements')->nullable();
            $table->timestamps();

            $table->index(['check_in_at', 'check_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_stay_details');
    }
};
