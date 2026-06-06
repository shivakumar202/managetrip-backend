<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteTransport extends Model
{
    protected $table = 'quote_transports';

    protected $fillable = [
        'quote_id',
        'day',
        'date',
        'service_location',
        'service',
        'time',
        'duration',
        'pricing',
        'total_price',
    ];

    protected $casts = [
        'day' => 'integer',
        'date' => 'date',
        'service_location' => 'string',
        'service' => 'string',
        'time' => 'string',
        'duration' => 'integer',
        'pricing' => 'array',
        'total_price' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quotes::class);
    }
}
