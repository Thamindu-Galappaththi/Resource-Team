<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        DB::table('users')
            ->whereNotNull('role_id')
            ->orderBy('id')
            ->eachById(function (object $user): void {
                DB::table('role_user')->insertOrIgnore([
                    'role_id' => $user->role_id,
                    'user_id' => $user->id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
