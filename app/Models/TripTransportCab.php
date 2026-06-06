<?php

namespace App\Models;

use App\Models\TripTransport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;

class TripTransportCab extends Model
{
    protected $table = 'trip_transpors_cabs';

    protected $fillable = [
        'trip_transport_id',
        'vehicle_id',
        'trip_day',
        'given_price',
        'travel_date',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'given_price' => 'decimal:2',
    ];

    public function tripTransport()
    {
        return $this->belongsTo(TripTransport::class, 'trip_transport_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
