<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityPrices extends Model
{
    protected $fillable = [
        'activity_id',
        'season_date_range_id',
        'pax_category_id',
        'price',
    ];

    public function activity()
    {
        return $this->belongsTo(Activities::class);
    }

    public function seasonDateRange()
    {
        return $this->belongsTo(SeasonDateRange::class);
    }

    public function paxCategory()
    {
        return $this->belongsTo(PaxCategories::class);
    }
}
