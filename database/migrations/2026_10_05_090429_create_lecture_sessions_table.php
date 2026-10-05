<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecture_sessions', function (Blueprint $table) {
            $table->id();

            // Lecturer who delivered the session
            $table->foreignId('lecturer_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Session information
            $table->date('session_date');
            $table->decimal('hours', 5, 2);

            // Information used when determining the applicable rate
            $table->string('qualification')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();

            // Session status
            $table->string('status')->default('completed');

            $table->timestamps();

            // Useful for filtering monthly lecture sessions
            $table->index('session_date');
            $table->index('department');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecture_sessions');
    }
};