<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_request_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hostel_student_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('student_name', 150);
            $table->string('student_id', 50);
            $table->date('check_in_date');
            $table->time('check_in_time');
            $table->date('check_out_date');
            $table->time('check_out_time');
            $table->string('home_town', 120);
            $table->string('phone', 20);
            $table->string('emergency_contact_name', 150);
            $table->string('emergency_contact_phone', 20);
            $table->string('status', 20)->default('pending');
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('sms_status', 20)->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_student_requests');
        Schema::dropIfExists('hostel_request_links');
    }
};
