<?php

namespace App\Models;

use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\Quotes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TripHotel extends Model
{
    protected $table = 'trip_hotels';

    protected $fillable = [
        'quote_id',
        'hotel_id',
        'room_type_id',
        'room_count',
        'night',
        'night_count',
        'total_pax',
        'adults',
        'children',
        'infants',
        'aweb',
        'cweb',
        'cnb',
        'check_in',
        'check_out',
        'given_price',
        'given_aweb_price',
        'given_cweb_price',
        'given_cnb_price',
        'updated_by',
        'remarks',
        'status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'given_price' => 'decimal:2',
        'given_aweb_price' => 'decimal:2',
        'given_cweb_price' => 'decimal:2',
        'given_cnb_price' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType()
    {
        return $this->belongsTo(HotelRoom::class, 'room_type_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
