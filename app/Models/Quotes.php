<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Query;
use App\Models\TripActivity;
use App\Models\TripFlight;
use App\Models\TripHotel;
use App\Models\TripTransport;
use App\Models\ExtraService;

class Quotes extends Model
{
    protected $fillable = [
        'query_id',
        'quote_code',
        'category',
        'categories',
        'basic_details_change',
        'adult_count',
        'child_count',
        'infant_count',
        'travel_date',
        'hotel_total',
        'activity_total',
        'transport_total',
        'extra_total',
        'base_price',
        'pricing_strategy',
        'discount_percentage',
        'discount_amount',
        'validity_days',
        'expiry_date',
        'tax_applied',
        'tax_applied_on',
        'markup_percentage',
        'markup_amount',
        'tax_percentage',
        'tax_amount',
        'package_cost',
        'currency',
        'remarks',
        'updated_by',
        'status',
        'markups',
        'tax',
        'tax_applies',
    ];

    protected $casts = [
        'categories' => 'array',
        'markups' => 'array',
        'tax' => 'array',
        'tax_applies' => 'array',
    ];

    public function trip()
    {
        return $this->belongsTo(Query::class, 'query_id');
    }

    public function hotels()
    {
        return $this->hasMany(TripHotel::class, 'quote_id');
    }

    public function transports()
    {
        return $this->hasMany(TripTransport::class, 'quote_id');
    }

    public function activities()
    {
        return $this->hasMany(TripActivity::class, 'quote_id');
    }

    public function flights()
    {
        return $this->hasMany(TripFlight::class, 'quote_id');
    }

    public function extraServices()
    {
        return $this->hasMany(ExtraService::class, 'quote_id');
    }

    // New modular relationships
    public function quoteHotels()
    {
        return $this->hasMany(QuoteHotel::class, 'quote_id');
    }

    public function quoteTransports()
    {
        return $this->hasMany(QuoteTransport::class, 'quote_id');
    }

    public function quoteActivities()
    {
        return $this->hasMany(QuoteActivity::class, 'quote_id');
    }

    public function quoteFerries()
    {
        return $this->hasMany(QuoteFerry::class, 'quote_id');
    }

    public function quoteFlights()
    {
        return $this->hasMany(QuoteFlight::class, 'quote_id');
    }

    public function quoteSpecialServices()
    {
        return $this->hasMany(QuoteSpecialService::class, 'quote_id');
    }

    public function quotePricings()
    {
        return $this->hasMany(QuotePricing::class, 'quote_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
