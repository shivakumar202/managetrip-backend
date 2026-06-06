<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function rooms()
    {
        return $this->hasMany(HotelRoom::class);
    }

    public function prices()
    {
        return $this->hasMany(HotelPrice::class);
    }

    public function hotels()
    {
        return $this->belongsToMany(Hotels::class, 'hotel_room_type', 'room_type_id', 'hotel_id');
    }
}
