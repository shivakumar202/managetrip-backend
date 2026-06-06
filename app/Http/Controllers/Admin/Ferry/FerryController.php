<?php

namespace App\Http\Controllers\Admin\Ferry;

use App\Http\Controllers\Controller;
use App\Models\Ferry;
use Illuminate\Http\Request;

class FerryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 5);
        $sortKey = $request->query('sort_key', 'operator');
        $sortDir = $request->query('sort_dir', 'asc');

        $operatorsQuery = Ferry::select('operator')
            ->distinct()
            ->orderBy($sortKey, $sortDir);

        $totalOperators = $operatorsQuery->count();

        $operators = $operatorsQuery
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->pluck('operator');

        $ferries = Ferry::with(['classes', 'routes.route'])
            ->whereIn('operator', $operators)
            ->orderBy($sortKey, $sortDir)
            ->get();

        $grouped = [];

        foreach ($ferries as $ferry) {
            $operator = $ferry->operator;

            if (!isset($grouped[$operator])) {
                $grouped[$operator] = [
                    'operator' => $operator,
                    'services' => [],
                ];
            }

            foreach ($ferry->routes as $ferryRoute) {
                $route = $ferryRoute->route; // Get the FerryRoutes model

                if ($ferry->classes && $ferry->classes->count() > 0) {
                    foreach ($ferry->classes as $class) {
                        $grouped[$operator]['services'][] = [
                            'id' => $ferry->id . '-' . $route->id . '-' . ($class->id ?? 0),
                            'ferry_id' => $ferry->id,
                            'ferry_name' => $ferry->name,
                            'route_from' => $route->from,
                            'route_to' => $route->to,
                            'class_name' => $class->class_name ?? null,
                            'operator' => $operator,
                            'updated_at' => $ferry->updated_at,
                        ];
                    }
                } else {
                    $grouped[$operator]['services'][] = [
                        'id' => $ferry->id . '-' . $route->id . '-0',
                        'ferry_id' => $ferry->id,
                        'ferry_name' => $ferry->name,
                        'route_from' => $route->from,
                        'route_to' => $route->to,
                        'class_name' => null,
                        'operator' => $operator,
                        'updated_at' => $ferry->updated_at,
                    ];
                }
            }
        }

        return response()->json([
            'data' => array_values($grouped),
            'meta' => [
                'total' => $totalOperators,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalOperators / $perPage),
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
