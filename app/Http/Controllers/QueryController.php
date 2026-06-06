<?php

namespace App\Http\Controllers;

use App\Models\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QueryController extends Controller
{
    /**
     * Store a newly created query in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validated = $request->validate([
            'query_source_name' => 'required|string',
            'query_source_id' => 'nullable|string',
            'reference_id' => 'nullable|string',
            'sales_team_id' => 'nullable|string',
            'sales_team_name' => 'nullable|string',
            'tags' => 'nullable|string',
            'destination' => 'required|string',
            'start_date' => 'required|date',
            'nights' => 'required|integer|min:1',
            'days' => 'required|integer|min:1',
            'adults' => 'required|integer|min:1',
            'children_ages' => 'nullable|string',
            'salutation' => 'nullable|string',
            'guest_name' => 'required|string',
            'phone_numbers' => 'nullable|string',
            'email_addresses' => 'nullable|string',
            'origin_city' => 'nullable|string',
            'nationality' => 'nullable|string',
            'comments' => 'nullable|string',
        ]);

        try {
            // Create new query

            $query_id = Query::latest();

            $queryId = explode('AB', $query_id->count() > 0 ? $query_id->first()->query_id : '0AB0')[1] + 1;
            $query_id = 'AB' . str_pad($queryId, 5, '0', STR_PAD_LEFT);


            $query = Query::create([
                'query_id' => $query_id,
                'source' => $validated['query_source_name'],
                'reference_id' => $validated['reference_id'],
                'sales_team_id' => $validated['sales_team_id'],
                'destination' => $validated['destination'],
                'start_date' => $validated['start_date'],
                'nights' => $validated['nights'],
                'adults' => $validated['adults'],
                'children' => count(array_filter(array_map('trim', explode(',', $validated['children_ages'] ?? '')))) ?? 0,
                'children_ages' => $validated['children_ages'],
                'salutation' => $validated['salutation'],
                'name' => $validated['guest_name'],
                'email' => $validated['email_addresses'],
                'phone' => $validated['phone_numbers'],
                'origin' => $validated['origin_city'],
                'nationality' => $validated['nationality'],
                'comments' => $validated['comments'],
                'created_by' => Auth::id() ?? null,
                'status' => 0, // Status: 1 = New Query
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Query created successfully',
                'data' => $query,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create query: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all queries with pagination.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        $queries = Query::paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $queries,
        ]);
    }

    /**
     * Get a specific query by ID.
     */
    public function show($id)
    {
        $query = Query::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $query,
        ]);
    }

    /**
     * Update a query.
     */
    public function update(Request $request, $id)
    {
        $query = Query::findOrFail($id);

        $validated = $request->validate([
            'query_source_name' => 'string',
            'reference_id' => 'nullable|string',
            'sales_team_id' => 'nullable|string',
            'destination' => 'string',
            'start_date' => 'date',
            'nights' => 'integer|min:1',
            'adults' => 'integer|min:1',
            'children_ages' => 'nullable|string',
            'salutation' => 'nullable|string',
            'guest_name' => 'string',
            'phone_numbers' => 'nullable|string',
            'email_addresses' => 'nullable|string',
            'origin_city' => 'nullable|string',
            'nationality' => 'nullable|string',
            'comments' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $query->update([
                'source' => $validated['query_source_name'] ?? $query->source,
                'reference_id' => $validated['reference_id'] ?? $query->reference_id,
                'sales_team_id' => $validated['sales_team_id'] ?? $query->sales_team_id,
                'destination' => $validated['destination'] ?? $query->destination,
                'start_date' => $validated['start_date'] ?? $query->start_date,
                'nights' => $validated['nights'] ?? $query->nights,
                'adults' => $validated['adults'] ?? $query->adults,
                'children_ages' => $validated['children_ages'] ?? $query->children_ages,
                'salutation' => $validated['salutation'] ?? $query->salutation,
                'name' => $validated['guest_name'] ?? $query->name,
                'email' => $validated['email_addresses'] ?? $query->email,
                'phone' => $validated['phone_numbers'] ?? $query->phone,
                'origin' => $validated['origin_city'] ?? $query->origin,
                'nationality' => $validated['nationality'] ?? $query->nationality,
                'comments' => $validated['comments'] ?? $query->comments,
                'status' => $validated['status'] ?? $query->status,
                'updated_by' => Auth::id() ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Query updated successfully',
                'data' => $query,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update query: ' . $e->getMessage(),
            ], 500);
        }
    }
}
