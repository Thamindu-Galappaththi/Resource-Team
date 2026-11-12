<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained()->restrictOnDelete();
            $table->string('invoice_number', 40)->unique()->nullable();
            $table->string('status', 20)->default('unpaid')->index();
            $table->decimal('extra_hours', 5, 2)->default(0);
            $table->json('fee_breakdown');
            $table->timestamp('issued_at');
            $table->timestamp('paid_at')->nullable();
            $table->date('payment_date')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_payment_invoices');
    }
};
