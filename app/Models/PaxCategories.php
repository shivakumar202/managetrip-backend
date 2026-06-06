<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaxCategories extends Model
{
    protected $fillable = [
        'category',
        'name',
        'start_age',
        'end_age',
    ];

    public function activityPrices()
    {
        return $this->hasMany(ActivityPrices::class);
    }

    public function ferryPricings()
    {
        return $this->hasMany(FerryPricing::class);
    }
}
