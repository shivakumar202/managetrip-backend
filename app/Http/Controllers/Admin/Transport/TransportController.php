<?php

namespace App\Http\Controllers\Admin\Transport;

use App\Http\Controllers\Controller;
use App\Imports\TransportPricingMatrix;
use App\Models\Duty;
use App\Models\DutyPrice;
use App\Models\Season;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TransportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search', '');
        $sortKey = $request->query('sort_key', 'duty_code');
        $sortDir = $request->query('sort_dir', 'asc');

        // Build query for duty prices (flattened data)
        // Only keep the latest price record for each duty and vehicle combination.
        $latestPriceIds = DutyPrice::selectRaw('MAX(id) AS id')
            ->groupBy('duty_id', 'vehicle_id');

        $query = DutyPrice::with('duty', 'vehicle', 'seasonDateRange')
            ->whereIn('duty_prices.id', $latestPriceIds);

        // Apply search filter
        if (!empty($search)) {
            $query->whereHas('duty', function ($q) use ($search) {
                $q->where('point_a', 'like', "%{$search}%")
                    ->orWhere('point_b', 'like', "%{$search}%")
                    ->orWhere('duty_code', 'like', "%{$search}%")
                    ->orWhere('service', 'like', "%{$search}%");
            })
                ->orWhereHas('vehicle', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });

        }

        // Get total count before applying joins for sorting
        $totalQuery = clone $query;
        $total = $totalQuery->count();

        // Apply sorting with joins
        if ($sortKey === 'point_a' || $sortKey === 'point_b' || $sortKey === 'service' || $sortKey === 'duty_code') {
            $query->join('duties', 'duty_prices.duty_id', '=', 'duties.id')
                ->orderBy('duties.' . $sortKey, $sortDir)
                ->select('duty_prices.*');
        } elseif ($sortKey === 'vehicle') {
            $query->join('vehicles', 'duty_prices.vehicle_id', '=', 'vehicles.id')
                ->orderBy('vehicles.name', $sortDir)
                ->select('duty_prices.*');
        } elseif ($sortKey === 'price') {
            $query->orderBy('price', $sortDir);
        } else {
            $query->join('duties', 'duty_prices.duty_id', '=', 'duties.id')
                ->orderBy('duties.duty_code', $sortDir)
                ->select('duty_prices.*');
        }

        // Apply pagination to the sorted results
        $offset = ($page - 1) * $perPage;
        $dutyPrices = $query->skip($offset)->take($perPage)->get();

        Log::info("Transport pagination debug", [
            'page' => $page,
            'perPage' => $perPage,
            'offset' => $offset,
            'total_from_query' => $total,
            'actual_results_count' => $dutyPrices->count(),
            'first_few_ids' => $dutyPrices->take(3)->pluck('id')->toArray()
        ]);

        // Transform the data
        $rows = $dutyPrices->map(function (DutyPrice $price) {
            return [
                'id' => $price->duty_id . '-' . $price->id,
                'duty_id' => $price->duty_id,
                'duty_code' => $price->duty?->duty_code,
                'service' => $price->duty?->service,
                'point_a' => $price->duty?->point_a,
                'point_b' => $price->duty?->point_b,
                'distance' => $price->duty?->distance ?? 0,
                'vehicle' => $price->vehicle?->name,
                'base_price' => (float) $price->price,
                'season_dates' => $this->formatSeasonDates($price->seasonDateRange),
                'start_date' => $price->seasonDateRange?->start_date,
                'end_date' => $price->seasonDateRange?->end_date,
                'availability' => 'available',
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
    private function formatSeasonDates($seasonDateRange)
    {
        if (!$seasonDateRange) {
            return '';
        }

        $startDate = Carbon::parse($seasonDateRange->start_date)->format('d M, Y');
        $endDate = Carbon::parse($seasonDateRange->end_date)->format('d M, Y');

        return "{$startDate} - {$endDate}";
    }

    public function transportServices(Request $request)
    {

        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search', '');
        $sortKey = $request->query('sort_key', 'duty_code');
        $sortDir = $request->query('sort_dir', 'asc');

        $query = Duty::query();

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('point_a', 'like', "%{$search}%")
                    ->orWhere('point_b', 'like', "%{$search}%")
                    ->orWhere('service', 'like', "%{$search}%")
                    ->orWhere('duty_code', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        if (in_array($sortKey, ['duty_code', 'point_a', 'point_b', 'service', 'distance'])) {
            $query->orderBy($sortKey, $sortDir);
        } else {
            $query->orderBy('duty_code', $sortDir);
        }

        // Get total count BEFORE pagination
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $services = $query->with('prices.vehicle', 'prices.seasonDateRange')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return response()->json([
            'services' => $services,
            'total' => $total
        ]);
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
        $request->validate([
            'file' => 'required|file|mimes:csv'
        ]);
        Excel::import(new TransportPricingMatrix, $request->file('file'));
        return response()->json([
            'message' => 'Transport pricing matrix imported successfully'
        ], 200);
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
