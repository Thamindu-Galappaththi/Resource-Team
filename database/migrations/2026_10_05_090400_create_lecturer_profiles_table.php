<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturer_profiles', function (Blueprint $table) {
            $table->id();

            // Link the lecturer profile to a Human Resource record
            $table->foreignId('resource_id')
                ->unique()
                ->constrained('resources')
                ->restrictOnDelete();

            // Lecturer information
            $table->string('name');
            $table->string('service_id')->unique();
            $table->string('nic')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('designation')->nullable();
            $table->boolean('slt_employee')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_profiles');
    }
};
