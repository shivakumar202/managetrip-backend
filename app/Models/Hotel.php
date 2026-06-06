<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_group_id',
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

    public function hotelGroup()
    {
        return $this->belongsTo(HotelGroup::class, 'hotel_group_id');
    }

    public function rooms()
    {
        return $this->hasMany(HotelRoom::class);
    }

    public function prices()
    {
        return $this->hasMany(HotelPrice::class);
    }
}
