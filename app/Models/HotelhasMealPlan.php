<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelhasMealPlan extends Model
{
    protected $fillable = [
        'hotel_id',
        'meal_plan_id',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotels::class);
    }

    public function mealPlan()
    {
        return $this->belongsTo(HotelMealPlans::class, 'meal_plan_id');
    }
}
