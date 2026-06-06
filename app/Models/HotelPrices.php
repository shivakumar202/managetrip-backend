<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelPrices extends Model
{
    protected $fillable = [
        'hotel_id',
        'room_id',
        'meal_plan_id',
        'extra_id',
        'season_date_ranges_id',
        'price',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotels::class, 'hotel_id');
    }


    public function room()
    {
        return $this->belongsTo(HotelRooms::class, 'room_id');
    }

    public function mealPlan()
    {
        return $this->belongsTo(HotelMealPlans::class, 'meal_plan_id');
    }

    public function extra()
    {
        return $this->belongsTo(HotelExtras::class, 'extra_id');
    }

}
