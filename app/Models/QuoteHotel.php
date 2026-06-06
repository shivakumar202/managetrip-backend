<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteHotel extends Model
{
    protected $table = 'quote_hotels';

    protected $fillable = [
        'quote_id',
        'hotel_id',
        'room_type_id',
        'meal_plan',
        'rooms',
        'aweb',
        'cweb',
        'cnb',
        'stay_nights',
        'pricing',
        'total_price',
        'category',
        'remarks',
        'comments',
        'status',
        'updated_by',
    ];

    protected $casts = [
        'stay_nights' => 'array',
        'pricing' => 'array',
        'total_price' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quotes::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotels::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}
