<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GoogleFlightsService
{
    private function httpClient()
    {
        $verify = filter_var(
            env('SERPAPI_VERIFY', true),
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($verify === false) {
            return Http::withoutVerifying();
        }

        return Http::withOptions([
            'verify' => true,
        ]);
    }

    private function resolveAirportCode(string $term): ?string
    {
        $term = trim($term);
        if ($term === '') {
            return null;
        }

        if (preg_match('/^[A-Z]{3}$/', strtoupper($term))) {
            return strtoupper($term);
        }

        $searchResponse = $this->airportSearch($term);
        if (!is_array($searchResponse)) {
            return null;
        }

        $textValues = [];
        $sections = [
            'organic_results',
            'local_results',
            'related_questions',
            'knowledge_graph',
            'answer_box',
        ];

        foreach ($sections as $section) {
            if (empty($searchResponse[$section])) {
                continue;
            }

            if (is_array($searchResponse[$section])) {
                foreach ($searchResponse[$section] as $item) {
                    if (is_string($item)) {
                        $textValues[] = $item;
                        continue;
                    }

                    foreach (['title', 'snippet', 'description', 'name'] as $field) {
                        if (!empty($item[$field])) {
                            $textValues[] = $item[$field];
                        }
                    }
                }

                continue;
            }

            if (is_string($searchResponse[$section])) {
                $textValues[] = $searchResponse[$section];
            }
        }

        if (!empty($searchResponse['answer_box']) && is_array($searchResponse['answer_box'])) {
            foreach (['title', 'snippet', 'description'] as $field) {
                if (!empty($searchResponse['answer_box'][$field])) {
                    $textValues[] = $searchResponse['answer_box'][$field];
                }
            }
        }

        $haystack = strtoupper(implode(' ', $textValues));
        if (preg_match('/\b([A-Z]{3})\b/', $haystack, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function search(array $data)
    {
        $departureId = isset($data['from']) ? strtoupper(trim($data['from'])) : null;
        $arrivalId = isset($data['to']) ? strtoupper(trim($data['to'])) : null;

        if ($departureId && !preg_match('/^[A-Z]{3}$/', $departureId)) {
            $resolved = $this->resolveAirportCode($departureId);
            if ($resolved) {
                $departureId = $resolved;
            }
        }

        if ($arrivalId && !preg_match('/^[A-Z]{3}$/', $arrivalId)) {
            $resolved = $this->resolveAirportCode($arrivalId);
            if ($resolved) {
                $arrivalId = $resolved;
            }
        }

        $cacheKey = 'flight_search_' . md5(
            json_encode($data)
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($data, $departureId, $arrivalId) {

                $response = $this->httpClient()->timeout(60)
                    ->get(
                        config('services.serpapi.url'),
                        [
                            'engine' => 'google_flights',

                            'departure_id' => $departureId,

                            'arrival_id' => $arrivalId,

                            'outbound_date' =>
                                $data['departure_date'],

                            'return_date' =>
                                $data['return_date'] ?? null,

                            'currency' =>
                                $data['currency'] ?? 'INR',

                            'hl' =>
                                $data['language'] ?? 'en',

                            'adults' =>
                                $data['adults'] ?? 1,

                            'children' =>
                                $data['children'] ?? 0,

                            'infants_in_seat' =>
                                $data['infants'] ?? 0,

                            'travel_class' =>
                                $data['class'] ?? 1,

                            'type' =>
                                $data['trip_type'] ?? 2,

                            'api_key' =>
                                config('services.serpapi.key'),
                        ]
                    );

                if (!$response->successful()) {
                    throw new \Exception(
                        'Flights fetch failed: ' . $response->status() . ' ' . $response->body(),
                    );
                }

                return $response->json();
            }
        );
    }

    public function airportSearch($term)
    {
        $response = $this->httpClient()->get(
            config('services.serpapi.url'),
            [
                'engine' => 'google',
                'q' => "{$term} airport code",
                'api_key' =>
                    config('services.serpapi.key'),
            ]
        );

        return $response->json();
    }

    public function priceCalendar(array $data)
    {
        $response = $this->httpClient()->get(
            config('services.serpapi.url'),
            [
                'engine' => 'google_flights',

                'departure_id' =>
                    $data['from'],

                'arrival_id' =>
                    $data['to'],

                'outbound_date' =>
                    $data['date'],

                'gl' => 'in',

                'currency' => 'INR',

                'api_key' =>
                    config('services.serpapi.key'),
            ]
        );

        return $response->json();
    }

    public function flightDetails(array $data)
    {
        $cacheKey = 'flight_details_' . md5(
            json_encode($data)
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($data) {
                $query = [
                    'engine' => 'flight_status',
                    'flight' => $data['flight_number'],
                    'date' => $data['date'],
                    'api_key' => config('services.serpapi.key'),
                ];

                if (!empty($data['airport'])) {
                    $query['airport'] = $data['airport'];
                }

                if (!empty($data['airline_iata'])) {
                    $query['airline_iata'] = $data['airline_iata'];
                }

                if (!empty($data['language'])) {
                    $query['hl'] = $data['language'];
                }

                if (!empty($data['country'])) {
                    $query['gl'] = $data['country'];
                }

                $response = $this->httpClient()->timeout(60)
                    ->get(
                        config('services.serpapi.url'),
                        $query
                    );

                if (!$response->successful()) {
                    throw new \Exception(
                        'Flight details fetch failed'
                    );
                }

                return $response->json();
            }
        );
    }
}