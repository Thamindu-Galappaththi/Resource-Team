<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTableIfMissing('resource_payment_discount_rules', function (Blueprint $table) {
            $table->id();
            $table->string('customer_category')->unique();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        }, ['id', 'customer_category', 'discount_percent', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at']);

        $this->createTableIfMissing('resource_payment_discount_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_rule_id')->nullable()->constrained('resource_payment_discount_rules')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['discount_rule_id', 'created_at']);
        }, ['id', 'discount_rule_id', 'actor_id', 'action', 'old_values', 'new_values', 'ip_address', 'created_at', 'updated_at']);
    }

    private function createTableIfMissing(string $tableName, Closure $definition, array $expectedColumns): void
    {
        if (Schema::hasTable($tableName)) {
            if (! Schema::hasColumns($tableName, $expectedColumns)) {
                throw new RuntimeException("The existing {$tableName} table is incomplete; inspect it before rerunning this migration.");
            }

            return;
        }

        Schema::create($tableName, $definition);
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_payment_discount_audits');
        Schema::dropIfExists('resource_payment_discount_rules');
    }
};
