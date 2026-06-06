<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use App\Models\SeasonDateRange;
use App\Models\PaxCategories;

class FerryPricingMatrix implements ToCollection
{
    public function collection(Collection $collection)
    {
        DB::transaction(function () use ($collection) {

            $rows = $collection->toArray();

            if (count($rows) < 6) return;

            $dateRanges = [];

            foreach ($rows as $row) {
                foreach ($row as $cell) {
                    $cell = trim($cell ?? '');
                    if (!$cell) continue;

                    if (str_contains($cell, ' - ')) {
                        $parts = explode(' - ', $cell);
                        if (count($parts) === 2) {
                            try {
                                $dateRanges[] = [
                                    'start_date' => Carbon::parse(trim($parts[0]))->format('Y-m-d'),
                                    'end_date'   => Carbon::parse(trim($parts[1]))->format('Y-m-d'),
                                ];
                            } catch (\Exception $e) {}
                        }
                    }
                }
            }

            $dateRanges = array_values(array_unique($dateRanges, SORT_REGULAR));

            $seasonIds = [];
            foreach ($dateRanges as $range) {
                $season = SeasonDateRange::updateOrCreate($range);
                $seasonIds[] = $season->id;
            }

            $paxNames = ['Adult', 'Child (3-12)', 'Child (1-2)'];
            $paxIds = [];

            foreach ($paxNames as $name) {
                $pax = PaxCategories::firstOrCreate(['name' => $name]);
                $paxIds[] = $pax->id;
            }

            $current = [
                'group' => null,
                'ferry' => null,
                'from' => null,
                'to' => null,
                'departure' => null,
            ];

            foreach ($rows as $index => $row) {

                if ($index < 3) continue;

                $row = array_map(fn($v) => trim($v ?? ''), $row);

                if ($row[0]) $current['group'] = $row[0];
                if ($row[1]) $current['ferry'] = $row[1];
                if ($row[2]) $current['from'] = $row[2];
                if ($row[3]) $current['to'] = $row[3];
                if ($row[4]) $current['departure'] = $row[4];

                $className = trim(str_replace(["\n", "\r"], '', $row[5] ?? ''));

                if (!$current['ferry'] || !$current['from'] || !$current['to']) continue;

                $ferry = DB::table('ferries')->where('name', $current['ferry'])->first();

                if (!$ferry) {
                    $ferryId = DB::table('ferries')->insertGetId([
                        'name' => $current['ferry'],
                        'operator' => $current['group'],
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $ferry = (object)['id' => $ferryId];
                }

                $route = DB::table('ferry_routes')
                    ->where('from', $current['from'])
                    ->where('to', $current['to'])
                    ->first();

                if (!$route) {
                    $routeId = DB::table('ferry_routes')->insertGetId([
                        'from' => $current['from'],
                        'to' => $current['to'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $route = (object)['id' => $routeId];
                }

                $ferryRoute = DB::table('ferry_route')
                    ->where('ferry_id', $ferry->id)
                    ->where('route_id', $route->id)
                    ->first();

                if (!$ferryRoute) {
                    DB::table('ferry_route')->insert([
                        'ferry_id' => $ferry->id,
                        'route_id' => $route->id,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $classId = null;

                if ($className) {
                    $class = DB::table('ferry_classes')
                        ->where('ferry_id', $ferry->id)
                        ->where('class_name', $className)
                        ->first();

                    if (!$class) {
                        $classId = DB::table('ferry_classes')->insertGetId([
                            'ferry_id' => $ferry->id,
                            'class_name' => $className,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $classId = $class->id;
                    }
                }

                $columnMap = [
                    6 => [0, 0],
                    7 => [0, 1],
                    8 => [0, 2],
                    9 => [1, 0],
                    10 => [1, 1],
                    11 => [1, 2],
                ];

                foreach ($columnMap as $col => [$seasonIndex, $paxIndex]) {

                    $price = $row[$col] ?? null;

                    if (!is_numeric($price)) continue;

                    $seasonId = $seasonIds[$seasonIndex] ?? null;
                    $paxId    = $paxIds[$paxIndex] ?? null;

                    if (!$seasonId || !$paxId) continue;

                    DB::table('ferry_pricings')->updateOrInsert(
                        [
                            'ferry_id' => $ferry->id,
                            'route_id' => $route->id,
                            'class_id' => $classId,
                            'pax_id' => $paxId,
                            'season_date_range_id' => $seasonId,
                            'departure' => $current['departure'],
                        ],
                        [
                            'price' => (int)$price,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        });
    }
}