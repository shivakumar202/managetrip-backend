<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelPriceExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'extra_id',
        'meal_plan_id',
        'season_date_ranges_id',
        'price',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function extra()
    {
        return $this->belongsTo(Extra::class);
    }

 

    public function dateRanges()
    {
        return $this->belongsTo(SeasonDateRange::class, 'season_date_ranges_id');
    }
}
