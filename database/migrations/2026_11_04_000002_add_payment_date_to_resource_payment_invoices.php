<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_payment_invoices', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('resource_payment_invoices', function (Blueprint $table) {
            $table->dropColumn('payment_date');
        });
    }
};
