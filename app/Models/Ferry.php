<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ferry extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'capacity',
        'operator',
        'status',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(FerryClass::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(FerryRoute::class);
    }

    public function pricings(): HasMany
    {
        return $this->hasMany(FerryPricing::class);
    }
}
