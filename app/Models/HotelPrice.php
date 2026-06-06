<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'meal_plan_id',
        'season_date_ranges_id',
        'base_price',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function mealPlan()
    {
        return $this->belongsTo(MealPlan::class);
    }



    public function extras()
    {

        return $this->hasMany(HotelPriceExtra::class, 'hotel_id', 'hotel_id');
    }

    public function seasonDateRange()
    {
        return $this->belongsTo(SeasonDateRange::class, 'season_date_ranges_id');
    }
}
