<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyPrice extends Model
{
     protected $fillable = [
        'duty_id',
        'vehicle_id',
        'season_date_range_id',
        'price'
    ];

    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function seasonDateRange()
    {
        return $this->belongsTo(SeasonDateRange::class, 'season_date_range_id');
    }

    
}
