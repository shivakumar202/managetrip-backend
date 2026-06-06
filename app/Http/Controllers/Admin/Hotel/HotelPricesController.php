<?php

namespace App\Http\Controllers\Admin\Hotel;

use App\Exports\HotelPricingMatrixExport;
use App\Http\Controllers\Controller;
use App\Imports\HotelPricingMatrixImport;
use App\Models\Hotel;
use App\Models\HotelPrice;
use App\Models\HotelPriceExtra;
use App\Models\RoomType;
use App\Models\MealPlan;
use App\Models\Season;
use App\Models\Extra;
use App\Models\HotelExtras;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class HotelPricesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search', '');
        $sortKey = $request->query('sort_key', 'hotel_id');
        $sortDir = $request->query('sort_dir', 'asc');

        // Build the query
        $query = HotelPrice::with([
            'hotel',
            'hotel.hotelGroup',
            'roomType',
            'mealPlan',
            'seasonDateRange',
        ]);

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('hotel', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                          ->orWhere('location', 'like', "%{$search}%");
                })
                ->orWhereHas('mealPlan', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('roomType', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Get total count before pagination
        $total = $query->count();

        // Apply sorting
        $sortColumn = match($sortKey) {
            'hotel' => 'hotel_id',
            'price' => 'base_price',
            default => 'hotel_id',
        };
        
        $query->orderBy($sortColumn, $sortDir === 'desc' ? 'desc' : 'asc');

        $prices = $query->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get();

        $priceIds = $prices->pluck('id')->toArray();
        $extraPrices = HotelPriceExtra::with(['extra'])
            ->whereIn('hotel_id', $prices->pluck('hotel_id')->toArray())
            ->get();

        $extrasMap = [];
        foreach ($extraPrices as $extra) {
            $key = "{$extra->hotel_id}_{$extra->season_date_ranges_id}";
            if (!isset($extrasMap[$key])) {
                $extrasMap[$key] = [];
            }
            $extrasMap[$key][] = [
                'code' => $extra->extra?->code,
                'description' => $extra->extra?->description,
                'price' => (float) $extra->price,
            ];
        }

        // Format the response
        $rows = $prices->map(function ($price) use ($extrasMap) {
            $extrasKey = "{$price->hotel_id}_{$price->season_date_ranges_id}";
            $extras = $extrasMap[$extrasKey] ?? [];

            return [
                'id' => $price->id,
                'hotel' => $price->hotel?->name,
                'hotel_group' => $price->hotel?->hotelGroup?->name,
                'location' => $price->hotel?->location,
                'star' => $price->hotel?->star,
                'room' => $price->roomType?->name,
                'start_date' => $price->seasonDateRange?->start_date,
                'end_date' => $price->seasonDateRange?->end_date,
                'meal_plan' => $price->mealPlan?->name,
                'base_price' => (float) $price->base_price,
                'extras' => $extras,
            ];
        });

        return response()->json([
            'data' => $rows->values(),
            'total' => $total,
        ], 200);
    }

    /**
     * Format season dates to display format
     */
    

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
        $request->validate([
            'file' => 'required|mimes:xlsx'
        ]);

        Excel::import(new HotelPricingMatrixImport, $request->file('file'));

        return response()->json([
            'message' => 'Import success',
        ], 200);
    }

    public function downloadPricing()
{
    return Excel::download(
        new HotelPricingMatrixExport(),
        'hotel_pricing_matrix.xlsx'
    );
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
}
