<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeasonDateRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
    ];


    public function hotelPrices()
    {
        return $this->hasMany(HotelPrice::class, 'season_date_ranges_id');
    }

    public function HotelPriceExtras()
    {
        return $this->belongsTo(HotelPriceExtra::class, 'season_date_ranges_id');
    }
    public function activityPrices()
    {
        return $this->hasMany(ActivityPrices::class, 'season_date_range_id');
    }
    public function ferryPrices()
    {
        return $this->hasMany(FerryPricing::class, 'season_date_range_id');
    }
}
