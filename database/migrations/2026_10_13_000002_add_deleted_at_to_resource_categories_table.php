<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('resource_categories', 'deleted_at')) {
            Schema::table('resource_categories', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('resource_categories', 'deleted_at')) {
            Schema::table('resource_categories', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
