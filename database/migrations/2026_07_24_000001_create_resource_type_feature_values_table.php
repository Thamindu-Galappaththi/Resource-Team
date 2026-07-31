<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_type_feature_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_type_id')
                ->constrained('resource_types')
                ->cascadeOnDelete();

            $table->foreignId('category_feature_id')
                ->constrained('category_features')
                ->cascadeOnDelete();

            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['resource_type_id', 'category_feature_id'], 'type_feature_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_type_feature_values');
    }
};
