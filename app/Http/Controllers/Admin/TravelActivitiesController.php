<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activities;
use Illuminate\Http\Request;

class TravelActivitiesController extends Controller
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

        // Fetch data
        $activities = Activities::when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('service', 'like', "%{$search}%");
        })
            ->orderBy($sortKey, $sortDir)
            ->get();

        // Group by name
        $grouped = $activities->groupBy('name')->map(function ($items, $name) {
            return [
                'id' => md5($name), // unique group id
                'name' => $name,
                'services' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'service' => $item->service,
                        'updated_at' => $item->updated_at,
                    ];
                })->values()
            ];
        })->values();

        // Pagination (manual)
        $total = $grouped->count();
        $offset = ($page - 1) * $perPage;
        $paginatedData = $grouped->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $paginatedData,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
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
}
