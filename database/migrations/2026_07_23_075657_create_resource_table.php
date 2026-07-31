<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tab 3 (Resource). Note there is deliberately NO "category_id"
     * column here. The category is already implied by resource_type_id
     * (a type always belongs to exactly one category), so duplicating
     * it here would just be denormalized data that could drift out of
     * sync if a type's category ever changed. The "Category" dropdown
     * on Tab 3 is purely a UI convenience for filtering the "Type"
     * dropdown down to a manageable list — it's never actually sent
     * to, or stored by, this table.
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();

            // Which resource type this physical/logical resource is an
            // instance of. From this we can always resolve the category
            // via resources -> resource_types -> resource_categories.
            $table->foreignId('resource_type_id')
                ->constrained('resource_types')
                ->cascadeOnDelete();

            // "Resource Name / Model" field.
            $table->string('name_model');

            // "Serial Number" field. Unique because two physical
            // resources should never share the same serial number.
            $table->string('serial_number')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};