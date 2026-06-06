<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialServicePricing extends Model
{
    protected $fillable = [
        'special_service_id',
        'price',
        'season_date_range_id',
    ];

    public function specialService()
    {
        return $this->belongsTo(SpecialServices::class, 'special_service_id');
    }

    public function seasonDateRange()
    {
        return $this->belongsTo(SeasonDateRange::class);
    }
}
