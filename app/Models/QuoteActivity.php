<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteActivity extends Model
{
    protected $table = 'quote_activities';

    protected $fillable = [
        'quote_id',
        'ticket_no',
        'status',
        'updated_by',
        'remarks',
        'comments',
        'day',
        'date',
        'activity_location',
        'activity',
        'time',
        'duration',
        'pricing',
        'total_price',
    ];

    protected $casts = [
        'day' => 'integer',
        'date' => 'date',
        'activity_location' => 'string',
        'activity' => 'string',
        'time' => 'string',
        'duration' => 'integer',
        'pricing' => 'array',
        'total_price' => 'decimal:2',
        'remarks' => 'string',
        'comments' => 'string',
        'ticket_no' => 'string',
        'status' => 'integer', // 0: Pending, 1: Confirmed, 2: Cancelled
        'updated_by' => 'string',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quotes::class);
    }
}
