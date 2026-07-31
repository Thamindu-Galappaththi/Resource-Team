<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the dynamic "Additional Features" rows added under Tab 1.
     * Each row belongs to exactly ONE category (1 category -> many features),
     * so this is a simple hasMany child table, not a pivot.
     */
    public function up(): void
    {
        Schema::create('category_features', function (Blueprint $table) {
            $table->id();

            // Foreign key back to the parent category.
            // constrained() assumes the column name "resource_category_id"
            // matches the "id" column on "resource_categories" (Laravel's
            // default convention), so no extra arguments are needed.
            // cascadeOnDelete() means: if a category is deleted, its
            // feature rows are deleted automatically instead of blocking
            // the delete or leaving orphaned rows.
            $table->foreignId('resource_category_id')
                ->constrained('resource_categories')
                ->cascadeOnDelete();

            // The "Feature Name" input on each row. Nullable — this
            // field is no longer required, so a feature row can be
            // saved with a blank name.
            $table->string('name')->nullable();

            // The toggle switch. true = "on" = the options field was
            // enabled/editable in the UI. false = "off" = disabled.
            $table->boolean('enabled')->default(false);

            // The "Options" input. Nullable because it's only meaningful
            // (and only ever filled in) when enabled = true. We still
            // store it even if enabled later gets switched off, so the
            // previously-entered value isn't lost — the frontend is what
            // enforces "disabled when off", not the database.
            $table->string('options')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_features');
    }
};