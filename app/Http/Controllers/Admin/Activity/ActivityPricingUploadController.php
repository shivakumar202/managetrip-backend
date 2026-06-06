<?php

namespace App\Http\Controllers\Admin\Activity;

use App\Http\Controllers\Controller;
use App\Imports\ActivityPricingMatrix;
use App\Models\Activities;
use App\Models\ActivitySeason;
use App\Models\PaxCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ActivityPricingUploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
  public function index(Request $request)
{
    $page = (int) $request->query('page', 1);
    $perPage = (int) $request->query('per_page', 10);
    $search = $request->query('search', '');
    $sortKey = $request->query('sort_key', 'name');
    $sortDir = $request->query('sort_dir', 'asc');

    $activities = Activities::with('prices.paxCategory', 'prices.seasonDateRange')->get();

    $data = [];

    foreach ($activities as $activity) {
        foreach ($activity->prices as $price) {
            $range = $price->seasonDateRange;
            if (!$range) continue;

            $data[] = [
                'id' => $activity->id . '-' . $price->pax_category_id . '-' . $range->id,
                'activity_id' => $activity->id,
                'name' => $activity->name,
                'service' => $activity->service,
                'description' => $activity->description,
                'open_time' => $activity->open_time,
                'close_time' => $activity->close_time,
                'duration' => $activity->duration,
                'slots' => $activity->slots,
                'pax_category' => $price->paxCategory->name ?? null,
                'base_price' => $price->price,
                'season_dates' => date('d M, Y', strtotime($range->start_date)) . ' - ' . date('d M, Y', strtotime($range->end_date)),
                'start_date' => $range->start_date,
                'end_date' => $range->end_date,
                'updated_at' => $activity->updated_at,
            ];
        }
    }

    if (!empty($search)) {
        $data = array_filter($data, function ($item) use ($search) {
            return stripos($item['name'], $search) !== false ||
                   stripos($item['service'], $search) !== false ||
                   stripos($item['pax_category'], $search) !== false;
        });
    }

    usort($data, function ($a, $b) use ($sortKey, $sortDir) {
        $valA = $a[$sortKey] ?? '';
        $valB = $b[$sortKey] ?? '';
        if ($valA == $valB) return 0;
        return $sortDir === 'asc' ? $valA <=> $valB : $valB <=> $valA;
    });

    $total = count($data);
    $offset = ($page - 1) * $perPage;
    $paginatedData = array_slice($data, $offset, $perPage);

    return response()->json([
        'data' => array_values($paginatedData),
        'meta' => [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ]
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
        Log::info('upload_start');

        $request->validate([
            'file' => 'required|file|mimes:csv',
        ]);

        Excel::import(new ActivityPricingMatrix, $request->file('file'));
        return response()->json([
            'message' => 'Activity pricing matrix imported successfully'
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
