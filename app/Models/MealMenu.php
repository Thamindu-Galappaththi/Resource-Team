<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealMenu extends Model
{
    protected $fillable = ['name', 'service_date', 'main_dish', 'dish_one', 'dish_two', 'dish_three', 'dish_four', 'image_path'];

    protected $casts = ['service_date' => 'date'];
}
