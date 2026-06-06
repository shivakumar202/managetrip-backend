<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GoogleFlightsService;

class FlightsController extends Controller
{
    public function __construct(
        private GoogleFlightsService $service
    ) {
    }

    public function search(
        Request $request
    ) {
        return response()->json(
            $this->service->search(
                $request->all()
            )
        );
    }

    public function airports(
        Request $request
    ) {
        return response()->json(
            $this->service->airportSearch(
                $request->term
            )
        );
    }

    public function calendar(
        Request $request
    ) {
        return response()->json(
            $this->service->priceCalendar(
                $request->all()
            )
        );
    }

    public function details(
        Request $request
    ) {
        $validated = $request->validate([
            'flight_number' => 'required|string|max:50',
            'date' => 'required|date',
            'airport' => 'nullable|string|max:10',
            'airline_iata' => 'nullable|string|max:10',
            'language' => 'nullable|string|max:5',
            'country' => 'nullable|string|max:5',
        ]);

        return response()->json(
            $this->service->flightDetails(
                $validated
            )
        );
    }
}