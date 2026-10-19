<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });

        Schema::table('lecturer_payables', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });

        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->foreign('lecturer_id')
                ->references('id')
                ->on('lecturer_profiles')
                ->restrictOnDelete();
        });

        Schema::table('lecturer_payables', function (Blueprint $table) {
            $table->foreign('lecturer_id')
                ->references('id')
                ->on('lecturer_profiles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lecturer_payables', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });

        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });

        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->foreign('lecturer_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('lecturer_payables', function (Blueprint $table) {
            $table->foreign('lecturer_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};