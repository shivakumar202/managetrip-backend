<?php

namespace App\Models;

use App\Models\Quotes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    protected $table = 'extra_services';

    protected $fillable = [
        'quote_id',
        'service_name',
        'service_date',
        'trip_day',
        'given_price',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'given_price' => 'decimal:2',
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
