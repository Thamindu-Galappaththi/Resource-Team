<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->foreignId('linked_resource_id')->constrained('resources')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['resource_id', 'linked_resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_links');
    }
};
