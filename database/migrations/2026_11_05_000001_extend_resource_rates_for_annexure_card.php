<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_rates', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('resource_id')->constrained('locations')->nullOnDelete();
            $table->string('resource_name', 190)->default('')->after('location_id');
            $table->decimal('daily_rate', 12, 2)->nullable()->after('resource_name');
            $table->decimal('half_day_rate', 12, 2)->nullable()->after('daily_rate');
            $table->string('photo_path')->nullable()->after('half_day_rate');
            $table->index(['location_id', 'resource_name', 'is_active', 'effective_from'], 'resource_rates_annexure_lookup_idx');
        });

        DB::table('resource_rates')->update([
            'daily_rate' => DB::raw('base_fee'),
            'half_day_rate' => DB::raw('base_fee'),
            'is_active' => false,
        ]);

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('booking_type', 30)->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('booking_type');
        });
        Schema::table('resource_rates', function (Blueprint $table) {
            $table->dropIndex('resource_rates_annexure_lookup_idx');
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['resource_name', 'daily_rate', 'half_day_rate', 'photo_path']);
        });
    }
};
