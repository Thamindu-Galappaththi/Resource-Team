<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tab 2 (Resource Type). Each type belongs to exactly one category
     * (the "Select Category" dropdown on that tab), so this table just
     * needs a single foreign key back to resource_categories.
     */
    public function up(): void
    {
        Schema::create('resource_types', function (Blueprint $table) {
            $table->id();

            // Which category this type was created under.
            $table->foreignId('resource_category_id')
                ->constrained('resource_categories')
                ->cascadeOnDelete();

            // The "Resource Type" text field.
            $table->string('name');

            // The "Description" textarea. Nullable since the form
            // doesn't mark it as required.
            $table->text('description')->nullable();

            $table->timestamps();

            // A type name should be unique WITHIN a category, but the
            // same type name is fine to reuse across two different
            // categories (e.g. "Laptop" under both "IT Equipment" and
            // "Warehouse Assets"). A composite unique index enforces
            // exactly that.
            $table->unique(['resource_category_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_types');
    }
};