<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FerryRoute extends Model
{
    protected $table = 'ferry_route';

    protected $fillable = [
        'ferry_id',
        'route_id',
        'status',
    ];

    public function ferry(): BelongsTo
    {
        return $this->belongsTo(Ferry::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(FerryRoutes::class, 'route_id');
    }
}
