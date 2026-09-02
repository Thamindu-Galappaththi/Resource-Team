<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('service_id')->nullable()->after('name');
            $table->string('nic')->nullable()->after('service_id');
            $table->string('location')->nullable()->after('email');
            $table->string('designation')->nullable()->after('user_role');
            $table->string('user_type')->nullable()->after('designation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'nic', 'location', 'designation', 'user_type']);
        });
    }
};
