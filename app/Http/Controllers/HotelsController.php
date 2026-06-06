<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\HotelGroup;
use App\Models\HotelPrice;
use App\Models\MealPlan;
use Illuminate\Http\Request;

class HotelsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 5);
        $search = $request->query('search', '');
        $sortKey = $request->query('sort_key', 'id');
        $sortDir = $request->query('sort_dir', 'asc');

        $query = Hotel::with([
            'prices.roomType:id,name',
            'prices.mealPlan:id,name'
        ])->select('id', 'name', 'location', 'star', 'check_in_time', 'check_out_time', 'payment_preference');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($sortKey === 'hotel') {
            $query->orderBy('name', $sortDir);
        } elseif ($sortKey === 'location') {
            $query->orderBy('location', $sortDir);
        } else {
            $query->orderBy('id', $sortDir);
        }

        $total = $query->count();

        $hotels = $query->forPage($page, $perPage)->get();

        $rows = $hotels->map(function ($hotel) {
            return [
                'id' => $hotel->id,
                'hotel' => $hotel->name,
                'location' => $hotel->location,
                'star' => $hotel->star,
                'room' => $hotel->prices->pluck('roomType.name')->filter()->unique()->implode(', '),
                'check_in_time' => $hotel->check_in_time,
                'check_out_time' => $hotel->check_out_time,
                'payment_preference' => $hotel->payment_preference,
                'meal_plan' => $hotel->prices->pluck('mealPlan.name')->filter()->unique()->implode(', ')
            ];
        });

        return response()->json([
            'data' => $rows,
            'total' => $total,
        ], 200);
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
        //
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

    public function hotelGroups(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 5);
        $search = $request->query('search', '');

        $query = HotelGroup::select('id', 'name')
            ->withCount('hotels');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $hotelGroups = $query->forPage($page, $perPage)->get();

        return response()->json([
            'data' => $hotelGroups
        ], 200);
    }

    public function hotelMealPlans(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 5);
        $search = $request->query('search', '');
        $query = MealPlan::select('id', 'name', 'description')
            ->distinct()
            ->with([
                'prices' => function ($query) {
                    $query->select('meal_plan_id', 'hotel_id');
                }
            ])
            ->get()
            ->map(function ($mealPlan) {
                return [
                    'id' => $mealPlan->id,
                    'name' => $mealPlan->name,
                    'description' => $mealPlan->description,
                    'hotel_count' => $mealPlan->prices->pluck('hotel_id')->unique()->count()
                ];
            });
        if ($search) {
            $query = $query->filter(function ($mealPlan) use ($search) {
                return str_contains(strtolower($mealPlan['name']), strtolower($search));
            });
        }
        $mealPlans = $query->forPage($page, $perPage)->values();

        return response()->json([
            'data' => $mealPlans
        ], 200);
    }

    public function hotelRoomTypes(Request $request)
{
    $page = $request->query('page', 1);
    $perPage = $request->query('per_page', 5);
    $search = $request->query('search', '');

    $query = \App\Models\RoomType::select('id', 'name')
        ->withCount('hotels');

    if ($search) {
        $query->whereRaw("LOWER(SUBSTRING_INDEX(name, '(', 1)) LIKE ?", ['%' . strtolower($search) . '%']);
    }

    $total = $query->count();

    $roomTypes = $query->forPage($page, $perPage)
        ->get()
        ->map(function ($roomType) {
            return [
                'id' => $roomType->id,
                'name' => trim(strtok($roomType->name, '(')),
                'hotel_count' => (int) $roomType->hotels_count
            ];
        });

    return response()->json([
        'data' => $roomTypes,
        'total' => $total
    ], 200);
}
}
