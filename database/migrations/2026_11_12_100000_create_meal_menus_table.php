<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('image_path');
            $table->timestamps();
        });

        Schema::table('canteen_reservations', function (Blueprint $table) {
            $table->foreignId('meal_menu_id')->nullable()->after('meal_type')->constrained('meal_menus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('canteen_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meal_menu_id');
        });
        Schema::dropIfExists('meal_menus');
    }
};
