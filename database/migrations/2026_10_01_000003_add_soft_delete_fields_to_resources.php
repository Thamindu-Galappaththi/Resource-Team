<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            if (! Schema::hasColumn('resources', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false);
            }
            if (! Schema::hasColumn('resources', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $columns = array_values(array_filter(['is_deleted', 'deleted_at'], fn ($column) => Schema::hasColumn('resources', $column)));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
