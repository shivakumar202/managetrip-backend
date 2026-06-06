<?php

namespace App\Imports;

use App\Models\Duty;
use App\Models\DutyPrice;
use App\Models\SeasonDateRange;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

class TransportPricingMatrix implements ToCollection
{
    public function collection(Collection $collection)
    {
        $rows = $collection->toArray();

        if (count($rows) < 6) return;

        $titleRow   = $rows[0];
        $vehicleRow = $rows[3];
        $dataRows   = array_slice($rows, 4);

        $headers = [];
        $currentVehicle = null;

        foreach ($vehicleRow as $i => $cell) {
            if ($i < 8) {
                $headers[$i] = trim($titleRow[$i] ?? '');
                continue;
            }

            $cell = trim($cell ?? '');

            if ($cell !== '') {
                $currentVehicle = $cell;
            }

            $headers[$i] = $currentVehicle;
        }

        $dateRanges = [];

        foreach ($rows as $row) {
            foreach ($row as $cell) {

                $cell = trim($cell ?? '');

                if ($cell === '') continue;

                if (str_contains($cell, ' - ')) {

                    $parts = explode(' - ', $cell);

                    if (count($parts) === 2) {
                        try {
                            $dateRanges[] = [
                                'start_date' => Carbon::parse(trim($parts[0]))->format('Y-m-d'),
                                'end_date'   => Carbon::parse(trim($parts[1]))->format('Y-m-d'),
                            ];
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }

        $dateRanges = array_unique($dateRanges, SORT_REGULAR);

        $lastA = null;
        $lastB = null;
        $autoDutyCode = Duty::max('duty_code') ?? 100;

        foreach ($dataRows as $data) {

            if (empty(array_filter($data))) continue;
            if (count($headers) !== count($data)) continue;

            $data = array_map(fn($v) =>
                mb_convert_encoding($v ?? '', 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252'),
                $data
            );

            $row = array_combine($headers, $data);

            $row['A'] = trim($row['A'] ?? '') ?: $lastA;
            $row['B'] = trim($row['B'] ?? '') ?: $lastB;

            $lastA = $row['A'];
            $lastB = $row['B'];

            if (!$row['A']) continue;

            $dutyCode = trim($row['Duty Code'] ?? '');

            if (!$dutyCode) {
                $autoDutyCode++;
                $dutyCode = $autoDutyCode;
            }

            $duty = Duty::updateOrCreate(
                [
                    'point_a' => $row['A'],
                    'point_b' => $row['B'],
                    'service' => trim($data[3] ?? '')
                ],
                [
                    'duty_code'   => $dutyCode,
                    'distance'    => is_numeric($data[4] ?? null) ? (int)$data[4] : null,
                    'start_time'  => $data[5] ?? null,
                    'duration'    => is_numeric($data[6] ?? null) ? (int)$data[6] : null,
                    'day_schedule'=> trim($data[7] ?? ''),
                ]
            );

            foreach ($headers as $index => $vehicleName) {

                if (!$vehicleName || $index < 8) continue;

                $price = $data[$index] ?? null;
                if (!is_numeric($price)) continue;

                $vehicle = Vehicle::firstOrCreate([
                    'vehicle_type' => trim($vehicleName)
                ]);

                foreach ($dateRanges as $range) {

                    $seasonDateRange = SeasonDateRange::updateOrCreate(
                        [
                            'start_date' => $range['start_date'],
                            'end_date'   => $range['end_date'],
                        ]
                    );

                    DutyPrice::updateOrCreate(
                        [
                            'duty_id'              => $duty->id,
                            'vehicle_id'           => $vehicle->id,
                            'season_date_range_id' => $seasonDateRange->id,
                        ],
                        [
                            'price' => (int)$price
                        ]
                    );
                }
            }
        }
    }
}