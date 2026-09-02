<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the two columns confirmed missing via
     * Schema::getColumnListing('resources'):
     *   - location_id  (Tab 3 "Location" dropdown, FK -> locations)
     *   - status       (Tab 3 "Status" dropdown, plain string)
     *
     * Each addition is guarded with hasColumn() so this is safe to
     * run even if one of the two somehow already exists.
     */
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            if (!Schema::hasColumn('resources', 'location_id')) {
                $table->foreignId('location_id')
                    ->after('resource_type_id')
                    ->constrained('locations')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('resources', 'status')) {
                $table->string('status')
                    ->after('serial_number')
                    ->default('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            if (Schema::hasColumn('resources', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('resources', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
        });
    }
};