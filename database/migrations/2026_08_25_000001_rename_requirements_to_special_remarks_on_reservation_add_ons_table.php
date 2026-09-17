<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_add_ons', function (Blueprint $table) {
            $table->renameColumn('requirements', 'special_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_add_ons', function (Blueprint $table) {
            $table->renameColumn('special_remarks', 'requirements');
        });
    }
};