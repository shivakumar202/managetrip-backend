<?php

namespace App\Models;

use App\Models\Quotes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TripFlight extends Model
{
    protected $table = 'trip_flights';

    protected $fillable = [
        'quote_id',
        'trip_type',
        'airline_name',
        'flight_number',
        'departure_airport',
        'arrival_airport',
        'departure_date',
        'arrival_date',
        'departure_time',
        'arrival_time',
        'adults',
        'children',
        'infants',
        'adult_given_price',
        'child_given_price',
        'infant_given_price',
        'total_price',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'arrival_date' => 'date',
        'departure_time' => 'time',
        'arrival_time' => 'time',
        'adult_given_price' => 'decimal:2',
        'child_given_price' => 'decimal:2',
        'infant_given_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
