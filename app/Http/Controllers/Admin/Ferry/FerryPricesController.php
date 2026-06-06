<?php

namespace App\Http\Controllers\Admin\Ferry;

use App\Http\Controllers\Controller;
use App\Imports\FerryPricingMatrix;
use App\Models\FerryPricing;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FerryPricesController extends Controller
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

        $ferryPrices = FerryPricing::with('ferry', 'class', 'route','paxCategory', 'seasonDateRange')->get();

        $data = [];

        foreach ($ferryPrices as $price) {
            $data[] = [
                'id' => $price->id,
                'ferry_name' => $price->ferry->name ?? null,
                'operator' => $price->ferry->operator ?? null,
                'from' => $price->route->from ?? null,
                'to' => $price->route->to ?? null,
                'class_name' => $price->class->class_name ?? null,
                'pax_category' => $price->paxCategory->name ?? null,
                'departure' => $price->departure,
                'start_date' => $price->seasonDateRange->start_date ?? null,
                'end_date' => $price->seasonDateRange->end_date ?? null,
                'price' => $price->price,
                'updated_at' => $price->updated_at,
            ];
        }

        if (!empty($search)) {
            $data = array_filter($data, function ($item) use ($search) {
                return stripos($item['ferry_name'], $search) !== false ||
                    stripos($item['from'], $search) !== false ||
                    stripos($item['to'], $search) !== false ||
                    stripos($item['class_name'], $search) !== false;
            });
        }

        usort($data, function ($a, $b) use ($sortKey, $sortDir) {
            $valA = $a[$sortKey] ?? '';
            $valB = $b[$sortKey] ?? '';
            if ($valA == $valB)
                return 0;
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
        $request->validate([
            'file' => 'required|mimes:csv'
        ]);

        Excel::import(new FerryPricingMatrix, $request->file('file'));
        return response()->json([
            'message' => 'Import success',
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
