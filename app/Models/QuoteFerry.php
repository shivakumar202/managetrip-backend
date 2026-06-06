<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteFerry extends Model
{
    protected $table = 'quote_ferries';

    protected $fillable = [
        'quote_id',
        'day',
        'date',
        'route',
        'time',
        'duration',
        'pricing',
        'ferry',
        'total_price',
        'remarks',
        'comments',
        'pnr_no',
        'status',
        'updated_by',
    ];

    protected $casts = [
        'day' => 'integer',
        'date' => 'date',
        'route' => 'string',
        'time' => 'string',
        'duration' => 'integer',
        'pricing' => 'array',
        'ferry' => 'string',
        'total_price' => 'decimal:2',
        'remarks' => 'string',
        'comments' => 'string',
        'pnr_no' => 'string',
        'status' => 'integer', // 0: Pending, 1: Confirmed, 2: Cancelled
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quotes::class);
    }
}
