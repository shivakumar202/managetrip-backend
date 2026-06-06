<?php

namespace App\Models;

use App\Models\Activities;
use App\Models\Quotes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TripActivity extends Model
{
    protected $table = 'trip_activities';

    protected $fillable = [
        'quote_id',
        'activity_id',
        'activity_date',
        'trip_day',
        'adults',
        'children',
        'infants',
        'activity_time',
        'duration',
        'adult_given_price',
        'child_given_price',
        'infant_given_price',
        'total_price',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'activity_time' => 'time',
        'adult_given_price' => 'decimal:2',
        'child_given_price' => 'decimal:2',
        'infant_given_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activities::class, 'activity_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
