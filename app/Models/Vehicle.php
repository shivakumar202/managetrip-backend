<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'vehicle_type',
        'seating',
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    public function prices()
    {
        return $this->hasMany(DutyPrice::class);
    }

    public function duties()
    {
        return $this->belongsToMany(Duty::class, 'duty_prices')->withPivot('price', 'season_id', 'season_date_range_id');
    }

    public function tripTransports()
    {
        return $this->hasMany(TripTransportCab::class, 'vehicle_id');
    }
}
