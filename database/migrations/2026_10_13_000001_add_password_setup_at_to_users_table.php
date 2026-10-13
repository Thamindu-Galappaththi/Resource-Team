<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password_setup_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_setup_at')->nullable()->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'password_setup_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_setup_at');
            });
        }
    }
};
