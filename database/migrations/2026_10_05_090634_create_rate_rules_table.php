<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rate_card_id')
                ->constrained('rate_cards')
                ->cascadeOnDelete();

            /*
             * Conditions used to determine which rate applies.
             * These remain nullable because the exact Finance-approved
             * rate formula has not yet been defined.
             */
            $table->string('qualification')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('user_type')->nullable();

            /*
             * Money is stored in minor units.
             * Example:
             * LKR 1,500.00 = 150000
             *
             * The exact scale must be documented consistently
             * throughout the payment module.
             */
            $table->unsignedBigInteger('hourly_rate_minor');

            // Additional Finance-approved conditions can be stored here.
            $table->json('conditions')->nullable();

            $table->timestamps();

            $table->index('rate_card_id');
            $table->index('qualification');
            $table->index('department');
            $table->index('location');
            $table->index('user_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_rules');
    }
};