<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturer_payables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecture_session_id')
                ->constrained('lecture_sessions')
                ->restrictOnDelete();

            $table->foreignId('lecturer_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('rate_card_id')
                ->constrained('rate_cards')
                ->restrictOnDelete();

            // Snapshot of the values used when this payable was calculated
            $table->decimal('hours', 5, 2);

            $table->unsignedBigInteger('hourly_rate_minor');

            $table->unsignedBigInteger('net_amount_minor');

            $table->char('currency', 3)->default('LKR');

            /*
             * Stores the actual rate-rule information used for this
             * payable so future rate changes do not rewrite history.
             */
            $table->json('rate_snapshot');

            // Payment processing status
            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index('lecturer_id');
            $table->index('rate_card_id');
            $table->index('status');

            $table->unique('lecture_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_payables');
    }
};