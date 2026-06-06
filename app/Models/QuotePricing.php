<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Quotes;

class QuotePricing extends Model
{
    protected $fillable = [
        'quote_id',
        'pricing_strategy',
        'category',
        'base_price',
        'markup_percentage',
        'markup_amount',
        'tax_percentage',
        'tax_amount',
        'tax_applied_on',
        'discount_percentage',
        'discount_amount',
        'package_cost',
        'currency',
        'markups',
        'tax',
        'tax_applies',
        'pricing',
        'created_by',
    ];

    protected $casts = [
        'markups' => 'array',
        'tax' => 'array',
        'tax_applies' => 'array',
        'pricing' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class, 'quote_id');
    }
}
