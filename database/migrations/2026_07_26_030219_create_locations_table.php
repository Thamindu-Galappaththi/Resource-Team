<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Standalone lookup table for the "Location" dropdown on Tab 3.
     * Kept intentionally minimal (just a name) since no creation form
     * exists for it yet — rows are added via LocationSeeder (or
     * directly) until a management screen is built. No foreign keys
     * of its own.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            // e.g. "Welisara", "Moratuwa", "Peradeniya". Unique so the
            // same location can't be duplicated by mistake.
            $table->string('name')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};