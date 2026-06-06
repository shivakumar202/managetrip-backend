<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialServices extends Model
{
    protected $fillable = [
        'name',
        'description',
        'import_source',
        'updated_by',
    ];

    public function pricings()
    {
        return $this->hasMany(SpecialServicePricing::class, 'special_service_id');
    }
}
