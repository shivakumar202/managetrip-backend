<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelMealPlans extends Model
{
    protected $fillable = [
        'meal_plan_name',
    ];

    public function hotels()
    {
        return $this->belongsToMany(Hotels::class, 'hotelhas_meal_plans');
    }
}
