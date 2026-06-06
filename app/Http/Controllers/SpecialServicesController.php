<?php

namespace App\Http\Controllers;

use App\Imports\SpecialServicePricingMatrix;
use App\Models\SpecialServices;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SpecialServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 5);
        $search = trim($request->query('search', ''));

        $query = SpecialServices::with([
            'pricings.seasonDateRange'
        ]);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $services = $query
            ->latest()
            ->get();

        $rows = [];

        foreach ($services as $service) {

            foreach ($service->pricings as $pricing) {

                $season = $pricing->seasonDateRange;

                if (!$season) {
                    continue;
                }

                $rows[] = [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'import_source' => $service->import_source,
                    'updated_by' => $service->updated_by,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,

                    'start_date' => $season->start_date,
                    'end_date' => $season->end_date,
                    'price' => (float) $pricing->price,
                ];
            }
        }

        $total = count($rows);

        $paginatedRows = collect($rows)
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return response()->json([
            'data' => $paginatedRows,
          
                "total" => $total,
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
            'file' => 'required|file|mimes:xlsx',
        ]);

        Excel::import(new SpecialServicePricingMatrix, $request->file('file'));
        return response()->json([
            'message' => 'Import successful',
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
