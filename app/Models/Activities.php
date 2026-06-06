<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activities extends Model
{
    protected $fillable = [
        'name',
        'service',
        'description',
        'open_time',
        'close_time',
        'duration',
        'slots',
        'created_by',
        'updated_by',
    ];

 public function prices()
 {
    return $this->hasMany(ActivityPrices::class, 'activity_id');
 }
}
