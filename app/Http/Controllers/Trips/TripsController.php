<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Models\Activities;
use App\Models\ActivityPrices;
use App\Models\ActivitySeason;
use App\Models\Duty;
use App\Models\DutyPrice;
use App\Models\Hotels;
use App\Models\Query;
use App\Models\HotelPrices;
use App\Models\MealPlan;
use App\Models\Season;
use App\Models\SeasonDateRange;
use App\Models\Quotes;
use App\Models\TripActivity;
use App\Models\TripFlight;
use App\Models\TripHotel;
use App\Models\TripTransport;
use App\Models\TripTransportCab;
use App\Models\ExtraService;
use App\Models\Ferry;
use App\Models\FerryClass;
use App\Models\FerryPricing;
use App\Models\FerryRoute;
use App\Models\FerryRoutes;
use App\Models\HotelPrice;
use App\Models\SpecialServicePricing;
use App\Models\SpecialServices;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TripsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trips = [];

        $trips = Query::get();
        return response()->json($trips);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $trip = Query::where('query_id', $id)->first();
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip);
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getHotel(Request $request)
    {
        $term = strtolower($request->query('term', ''));

        $cacheKey = 'hotels_search_' . md5($term);
        $cacheTTL = 360;

        $hotels = Cache::remember($cacheKey, $cacheTTL, function () use ($term) {

            $latestIds = Hotels::selectRaw('MAX(id) as id')
                ->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(location) LIKE ?', ["%{$term}%"]);
                })
                ->groupBy('name', 'location')
                ->pluck('id');

            return Hotels::whereIn('id', $latestIds)
                ->orderBy('name')
                ->get();
        });

        return response()->json($hotels);
    }

    public function getHotelDetails($id)
    {
        $cacheKey = 'hotel_details_' . $id;
        $cacheTTL = 0;

        $hotel = Cache::remember($cacheKey, $cacheTTL, function () use ($id) {

            $hotel = Hotels::with(['roomTypes'])->find($id);

            if (!$hotel) {
                return null;
            }

            $mealPlans = MealPlan::get();

            $rooms = $hotel->roomTypes;

            foreach ($rooms as $room) {
                preg_match('/\((\d+)P\)/', $room->name, $matches);
                $room->occupancy = isset($matches[1])
                    ? (int) $matches[1]
                    : null;
            }

            return [
                'meal_plans' => $mealPlans,
                'rooms' => $rooms,
            ];
        });

        if (!$hotel) {
            return response()->json([
                'message' => 'Hotel not found'
            ], 404);
        }

        return response()->json($hotel);
    }


    public function getHotelPrices(Request $request)
    {
        $hotelId = $request->input('hotel_id');
        $roomType = $request->input('room_type');
        $mealPlanId = $request->input('meal_plan_id');
        $rooms = $request->input('rooms', 1);
        $awb = $request->input('awb', 0);
        $cwb = $request->input('cwb', 0);
        $cnb = $request->input('cnb', 0);

        $dates = $request->input('dates', []);
        if (!is_array($dates) || empty($dates)) {
            $singleDate = $request->input('date');
            $dates = $singleDate ? [$singleDate] : [];
        }

        if (!$hotelId || !$roomType || !$mealPlanId) {
            return response()->json(['message' => 'Missing required parameters'], 400);
        }

        Log::info('STEP 6: getHotelPrices called', [
            'hotel_id' => $hotelId,
            'room_type' => $roomType,
            'meal_plan_id' => $mealPlanId,
            'dates' => $dates
        ]);

        $response = [];

        foreach ($dates as $date) {
            try {
                $parsedDate = \Carbon\Carbon::parse($date)->toDateString();
                $searchDateCarbon = \Carbon\Carbon::parse($parsedDate);

                Log::info('STEP 6.5: Looking for price', [
                    'hotel_id' => $hotelId,
                    'room_type_id' => $roomType,
                    'meal_plan_id' => $mealPlanId,
                    'parsed_date' => $parsedDate,
                    'search_date_carbon' => $searchDateCarbon->format('Y-m-d')
                ]);

                // Fetch price with seasonDateRange and extras
                $price = HotelPrice::where('hotel_id', $hotelId)
                    ->where('room_type_id', $roomType)
                    ->where('meal_plan_id', $mealPlanId)
                    ->with(['seasonDateRange', 'extras.extra'])
                    ->get()
                    ->first(function ($p) use ($searchDateCarbon) {
                        if (!$p->seasonDateRange) {
                            return false;
                        }

                        $rangeStart = \Carbon\Carbon::parse($p->seasonDateRange->start_date);
                        $rangeEnd = \Carbon\Carbon::parse($p->seasonDateRange->end_date);

                        Log::info('STEP 6.6: Comparing dates', [
                            'search_date' => $searchDateCarbon->format('Y-m-d'),
                            'range_start' => $rangeStart->format('Y-m-d'),
                            'range_end' => $rangeEnd->format('Y-m-d'),
                            'is_valid' => $searchDateCarbon->between($rangeStart, $rangeEnd)
                        ]);

                        return $searchDateCarbon->between($rangeStart, $rangeEnd, true);
                    });

                Log::info('STEP 7: Price query executed', [
                    'found' => $price ? true : false,
                    'season_range' => $price ? ($price->seasonDateRange->start_date . ' to ' . $price->seasonDateRange->end_date) : null
                ]);

                if (!$price) {
                    Log::warning('STEP 8: No price found for date', ['date' => $parsedDate]);

                    $response[] = [
                        'date' => $date,
                        'base_price' => 0,
                        'total_price' => 0,
                        'awb_price' => 0,
                        'cwb_price' => 0,
                        'cnb_price' => 0,
                        'rooms' => $rooms,
                        'room_type_id' => $roomType,
                        'awb' => $awb,
                        'cwb' => $cwb,
                        'cnb' => $cnb,
                    ];
                    continue;
                }

                $awbPrice = 0;
                $cwbPrice = 0;
                $cnbPrice = 0;

                // Extract extras prices
                if ($price->extras && count($price->extras) > 0) {
                    foreach ($price->extras as $extra) {
                        if (!$extra->extra) {
                            continue;
                        }

                        $code = strtolower($extra->extra->code ?? '');

                        if ($code === 'awb') {
                            $awbPrice = (float) $extra->price;
                        } elseif ($code === 'cwb') {
                            $cwbPrice = (float) $extra->price;
                        } elseif ($code === 'cnb') {
                            $cnbPrice = (float) $extra->price;
                        }
                    }
                }

                Log::info('STEP 9: Extras mapped', [
                    'awbPrice' => $awbPrice,
                    'cwbPrice' => $cwbPrice,
                    'cnbPrice' => $cnbPrice
                ]);

                $basePrice = (float) $price->base_price;
                $totalPrice = ($basePrice * $rooms)
                    + ($awbPrice * $awb)
                    + ($cwbPrice * $cwb)
                    + ($cnbPrice * $cnb);

                Log::info('STEP 10: Total calculated', [
                    'base' => $basePrice,
                    'total' => $totalPrice,
                    'calculation' => "({$basePrice} * {$rooms}) + ({$awbPrice} * {$awb}) + ({$cwbPrice} * {$cwb}) + ({$cnbPrice} * {$cnb})"
                ]);

                $response[] = [
                    'date' => $date,
                    'base_price' => $basePrice,
                    'total_price' => $totalPrice,
                    'rooms' => $rooms,
                    'room_type_id' => $roomType,
                    'awb' => $awb,
                    'cwb' => $cwb,
                    'cnb' => $cnb,
                    'awb_price' => $awbPrice,
                    'cwb_price' => $cwbPrice,
                    'cnb_price' => $cnbPrice,
                    'season_range' => $price->seasonDateRange ? ($price->seasonDateRange->start_date . ' to ' . $price->seasonDateRange->end_date) : null
                ];
            } catch (\Exception $e) {
                Log::error('STEP ERROR: Exception in getHotelPrices', [
                    'date' => $date,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $response[] = [
                    'date' => $date,
                    'base_price' => 0,
                    'total_price' => 0,
                    'awb_price' => 0,
                    'cwb_price' => 0,
                    'cnb_price' => 0,
                    'rooms' => $rooms,
                    'room_type_id' => $roomType,
                    'awb' => $awb,
                    'cwb' => $cwb,
                    'cnb' => $cnb,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('STEP 11: Final response', ['count' => count($response)]);

        return response()->json($response);
    }
    public function getServiceLocation(Request $request)
    {
        $term = trim($request->query('term', ''));
        $cacheKey = 'service_locations_all';
        $cacheTTL = 1;

        $locations = Cache::remember($cacheKey, $cacheTTL, function () {
            $locations = Duty::select('point_a', 'point_b')
                ->distinct()
                ->get()
                ->map(function ($item) {
                    $pointB = trim((string) $item->point_b);

                    return [
                        'location_name' => ($pointB && $pointB !== '-')
                            ? $item->point_a . ' to ' . $pointB
                            : $item->point_a
                    ];
                })
                ->pluck('location_name')
                ->unique()
                ->values();

            Log::info('Service locations fetched from database and cached', ['count' => count($locations)]);
            return $locations;
        });

        if ($term !== '') {
            $normalizedTerm = strtolower($term);
            $locations = $locations->filter(function ($location) use ($normalizedTerm) {
                return strpos(strtolower($location), $normalizedTerm) !== false;
            })->values();

            Log::info('Service locations filtered by term', ['term' => $term, 'count' => $locations->count()]);
        }

        return response()->json($locations);
    }

    public function getService(Request $request)
    {
        $location = trim($request->query('service_location'));
        Log::info('incoming_location', ['value' => $location]);

        if (!$location) {
            Log::warning('missing_location');
            return response()->json(['message' => 'Location parameter is required'], 400);
        }

        $cacheKey = 'services_location_' . md5($location);
        $cacheTTL = 60 * 24;

        $services = Cache::remember($cacheKey, $cacheTTL, function () use ($location) {

            $normalized = strtolower($location);
            Log::info('normalized_slug', ['value' => $normalized]);

            if (str_contains($normalized, '-to-')) {
                [$pointA, $pointB] = explode('-to-', $normalized);

                $pointA = trim(str_replace('-', ' ', $pointA));
                $pointB = trim(str_replace('-', ' ', $pointB));

                Log::info('parsed_points', ['point_a' => $pointA, 'point_b' => $pointB]);

                $data = Duty::whereRaw('LOWER(TRIM(point_a)) = ?', [$pointA])
                    ->whereRaw('LOWER(TRIM(point_b)) = ?', [$pointB])
                    ->get();

                Log::info('query_result_to_case', ['count' => $data->count(), 'data' => $data]);

                return $data;
            }

            $normalized = trim(str_replace('-', ' ', $normalized));
            Log::info('single_location', ['value' => $normalized]);

            $data = Duty::where(function ($q) use ($normalized) {
                $q->whereRaw('LOWER(TRIM(point_a)) = ?', [$normalized])
                    ->orWhereRaw('LOWER(TRIM(point_b)) = ?', [$normalized]);
            })->get();

            Log::info('query_result_single', ['count' => $data->count(), 'data' => $data]);

            return $data;
        });

        Log::info('final_response', ['count' => $services->count()]);

        // Transform the data to match frontend expectations
        $transformedServices = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'value' => $service->id,
                'label' => $service->service,
            ];
        });

        return response()->json($transformedServices);
    }

    public function getVehicleTypes(Request $request)
    {

        $vehicles = Vehicle::get();

        return response()->json($vehicles);
    }

    public function getActivityPrice(Request $request)
    {

        $service = $request->query('service_id');
        $date = $request->query('date');
        $vehicleType = $request->query('vehicle_type');
        $vehicleCount = $request->query('vehicle_count', 1);

        $duty = DutyPrice::where('duty_id', $service)->where('vehicle_id', $vehicleType)
            ->whereHas('seasonDateRange', function ($q) use ($date) {
                $q->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date);
            })
            ->value('price');

        $price = $duty ? floatval($duty) * $vehicleCount : 0;

        return response()->json([
            'price' => $price,
            'rate' => $duty ? floatval($duty) : 0,
        ]);
    }


    public function getTripActivityLocations(Request $request)
    {
        $locations = Activities::groupBy('name')
            ->pluck('name');

        return response()->json($locations);
    }
    public function getTripActivities(Request $request)
    {
        $location = $request->query('service_location');

        if (!$location) {
            return response()->json([
                'message' => 'Location parameter is required'
            ], 400);
        }

        $activities = Activities::where('name', $location)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'value' => $activity->id,
                    'label' => $activity->service,
                ];
            });

        return response()->json($activities);
    }

    public function getTripActivityPricing(Request $request)
    {
        $activityId = $request->query('service_id');
        $date = $request->query('date');
        $ticketType = strtolower($request->query('ticket_type', 'adult'));
        $count = (int) $request->query('count', 1);

        if (!$activityId || !$date) {
            return response()->json([
                'message' => 'Missing required parameters: service_id, date'
            ], 400);
        }

        try {

            $ticketMap = [
                'adult' => 'Adult',
                'child' => 'Child (3-12)',
                'infant' => 'Child (1-2)',
            ];

            $selectedCategory = $ticketMap[$ticketType] ?? 'Adult';

            $prices = ActivityPrices::query()
                ->with('paxCategory')
                ->where('activity_id', $activityId)

                ->whereHas('seasonDateRange', function ($q) use ($date) {
                    $q->where('start_date', '<=', $date)
                        ->where('end_date', '>=', $date);
                })

                ->whereHas('paxCategory', function ($q) use ($selectedCategory) {
                    $q->where('name', $selectedCategory);
                })

                ->get();

            if ($prices->isEmpty()) {
                return response()->json([
                    'prices' => []
                ]);
            }

            $result = [];

            foreach ($prices as $price) {

                $amount = (float) $price->price;

                $result[] = [
                    'id' => $price->paxCategory?->id,
                    'name' => $price->paxCategory?->name,
                    'rate' => $amount,
                    'price' => $amount * $count,
                    'count' => $count,
                ];
            }

            return response()->json([
                'prices' => $result
            ]);
        } catch (\Exception $e) {

            Log::error('Failed to fetch activity pricing', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch activity pricing',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTripFerryRoutes(Request $request)
    {
        try {
            $routes = FerryRoutes::selectRaw(
                "CONCAT(`from`, ' to ', `to`) as route"
            )
                ->pluck('route');

            return response()->json($routes);
        } catch (\Exception $e) {
            \Log::error('Error fetching ferry routes: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch ferry routes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTripFerryServices(Request $request)
    {
        $service_location = $request->query('service_location');

        if (!$service_location) {
            return response()->json([
                'message' => 'service_location parameter is required'
            ], 400);
        }

        $normalized = strtolower($service_location);
        $route = explode(' to ', $normalized);

        $from = trim($route[0]);
        $to = isset($route[1]) ? trim($route[1]) : null;

        try {
            $services = Ferry::with('classes')
                ->whereHas('routes.route', function ($query) use ($from, $to) {
                    $query->where('from', $from);

                    if ($to) {
                        $query->where('to', $to);
                    }
                })
                ->get();

            $result = collect();
            $counter = 1;

            foreach ($services as $ferry) {

                if ($ferry->classes->isEmpty()) {
                    $result->push([
                        'id' => $counter,
                        'value' => $counter,
                        'label' => $ferry->name,
                    ]);

                    $counter++;
                } else {

                    foreach ($ferry->classes as $class) {
                        $result->push([
                            'id' => $counter,
                            'value' => $counter,
                            'label' => $ferry->name . ' - ' . $class->class_name,
                        ]);

                        $counter++;
                    }
                }
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to fetch ferry services: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch ferry services',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTripFerryPricing(Request $request)
    {
        $routeName = $request->query('route_name');
        $ferryId = $request->query('service_id');
        $date = $request->query('date');
        $ticketType = strtolower($request->query('ticket_type', 'adult'));
        $count = (int) $request->query('count', 1);

        if (!$routeName || !$ferryId || !$date) {
            return response()->json([
                'message' => 'Missing required parameters'
            ], 400);
        }

        try {

            $ticketMap = [
                'adult' => 'Adult',
                'child' => 'Child (3-12)',
                'infant' => 'Child (1-2)',
            ];

            $selectedCategory = $ticketMap[$ticketType] ?? 'Adult';

            $class = trim(explode('-', $routeName)[1] ?? '');
            $classId = FerryClass::where('class_name', $class)->value('id');

            $routeParts = explode(' to ', strtolower($routeName));

            $from = trim($routeParts[0]);
            $to = trim($routeParts[1] ?? '');

            $routeQuery = FerryRoutes::where('from', $from);

            if ($to) {
                $routeQuery->where('to', $to);
            }

            $routeId = $routeQuery->value('id');

            $query = FerryPricing::query()
                ->with([
                    'seasonDateRange',
                    'paxCategory',
                    'class'
                ])
                ->where('route_id', $routeId)
                ->where('ferry_id', $ferryId)
                ->where('class_id', $classId)
                ->whereHas('seasonDateRange', function ($q) use ($date) {
                    $q->where('start_date', '<=', $date)
                        ->where('end_date', '>=', $date);
                })
                ->whereHas('paxCategory', function ($q) use ($selectedCategory) {
                    $q->where('name', $selectedCategory);
                });

            $pricing = $query->get();

            $prices = [];

            foreach ($pricing as $item) {
                $price = (float) $item->price;

                $prices[] = [
                    'id' => $item->id,
                    'pax_id' => $item->pax_id,
                    'price' => $price * $count,
                    'rate' => $price,
                    'count' => $count,
                    'paxCategory' => $item->paxCategory?->name,
                    'departure' => $item->departure,
                ];
            }

            return response()->json([
                'prices' => $prices
            ]);
        } catch (\Exception $e) {

            Log::error('Failed to fetch ferry pricing', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch ferry pricing',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getTripSpecialServices(Request $request)
    {
        $term = trim($request->query('term', ''));
        $serviceId = $request->query('service_id');
        $date = $request->query('date');

        $query = SpecialServices::with([
            'pricings' => function ($q) use ($date) {
                if ($date) {
                    $q->whereHas('seasonDateRange', function ($sq) use ($date) {
                        $sq->whereDate('start_date', '<=', $date)
                            ->whereDate('end_date', '>=', $date);
                    });
                }
            }
        ]);

        if ($serviceId) {
            $query->where('id', $serviceId);
        } else {
            $query->when($term, function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%");
            });
        }

        $services = $query->get()->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->pricings->first()?->price,
            ];
        });

        return response()->json($services);
    }
}
