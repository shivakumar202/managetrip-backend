<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotels extends Model
{
    protected $fillable = [
        'name',
        'location',
        'star',
        'contact_info',
        'check_in_time',
        'check_out_time',
        'ceb',
        'status',
        'payment_preference',
    ];

    public function seasons()
    {
        return $this->hasMany(HotelSeasons::class, 'hotel_id');
    }



    public function extras()
    {
        return $this->hasMany(HotelExtras::class, 'hotel_id');
    }

    public function prices()
    {
        return $this->hasMany(HotelPrices::class, 'hotel_id');
    }

    public function roomTypes()
    {
        return $this->belongsToMany(RoomType::class, 'hotel_room_type', 'hotel_id', 'room_type_id');
    }
}
