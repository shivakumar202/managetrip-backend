<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelRooms extends Model
{
    protected $fillable = [
        'room_name',
        'description',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotels::class, 'hotel_id');
    }
}
