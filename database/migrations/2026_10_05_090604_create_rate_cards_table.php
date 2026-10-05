<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            // Example: LKR
            $table->char('currency', 3)->default('LKR');

            // Version/effective period of this rate card
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['effective_from', 'effective_to']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};