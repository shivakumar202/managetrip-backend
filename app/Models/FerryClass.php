<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FerryClass extends Model
{
    protected $fillable = [
        'ferry_id',
        'class_name',
    ];

    public function ferry(): BelongsTo
    {
        return $this->belongsTo(Ferry::class);
    }
}
