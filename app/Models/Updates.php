<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Updates extends Model
{
    protected $table = 'updates';

    protected $fillable = [
        'title',
        'description',
        'status'
    ];

    public function getStatusBadgeAttribute()
    {
        return $this->status === 'issue' ? 'badge badge-danger' : 'badge badge-success';
    }

    public function Images()
    {
        return $this->hasMany(Drive::class, 'table_id')->where('table_name', 'updates');
    }

    public function comments()
    {
        return $this->hasMany(UpdateComment::class, 'update_id');
    }
}

