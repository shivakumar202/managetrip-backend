<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    //
    protected $table = 'queries';

    protected $fillable = [
        'query_id',
        'source',
        'destination',
        'reference_id',
        'sales_team_id',
        'tag_id',
        'source_contact_person',
        'start_date',
        'nights',
        'adults',
        'children',
        'children_ages',
        'salutation',
        'name',
        'email',
        'phone',
        'origin',
        'nationality',
        'comments',
        'created_by',
        'updated_by',
        'deleted_at',
        'status',
    ];

    public function quotes()
    {
        return $this->hasMany(Quotes::class, 'query_id');
    }
}
