<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelExtras extends Model
{
    protected $fillable = [
        'extra_name',
        'short_code',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotels::class);
    }
}
