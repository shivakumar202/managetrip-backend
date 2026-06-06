<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SpecialServices;

class QuoteSpecialService extends Model
{
    protected $table = 'quote_special_services';

    protected $fillable = [
        'quote_id',
        'type',
        'service_id',
        'service_name',
        'day',
        'notes',
        'pricing',
        'total_price',
    ];

    protected $casts = [
        'pricing' => 'array',
        'total_price' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quotes::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(SpecialServices::class, 'service_id');
    }
}
