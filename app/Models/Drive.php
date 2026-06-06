<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Drive extends Model
{
    //

    protected $table = 'drives';

    protected $fillable = [
        'table_name',
        'table_id',
        'file'
    ];

      public function getFileAttribute()
    {
        $fileValue = $this->attributes['file'] ?? null;
        
        if (!$fileValue) {
            return env('FRONTEND_URL') . '/images/default.jpg';
        }

        if ($this->table_name === 'updates') {
            return Storage::disk('frontend')->url($fileValue);
        }
        
        if (File::isFile('storage/' . $this->table_name . '/' . $fileValue)) {
            return asset('storage/' . $this->table_name . '/' . $fileValue . '?' . time());
        }
        
        return env('FRONTEND_URL') . '/images/default.jpg';
    }
}
