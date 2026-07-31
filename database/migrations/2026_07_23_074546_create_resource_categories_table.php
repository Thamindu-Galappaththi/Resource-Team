<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This is the top-level table for Tab 1 (Resource Category).
     * Everything else (types, resources) eventually traces back to a row
     * in this table, so it has no foreign keys of its own.
     */
    public function up(): void
    {
        Schema::create('resource_categories', function (Blueprint $table) {
            $table->id();

            // The "Category Name" text field from the Tab 1 form.
            // Kept unique so the same category can't be created twice by mistake.
            $table->string('name')->unique();

            $table->timestamps(); // created_at / updated_at, useful for auditing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_categories');
    }
};