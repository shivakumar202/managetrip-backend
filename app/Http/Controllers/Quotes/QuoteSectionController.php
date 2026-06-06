<?php

namespace App\Http\Controllers\Quotes;

use App\Http\Controllers\Controller;
use App\Models\Quotes;
use App\Models\Query;
use App\Models\QuotePricing;
use App\Models\SpecialServices;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class QuoteSectionController extends Controller
{
    /**
     * Create a new draft quote for a trip
     */
    public function createDraftQuote(Request $request, $tripId)
    {
        $trip = Query::where('query_id', $tripId)->first();

        if (!$trip) {
            return response()->json([
                'message' => 'Trip not found'
            ], 404);
        }

        $packageCategories = $request->input('package_categories');
        $categoryField = $request->input('category');
        $existingQuoteCode = $request->input('quote_code') ?? $request->input('base_quote_code');

        if (is_array($packageCategories) && count($packageCategories)) {
            $categoriesToCreate = array_values($packageCategories);
        } elseif (!empty($categoryField)) {
            $categoriesToCreate = [trim($categoryField)];
        } else {
            $categoriesToCreate = ['standard'];
        }

        $categoriesToCreate = array_values(array_filter(array_unique(array_map('trim', $categoriesToCreate))));
        if (empty($categoriesToCreate)) {
            $categoriesToCreate = ['standard'];
        }


        if ($existingQuoteCode) {
            $quote = Quotes::where('quote_code', $existingQuoteCode)->first();

            if ($quote) {
                $quote->update([
                    'categories' => $categoriesToCreate,
                    'updated_by' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'quote_id' => $quote->id,
                    'quote_code' => $quote->quote_code,
                    'categories' => $quote->categories,
                ]);
            }
        }

        $newQuoteCode = 'ABQT-' . strtoupper(Str::random(8));

        $quote = Quotes::create([
            'query_id' => $trip->id,
            'quote_code' => $newQuoteCode,
            'status' => 'draft',
            'categories' => $categoriesToCreate,
            'updated_by' => auth()->id(),
            'travel_date' => $trip->travel_date ?? now(),

            'validity_days' => 30,
            'expiry_date' => now()->addDays(30),

        ]);

        return response()->json([
            'success' => true,
            'quote_id' => $quote->id,
            'quote_code' => $quote->quote_code,
            'categories' => $quote->categories,
        ]);
    }

    /**
     * Get quote with all sections
     * Searches by resolving the trip ID first (like createDraftQuote does)
     */
    public function getQuote($quoteId)
    {
        // First, try to resolve the trip ID from various identifiers
        $trip = Query::find($quoteId);
        if (!$trip) {
            $trip = Query::where('reference_id', $quoteId)->first();
        }
        if (!$trip) {
            $trip = Query::where('query_id', $quoteId)->first();
        }

        $quote = null;

        if ($trip) {
            $with = [
                'quoteHotels.hotel',
                'quoteTransports',
                'quoteActivities',
                'quoteFlights',
                'quoteSpecialServices',
                'trip',
            ];

            if (Schema::hasTable('quote_ferries')) {
                $with[] = 'quoteFerries';
            }

            $quote = Quotes::with($with)->where('query_id', $trip->id)->first();
        }

        if (!$quote) {
            $with = [
                'quoteHotels.hotel',
                'quoteTransports',
                'quoteActivities',
                'quoteFlights',
                'quoteSpecialServices',
                'trip',
            ];

            if (Schema::hasTable('quote_ferries')) {
                $with[] = 'quoteFerries';
            }

            $quote = Quotes::with($with)->find($quoteId);
        }

        if (!$quote) {
            $with = [
                'quoteHotels.hotel',
                'quoteTransports',
                'quoteActivities',
                'quoteFlights',
                'quoteSpecialServices',
                'quoteFerries',
                'quotePricings',
                'trip',
            ];

            if (Schema::hasTable('quote_ferries')) {
                $with[] = 'quoteFerries';
            }

            $quote = Quotes::with($with)->where('quote_code', $quoteId)->first();
        }

        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }

        return response()->json($quote);
    }



    public function draftgetQuote($quoteCode)
    {
        $with = [
            'quoteHotels.hotel',
            'quoteTransports',
            'quoteActivities',
            'quoteFlights',
            'quoteSpecialServices',
            'quoteFerries',
            'quotePricings',
        ];

        $quote = Quotes::with($with)
            ->where('quote_code', $quoteCode)
            ->first();

        if (!$quote) {
            return response()->json([
                'message' => 'Quote not found'
            ], 404);
        }

        return response()->json($quote);
    }

    /**
     * Save hotel section
     */
    public function saveHotels(Request $request, $query_id)
    {
        $quote = Quotes::where('quote_code', $query_id)->first();

        if (!$quote) {
            return response()->json([
                'message' => 'Quote not found'
            ], 404);
        }

        $data = $request->input('request_data')
            ?? $request->input('hotels')
            ?? [];

        if (!is_array($data) || empty($data)) {
            return response()->json([
                'message' => 'Hotel data is required'
            ], 422);
        }

        $quote->quoteHotels()->delete();

        $hotelTotal = 0;

        foreach ($data as $hotelData) {

            $pricing = $hotelData['pricing'] ?? [];

            $totalPrice = collect($pricing)->sum(function ($price) {
                return $price['total_price'] ?? 0;
            });

            $quote->quoteHotels()->create([
                'hotel_id' => $hotelData['hotel_id'] ?? null,
                'room_type_id' => $hotelData['room_type_id'] ?? null,
                'meal_plan' => $hotelData['meal_plan'] ?? null,
                'rooms' => $hotelData['rooms'] ?? 1,
                'pax' => $hotelData['occupancy'] ?? 2,
                'aweb' => $hotelData['aweb'] ?? 0,
                'cweb' => $hotelData['cweb'] ?? 0,
                'cnb' => $hotelData['cnb'] ?? 0,
                'stay_nights' => $hotelData['stay_nights'] ?? [],
                'pricing' => $pricing,
                'category' => $hotelData['category'] ?? null,
                'total_price' => $totalPrice,
            ]);

            $hotelTotal += $totalPrice;
        }

        return response()->json([
            'success' => true,
            'message' => 'Hotels saved successfully',
            'quote_id' => $quote->id,
            'hotel_total' => $hotelTotal
        ]);
    }

    /**
     * Save transport & activities section
     */
    public function saveTransportActivities(Request $request, $quoteId)
    {
        $quote = Quotes::where('quote_code', $quoteId)->first();

        if (!$quote) {
            return response()->json([
                'message' => 'Quote not found'
            ], 404);
        }

        $payloadDays = $request->input('days') ?? $request->input('request_data') ?? [];

        if (!is_array($payloadDays)) {
            return response()->json([
                'message' => 'Invalid payload'
            ], 422);
        }

        $quote->quoteTransports()->delete();
        $quote->quoteActivities()->delete();

        if (Schema::hasTable('quote_ferries')) {
            $quote->quoteFerries()->delete();
        }

        $transportTotal = 0;
        $activityTotal = 0;
        $ferryTotal = 0;

        foreach ($payloadDays as $dayData) {

            $date = $dayData['selectedDays']['date'] ?? null;

            foreach (($dayData['sections'] ?? []) as $section) {

                $type = $section['type'] ?? null;

                $location = is_array($section['selectedServiceLocation'] ?? null)
                    ? (
                        $section['selectedServiceLocation']['label']
                        ?? $section['selectedServiceLocation']['value']
                        ?? null
                    )
                    : ($section['selectedServiceLocation'] ?? null);

                $service =
                    $section['selectedService']['label']
                    ?? $section['selectedService']['value']
                    ?? null;

                $pricing = collect($section['pricingRows'] ?? [])
                    ->values()
                    ->map(function ($row, $index) use ($type) {

                        $price = $row['given']
                            ?? (($row['rate'] ?? 0) * ($row['quantity'] ?? 1));

                        if ($type === 'transport') {
                            return [
                                'id' => $index + 1,
                                'vehicle' => $row['selectedVehicle']['label'] ?? null,
                                'qty' => $row['quantity'] ?? 1,
                                'price' => $price,
                            ];
                        }

                        return [
                            'id' => $index + 1,
                            'category' => ucfirst($row['type'] ?? ''),
                            'qty' => $row['quantity'] ?? 1,
                            'price' => $price,
                        ];
                    })
                    ->toArray();

                $total = collect($pricing)->sum('price');

                if ($type === 'transport') {

                    $quote->quoteTransports()->create([
                        'quote_id' => $quote->id,
                        'date' => $date,
                        'service_location' => $location,
                        'service' => $service,
                        'time' => null,
                        'duration' => null,
                        'pricing' => array_values($pricing),
                        'total_price' => $total,
                    ]);

                    $transportTotal += $total;
                } elseif ($type === 'activity') {

                    $quote->quoteActivities()->create([
                        'quote_id' => $quote->id,
                        'date' => $date,
                        'activity_location' => $location,
                        'activity' => $service,
                        'time' => null,
                        'duration' => null,
                        'pricing' => array_values($pricing),
                        'total_price' => $total,
                    ]);

                    $activityTotal += $total;
                } elseif ($type === 'ferry' && Schema::hasTable('quote_ferries')) {

                    $quote->quoteFerries()->create([
                        'quote_id' => $quote->id,
                        'date' => $date,
                        'route' => $location,
                        'ferry' => $service,
                        'time' => null,
                        'duration' => null,
                        'pricing' => array_values($pricing),
                        'total_price' => $total,
                    ]);

                    $ferryTotal += $total;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Saved successfully',
            'transport_total' => $transportTotal,
            'activity_total' => $activityTotal,
            'ferry_total' => $ferryTotal,
            'grand_total' => $transportTotal + $activityTotal + $ferryTotal
        ]);
    }

    /**
     * Save flights section
     */
    public function saveFlights(Request $request, $quoteId)
    {
        $quote = Quotes::where('quote_code', $quoteId)->first();

        if (!$quote) {
            return response()->json([
                'message' => 'Quote not found'
            ], 404);
        }

        $flights = $request->input('flights', []);

        $quote->quoteFlights()->delete();

        $totalPrice = 0;

        foreach ($flights as $flightData) {

            $price = $flightData['given'] ?? $flightData['rate'] ?? 0;

            $quote->quoteFlights()->create([
                'departure_airport' => $flightData['departure'] ?? null,
                'arrival_airport' => $flightData['arrival'] ?? null,
                'travel_date' => $flightData['travel_date'] ?? null,
                'departure_time' => $flightData['timing'] ?? null,
                'arrival_time' => $flightData['timing'] ?? null,
                'travel_class' => $flightData['trip_type'] ?? null,
                'adult' => $flightData['adult'] ?? 0,
                'child' => $flightData['child'] ?? 0,
                'price' => $price,
            ]);

            $totalPrice += $price;
        }

        return response()->json([
            'success' => true,
            'message' => 'Flights section saved successfully',
            'total_price' => $totalPrice
        ]);
    }
    /**
     * Save special services section (extras, other services)
     */
    public function saveSpecialServices(Request $request, $quoteId)
    {
        $quote = Quotes::where('quote_code', $quoteId)->first();
        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }

        $validated = $request->validate([
            'services' => 'array|nullable',
        ]);

        $quote->quoteSpecialServices()->delete();

        $totalPrice = 0;
        if (isset($validated['services']) && is_array($validated['services'])) {
            foreach ($validated['services'] as $service) {
                $serviceId = $service['service_id'] ?? null;
                if ($serviceId && !SpecialServices::where('id', $serviceId)->exists()) {
                    $serviceId = null;
                }

                $quote->quoteSpecialServices()->create([
                    'type' => $service['type'] ?? 'other_service',
                    'service_id' => $serviceId,
                    'service_name' => $service['service_name'] ?? null,
                    'day' => $service['day'] ?? null,
                    'notes' => $service['notes'] ?? null,
                    'pricing' => $service['pricing'] ?? [],
                    'total_price' => $service['total_price'] ?? 0,
                ]);
                $totalPrice += ($service['total_price'] ?? 0);
            }
        }


        return response()->json([
            'success' => true,
            'message' => 'Special Services section saved successfully',
        ]);
    }

    /**
     * Save pricing information for the quote
     */
    public function savePricing(Request $request, $quoteId)
    {
        $quote = Quotes::where('quote_code', $quoteId)->first();

        if (!$quote) {
            return response()->json([
                'message' => 'Quote not found'
            ], 404);
        }

        $pricing = $request->input('pricing', []);

        $basePrice = 0;
        $packageCost = 0;

        $seenCategories = [];
        $savedPricingIds = [];

        foreach ($pricing['categories'] ?? [] as $category) {

            $categoryName = $category['name'] ?? 'default';

            $basePrice += $category['base_cost'] ?? 0;
            $packageCost += $category['totals']['final_price'] ?? 0;

            $seenCategories[] = $categoryName;

            $categoryMarkups = $category['markup'] ?? [];
            $categoryTaxes = $category['tax'] ?? [];

            unset($category['markup']);
            unset($category['tax']);

            $quotePricing = QuotePricing::updateOrCreate(
                ['quote_id' => $quote->id, 'category' => $categoryName],
                [
                    'pricing_strategy' => $pricing['pricing_strategy'] ?? null,
                    'currency' => $pricing['currency'] ?? 'INR',
                    'base_price' => $category['base_cost'] ?? 0,
                    'package_cost' => $category['totals']['final_price'] ?? 0,
                    'markups' => $categoryMarkups,
                    'tax' => $categoryTaxes,
                    'pricing' => $category,
                    'created_by' => auth()->id(),
                ]
            );

            if ($quotePricing && $quotePricing->id) {
                $savedPricingIds[] = $quotePricing->id;
            }
        }

        // Remove any existing QuotePricing records for this quote that were not present in the request
        if (!empty($seenCategories)) {
            QuotePricing::where('quote_id', $quote->id)
                ->whereNotIn('category', $seenCategories)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Pricing saved successfully',
            'pricing_ids' => $savedPricingIds,
            'base_price' => $basePrice,
            'package_cost' => $packageCost,
        ]);
    }

    /**
     * Finalize quote (mark as sent/submitted)
     */
    public function finalizeQuote(Request $request, $quoteId)
    {
        $quote = Quotes::with('trip')->where('quote_code', $quoteId)->first();
        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }

        $quote->trip->update([
            'status' => 1,
        ]);

        $quote->update([
            'status' => 'draft',
            'remarks' => $validated['remarks'] ?? null,

            'updated_by' => auth()->id(),
        ]);

        // persist final pricing snapshot

        return response()->json([
            'success' => true,
            'message' => 'Quote finalized successfully',
            'quote' => $quote,
        ]);
    }

    /**
     * Get quote progress/completion status
     */
    public function getQuoteProgress($quoteId)
    {
        $quote = Quotes::find($quoteId);
        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }

        return response()->json([
            'hotel_section' => [
                'completed' => $quote->quoteHotels()->exists(),
                'total_price' => $quote->hotel_total,
            ],
            'transport_activities_section' => [
                'completed' => $quote->quoteTransports()->exists() || $quote->quoteActivities()->exists(),
                'transport_total' => $quote->transport_total,
                'activity_total' => $quote->activity_total,
            ],
            'flight_section' => [
                'completed' => $quote->quoteFlights()->exists(),
                'total_price' => $quote->quoteFlights()->sum('price'),
            ],
            'special_services_section' => [
                'completed' => $quote->quoteSpecialServices()->exists(),
                'total_price' => $quote->extra_total,
            ],
            'overall_status' => $quote->status,
            'final_price' => $quote->package_cost,
        ]);
    }

    public function getQuoteHotels($quoteId)
    {
        $quote = Quotes::with('quoteHotels.hotel')->where('quote_code', $quoteId)->first();
        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }

        return response()->json([
            'hotels' => $quote->quoteHotels->map(function ($qh) {
                return [
                    'hotel_id' => $qh->hotel_id,
                    'hotel_name' => $qh->hotel ? $qh->hotel->name : null,
                    'room_type_id' => $qh->room_type_id,
                    'meal_plan' => $qh->meal_plan,
                    'rooms' => $qh->rooms,
                    'pax' => $qh->pax,
                    'aweb' => $qh->aweb,
                    'cweb' => $qh->cweb,
                    'cnb' => $qh->cnb,
                    'stay_nights' => $qh->stay_nights,
                    'category' => $qh->category,
                    'pricing' => $qh->pricing,
                    'total_price' => $qh->total_price,
                ];
            }),
        ]);
    }


    public function getLatestQuote($tripId)
    {
        $trip = Query::where('query_id', $tripId)->first();

        if (!$trip) {
            return response()->json([
                'message' => 'Trip not found'
            ], 404);
        }

        $quote = Quotes::with([
            'quoteHotels.hotel',
            'quoteTransports',
            'quoteActivities',
            'quoteFlights',
            'quoteSpecialServices',
            'quoteFerries',
            'quotePricings',
            'trip',
        ])
            ->where('query_id', $trip->id)
            ->orderBy('created_at', 'desc')
            ->latest()
            ->first();

        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }

        return response()->json($quote);
    }


    public function getAllQuotesForTrip($tripId, $quoteCode = null)
    {
        $trip = Query::where('query_id', $tripId)->first();

        if (!$trip) {
            return response()->json([
                'message' => 'Trip not found'
            ], 404);
        }

        $quotes = Quotes::with([
            'quotePricings'
        ])
            ->where('query_id', $trip->id)
            ->latest()
            ->get();

        $query = Quotes::with([
            'quoteHotels.hotel',
            'quoteTransports',
            'quoteActivities',
            'quoteFlights',
            'quoteSpecialServices',
            'quoteFerries',
            'quotePricings',
            'trip',
        ])->where('query_id', $trip->id);

        if ($quoteCode) {
            $query->where('quote_code', $quoteCode);
        } else {
            $query->latest();
        }

        $quote = $query->first();

        if (!$quote) {
            return response()->json([
                'message' => 'Quote not found'
            ], 404);
        }

        $quote->load(['quotePricings']);

        return response()->json([
            'selected_quote' => $quote,
            'quotes' => $quotes
        ]);
    }



    public function getQuoteSuggestions($tripId, $quoteCode = null)
    {
        $trip = Query::where('query_id', $tripId)->first();

        if (!$trip) {
            return response()->json([
                'message' => 'Trip not found'
            ], 404);
        }

        $query = Quotes::with([
            'quoteHotels.hotel',
            'quoteTransports',
            'quoteActivities',
            'quoteFlights',
            'quoteSpecialServices',
            'quoteFerries',
            'quotePricings',
            'trip',
        ]);

        if ($quoteCode) {

            // Return only the requested quote
            $quotes = $query->where('quote_code', $quoteCode)->get();
        } else {

            // Return 4 matching quotes
            $quotes = $query
                ->where('adult_count', $trip->adults)
                ->where('child_count', $trip->children)
                ->where('nights', $trip->nights)->orWhere(function ($q) use ($trip) {
                    $q->where('travel_date', $trip->start_date)
                        ->where('nights', $trip->nights);
                })
                ->latest()
                ->take(4)
                ->get();
        }

        if ($quotes->isEmpty()) {
            return response()->json([
                'quotes' => [],
                'message' => 'No suggestions found'
            ], 200);
        }

        return response()->json([
            'quotes' => $quotes
        ], 200);
    }



    public function getQuoteSummary($quoteId)
    {
        $quotes = Quotes::with([
            'quoteHotels.hotel',
            'quoteTransports',
            'quoteActivities',
            'quoteFlights',
            'quoteSpecialServices',
            'quoteFerries',
            'trip',
        ])
            ->where('quote_code', $quoteId)
            ->get();

        if ($quotes->isEmpty()) {
            return response()->json([
                'message' => 'Quote not found'
            ], 404);
        }

        $firstQuote = $quotes->first();

        return response()->json([

            'quote_code' => $firstQuote->quote_code,

            'status' => $firstQuote->status,

            'remarks' => $firstQuote->remarks,

            'trip' => [
                'id' => $firstQuote->trip->id ?? null,
                'reference_id' => $firstQuote->trip->reference_id ?? null,
                'customer_name' => $firstQuote->trip->customer_name ?? null,
            ],

            'acommodation' => [
                'total_price' => $quotes->sum('hotel_total'),

                'details' => $quotes
                    ->flatMap(function ($quote) {

                        return $quote->quoteHotels
                            ->map(function ($qh) {

                                return [

                                    'hotel_name' =>
                                    optional(
                                        $qh->hotel
                                    )->name,

                                    'room_type_id' =>
                                    $qh->room_type_id,

                                    'meal_plan' =>
                                    $qh->meal_plan,

                                    'rooms' =>
                                    $qh->rooms,

                                    'pax' =>
                                    $qh->pax,

                                    'aweb' =>
                                    $qh->aweb,

                                    'cweb' =>
                                    $qh->cweb,

                                    'cnb' =>
                                    $qh->cnb,

                                    'stay_nights' =>
                                    $qh->stay_nights,

                                    'category' =>
                                    $qh->category,

                                    'pricing' =>
                                    $qh->pricing,

                                    'total_price' =>
                                    $qh->total_price,
                                ];
                            });
                    })
                    ->values(),
            ],

            'transport_activities' => [

                'total_price' =>
                $quotes->sum('transport_total')
                    +
                    $quotes->sum('activity_total'),

                'details' =>

                $quotes

                    ->flatMap(function ($quote) {

                        return

                            $quote
                            ->quoteTransports

                            ->map(function ($qt) {

                                return [

                                    'type' =>
                                    'transport',

                                    'day' =>
                                    $qt->day,

                                    'date' =>
                                    $qt->date,

                                    'service_location' =>
                                    $qt->service_location,

                                    'service' =>
                                    $qt->service,

                                    'time' =>
                                    $qt->time,

                                    'duration' =>
                                    $qt->duration,

                                    'pricing' =>
                                    $qt->pricing,

                                    'total_price' =>
                                    $qt->total_price,
                                ];
                            })

                            ->merge(

                                $quote
                                    ->quoteActivities

                                    ->map(function ($qa) {

                                        return [

                                            'type' =>
                                            'activity',

                                            'day' =>
                                            $qa->day,

                                            'date' =>
                                            $qa->date,

                                            'activity_location' =>
                                            $qa->activity_location,

                                            'activity' =>
                                            $qa->activity,

                                            'time' =>
                                            $qa->time,

                                            'duration' =>
                                            $qa->duration,

                                            'pricing' =>
                                            $qa->pricing,

                                            'total_price' =>
                                            $qa->total_price,
                                        ];
                                    })

                            )

                            ->merge(

                                $quote
                                    ->quoteFerries

                                    ->map(function ($qf) {

                                        return [

                                            'type' =>
                                            'ferry',

                                            'day' =>
                                            $qf->day,

                                            'date' =>
                                            $qf->date,

                                            'route' =>
                                            $qf->route,

                                            'ferry' =>
                                            $qf->ferry,

                                            'time' =>
                                            $qf->time,

                                            'duration' =>
                                            $qf->duration,

                                            'pricing' =>
                                            $qf->pricing,

                                            'total_price' =>
                                            $qf->total_price,
                                        ];
                                    })

                            );
                    })

                    ->values(),
            ],

            'travel_date' =>
            $firstQuote->travel_date,

            'categories' =>

            $quotes

                ->map(function ($quote) {

                    return [

                        'quote_id' =>
                        $quote->id,

                        'category' =>
                        $quote->category,

                        'pricing' => [

                            'hotel_total' =>
                            $quote->hotel_total,

                            'transport_total' =>
                            $quote->transport_total,

                            'activity_total' =>
                            $quote->activity_total,

                            'extra_total' =>
                            $quote->extra_total,

                            'base_price' =>
                            $quote->base_price,

                            'markup_percentage' =>
                            $quote->markup_percentage,

                            'markup_amount' =>
                            $quote->markup_amount,

                            'markups' =>
                            $quote->markups,

                            'tax' =>
                            $quote->tax,

                            'tax_applies' =>
                            $quote->tax_applies,

                            'tax_percentage' =>
                            $quote->tax_percentage,

                            'tax_applied_on' =>
                            $quote->tax_applied_on,

                            'tax_amount' =>
                            $quote->tax_amount,

                            'discount_percentage' =>
                            $quote->discount_percentage,

                            'discount_amount' =>
                            $quote->discount_amount,

                            'package_cost' =>
                            $quote->package_cost,

                            'currency' =>
                            $quote->currency,
                        ],
                    ];
                })

                ->values(),
        ]);
    }
}
