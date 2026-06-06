<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FerryRoutes extends Model
{
    protected $table = 'ferry_routes';

    protected $fillable = [
        'from',
        'to',
    ];

    public function ferries(): HasMany
    {
        return $this->hasMany(FerryRoute::class, 'route_id');
    }

    public function pricings(): HasMany
    {
        return $this->hasMany(FerryPricing::class, 'route_id');
    }
}
