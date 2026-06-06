<?php

namespace App\Imports;

use App\Models\Activities;
use App\Models\PaxCategories;
use App\Models\SeasonDateRange;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

class ActivityPricingMatrix implements ToCollection
{
    public function collection(Collection $collection)
    {
        $rows = $collection->toArray();

        if (count($rows) < 5) return;

        $paxRow    = $rows[3];
        $dataRows  = array_slice($rows, 4);

        $dateRanges = [];

        foreach ($rows as $row) {
            foreach ($row as $cell) {

                $cell = trim($cell ?? '');

                if ($cell !== '' && str_contains($cell, '-')) {

                    $parts = explode('-', $cell);

                    if (count($parts) >= 2) {
                        try {
                            $dateRanges[] = [
                                'start_date' => Carbon::parse(trim($parts[0]))->format('Y-m-d'),
                                'end_date'   => Carbon::parse(trim($parts[1]))->format('Y-m-d')
                            ];
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }

        $dateRanges = array_unique($dateRanges, SORT_REGULAR);

        if (empty($dateRanges)) return;

        $paxMap = [];
        $currentPax = null;

        foreach ($paxRow as $i => $pax) {

            $pax = trim($pax ?? '');

            if ($pax !== '') {
                $currentPax = $pax;
            }

            if ($currentPax) {
                $paxMap[$i] = $currentPax;
            }
        }

        $currentLocation = null;

        foreach ($dataRows as $row) {

            if (empty(array_filter($row))) continue;

            $location = trim($row[0] ?? '');

            if ($location !== '') {
                $currentLocation = $location;
            }

            $location = $currentLocation;

            $service = trim($row[1] ?? '');

            if (!$service) continue;

            $activity = Activities::firstOrCreate(
                [
                    'service' => $service,
                    'name'    => $location,
                ],
                [
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id()
                ]
            );

            foreach ($paxMap as $colIndex => $paxName) {

                $priceRaw = trim($row[$colIndex] ?? '');
                $price = is_numeric($priceRaw) ? (int)$priceRaw : 0;

                $paxNameLower = strtolower(trim($paxName));

                if ($paxNameLower === 'adult') {
                    $start_age = 12;
                    $end_age   = 60;
                } else {
                    preg_match('/(\d+)\s*-\s*(\d+)/', $paxName, $matches);
                    $start_age = $matches[1] ?? null;
                    $end_age   = $matches[2] ?? null;
                }

                $pax = PaxCategories::firstOrCreate(
                    [
                        'category' => 'activity',
                        'name' => $paxName
                    ],
                    [
                        'start_age' => $start_age,
                        'end_age'   => $end_age
                    ]
                );

                foreach ($dateRanges as $range) {

                    $seasonDateRange = SeasonDateRange::updateOrCreate(
                        [
                            'start_date' => $range['start_date'],
                            'end_date'   => $range['end_date'],
                        ]
                    );

                    $activity->prices()->updateOrCreate(
                        [
                            'season_date_range_id' => $seasonDateRange->id,
                            'pax_category_id'      => $pax->id
                        ],
                        [
                            'price' => $price
                        ]
                    );
                }
            }
        }
    }
}