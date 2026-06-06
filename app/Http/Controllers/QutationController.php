<?php

namespace App\Http\Controllers;

use App\Models\Query;
use App\Models\Quotes;
use Illuminate\Http\Request;

class QutationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
{
    $validatedData = $request->validate([
        'trip_id' => 'required|string|exists:queries,query_id',
    ]);

    $query = Query::where('query_id', $validatedData['trip_id'])->first();

    $quotes = Quotes::with([
        'hotels.hotel',
        'transports.duty',
        'transports.cabs',
        'activities.activity'
    ])
    ->where('adult_count', $query->adults)
    ->where('child_count', $query->children)
    ->take(4)
    ->get();

    $formattedQuotes = $quotes->map(function ($quote) {
        return [
            'id' => $quote->id,
            'title' => $quote->title ?? 'Quote',
            'total' => $quote->package_cost ?? 0,

            'hotels' => $quote->hotels->map(function ($h) {
                return $h->hotel;
            })->values(),

            'transports' => $quote->transports->map(function ($t) {
                return [
                    'duty' => $t->duty,
                    'cab' => $t->cabs,
                ];
            })->values(),

            'activities' => $quote->activities->map(function ($a) {
                return $a->activity;
            })->values(),
        ];
    });

    return response()->json([
        'quotations' => $formattedQuotes
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
}
