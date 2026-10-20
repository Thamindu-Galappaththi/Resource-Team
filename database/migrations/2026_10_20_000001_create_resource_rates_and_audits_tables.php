<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_type_id')->constrained('resource_types')->cascadeOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained('resources')->cascadeOnDelete();
            $table->decimal('base_fee', 12, 2);
            $table->decimal('base_duration_hours', 6, 2);
            $table->decimal('extra_hour_rate', 12, 2);
            $table->decimal('tax_percent', 6, 3)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['resource_type_id', 'resource_id', 'is_active', 'effective_from', 'effective_to'], 'resource_rates_lookup_idx');
        });

        Schema::create('resource_rate_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_rate_id')->nullable()->constrained('resource_rates')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['resource_rate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_rate_audits');
        Schema::dropIfExists('resource_rates');
    }
};
