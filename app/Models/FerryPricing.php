<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FerryPricing extends Model
{
    protected $fillable = [
        'ferry_id',
        'route_id',
        'season_date_range_id',
        'class_id',
        'pax_id',
        'departure',
        'price',
    ];

    public function ferry(): BelongsTo
    {
        return $this->belongsTo(Ferry::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(FerryRoutes::class, 'route_id');
    }

    public function seasonDateRange()
    {
        return $this->belongsTo(SeasonDateRange::class);
    }
    public function paxCategory()
    {
        return $this->belongsTo(PaxCategories::class, 'pax_id');
    }
    public function class(): BelongsTo
    {
        return $this->belongsTo(FerryClass::class, 'class_id');
    }
}
