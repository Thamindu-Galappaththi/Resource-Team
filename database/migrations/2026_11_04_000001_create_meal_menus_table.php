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
            $table->string('name', 120);
            $table->date('service_date');
            $table->string('main_dish', 120);
            $table->string('dish_one', 120);
            $table->string('dish_two', 120);
            $table->string('dish_three', 120);
            $table->string('dish_four', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_menus');
    }
};
