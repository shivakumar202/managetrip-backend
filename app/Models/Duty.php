<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Duty extends Model
{
     protected $fillable = [
        'duty_code',
        'point_a',
        'point_b',  
        'service',
        'distance',
        'start_time',
        'duration',
        'day_schedule',
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    public function prices()
    {
        return $this->hasMany(DutyPrice::class);
    }
}
