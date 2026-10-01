<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('resources')->whereNull('serial_number')->exists()) {
            throw new RuntimeException('Cannot restore the required serial number column while resources have no serial number.');
        }

        Schema::table('resources', function (Blueprint $table) {
            $table->string('serial_number')->nullable(false)->change();
        });
    }
};
