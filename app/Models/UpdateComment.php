<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateComment extends Model
{
    protected $table = 'update_comments';

    protected $fillable = [
        'update_id',
        'comment',
    ];

    public function updates()
    {
        return $this->belongsTo(Updates::class, 'update_id');
    }
}
