<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteFlight extends Model
{
    protected $table = 'quote_flights';

    protected $fillable = [
        'quote_id',
        'flight_number',
        'departure_airport',
        'arrival_airport',
        'travel_class',
        'departure_time',
        'arrival_time',
        'price',
        'remarks',
        'comments',
        'pnr_no',
        'status',
        'updated_by',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'price' => 'decimal:2',
        'remarks' => 'string',
        'comments' => 'string',
        'pnr_no' => 'string',
        'status' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quotes::class);
    }
}
