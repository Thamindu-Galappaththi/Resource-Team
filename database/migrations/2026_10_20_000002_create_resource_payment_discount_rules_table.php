<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resource_payment_discount_rules')) {
            Schema::create('resource_payment_discount_rules', function (Blueprint $table) {
                $table->id();
                $table->string('customer_category', 80)->unique();
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('resource_payment_discount_audits')) {
            Schema::create('resource_payment_discount_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_rule_id')->nullable()->constrained('resource_payment_discount_rules')->nullOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 32);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                $table->index(['discount_rule_id', 'created_at'], 'rp_discount_audits_lookup_idx');
            });
        } else {
            Schema::whenTableDoesntHaveIndex(
                'resource_payment_discount_audits',
                ['discount_rule_id', 'created_at'],
                function (Blueprint $table) {
                    $table->index(['discount_rule_id', 'created_at'], 'rp_discount_audits_lookup_idx');
                }
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_payment_discount_audits');
        Schema::dropIfExists('resource_payment_discount_rules');
    }
};
