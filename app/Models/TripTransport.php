<?php

namespace App\Models;

use App\Models\Duty;
use App\Models\Quotes;
use App\Models\TripTransportCab;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TripTransport extends Model
{
    protected $table = 'trip_transports';

    protected $fillable = [
        'quote_id',
        'duty_id',
        'travel_date',
        'trip_day',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'travel_date' => 'date',
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class);
    }

    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }

    public function cabs()
    {
        return $this->hasMany(TripTransportCab::class, 'trip_transport_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
