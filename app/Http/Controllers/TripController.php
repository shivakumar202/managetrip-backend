<?php

namespace App\Http\Controllers;

use App\Models\Query;
use App\Models\QuoteActivity;
use App\Models\QuoteFerry;
use App\Models\QuoteHotel;
use App\Models\Quotes;
use App\Models\QuoteTransport;
use Illuminate\Http\Request;
use League\Uri\QueryString;

class TripController extends Controller
{
    public function getQuoteHotels($tripId, $quotecode)
    {

        $trip = Query::where('query_id', $tripId)->first();
        $quote = Quotes::where('quote_code', $quotecode)->where('query_id', $trip->id)->first();

        // Fetch hotels related to the quote
        $hotels = QuoteHotel::where('quote_id', $quote->id)->get();

        return response()->json([
            'hotels' => $hotels,
        ]);
    }


    public function getQuoteActivities($tripId, $quotecode)
    {
        $trip = Query::where('query_id', $tripId)->first();
        $quote = Quotes::where('quote_code', $quotecode)->where('query_id', $trip->id)->first();

        // Fetch activities related to the quote
        $activities = QuoteActivity::where('quote_id', $quote->id)->get();

        return response()->json([
            'activities' => $activities,
        ]);
    }

    public function getQuoteTransports($tripId, $quotecode)
    {
        $trip = Query::where('query_id', $tripId)->first();
        $quote = Quotes::where('quote_code', $quotecode)->where('query_id', $trip->id)->first();

        $transports = QuoteTransport::where('quote_id', $quote->id)->get();

        return response()->json([
            'transports' => $transports,
        ]);
    }

    public function getQuoteFerries($tripId, $quotecode)
    {
        $trip = Query::where('query_id', $tripId)->first();
        $quote = Quotes::where('quote_code', $quotecode)->where('query_id', $trip->id)->first();

        $ferries = QuoteFerry::where('quote_id', $quote->id)->get();

        return response()->json([
            'ferries' => $ferries,
        ]);
    }
}
