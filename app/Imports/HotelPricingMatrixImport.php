<?php

namespace App\Imports;

use App\Models\Hotels as Hotel;
use App\Models\HotelGroup;
use App\Models\RoomType;
use App\Models\MealPlan;
use App\Models\Extra;
use App\Models\HotelPrice;
use App\Models\HotelPriceExtra;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class HotelPricingMatrixImport implements ToCollection, WithMultipleSheets, WithCalculatedFormulas
{
    public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $headerRow1 = $rows[0];
            $headerRow3 = $rows[3];

            $columnMap = [];
            $currentSeason = null;

            for ($i = 5; $i < count($headerRow3); $i++) {

                if (($i - 5) % 3 === 0) {
                    $seasonName = trim($headerRow1[$i] ?? '');
                    if ($seasonName !== '') {
                        $currentSeason = $seasonName;
                    }
                }

                if (!$currentSeason) continue;

                $mod = ($i - 5) % 3;

                $meal = match ($mod) {
                    0 => 'CP',
                    1 => 'MAP',
                    2 => 'AP',
                };

                $groupStart = $i - (($i - 5) % 3);

                $rangeParts = [];

                for ($r = 1; $r <= 3; $r++) {
                    if (!empty(trim($rows[$r][$groupStart] ?? ''))) {
                        $rangeParts[] = trim($rows[$r][$groupStart]);
                    }
                }

                $columnMap[$i] = [
                    'meal' => $meal,
                    'range' => implode("\n", $rangeParts)
                ];
            }

            $extrasMap = Extra::pluck('id', 'code')->toArray();
            $dateRangeCache = [];

            $hotel = null;
            $lastRoom = null;

            $defaultGroup = HotelGroup::firstOrCreate(['name' => 'Default Hotel Group']);

            foreach ($rows->slice(4) as $row) {

                if (!isset($row[4]) || !trim($row[4])) continue;

                $name = trim($row[4]);
                $isExtrasRow = isset($row[2]) && strtolower(trim($row[2])) === 'extras';

                if (!$isExtrasRow && isset($row[2]) && $row[2]) {
                    $hotel = Hotel::firstOrCreate(
                        [
                            'name' => trim($row[2]),
                            'hotel_group_id' => $defaultGroup->id
                        ],
                        [
                            'location' => trim($row[1]),
                            'star' => is_numeric($row[3]) ? (int)$row[3] : null,
                            'hotel_group_id' => $defaultGroup->id
                        ]
                    );
                }

                if (!$hotel) continue;

                if (
                    $isExtrasRow ||
                    str_contains(strtolower($name), 'extra') ||
                    str_contains(strtolower($name), 'child')
                ) {
                    $this->saveExtras($hotel, $lastRoom, $row, $columnMap, $extrasMap, $dateRangeCache);
                    continue;
                }

                $room = RoomType::firstOrCreate(['name' => $name]);
                $lastRoom = $room;

                $hotel->roomTypes()->syncWithoutDetaching([$room->id]);

                foreach ($columnMap as $colIndex => $map) {

                    $priceValue = $row[$colIndex] ?? null;
                    if ($priceValue === null || $priceValue === '') continue;
                    $priceValue = is_numeric($priceValue) ? $priceValue : floatval($priceValue);

                    $meal = MealPlan::firstOrCreate(['name' => $map['meal']]);

                    $ranges = $this->parseSeasonRanges($map['range']);

                    foreach ($ranges as [$start, $end]) {

                        $cacheKey = "{$start}_{$end}";

                        if (!isset($dateRangeCache[$cacheKey])) {
                            $dateRangeCache[$cacheKey] = DB::table('season_date_ranges')->insertGetId([
                                'start_date' => $start,
                                'end_date' => $end,
                            ]);
                        }

                        $rangeId = $dateRangeCache[$cacheKey];

                        HotelPrice::updateOrCreate(
                            [
                                'hotel_id' => $hotel->id,
                                'room_type_id' => $room->id,
                                'meal_plan_id' => $meal->id,
                                'season_date_ranges_id' => $rangeId,
                            ],
                            [
                                'base_price' => $priceValue
                            ]
                        );
                    }
                }
            }
        });
    }

    private function parseSeasonRanges(string $range): array
    {
        $lines = preg_split('/[\r\n]+/', $range);
        $results = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            if (preg_match('/^(.+?)\s*(?:-|–|—|to)\s*(.+)$/i', $line, $m)) {
                try {
                    $startRaw = trim($m[1]);
                    $endRaw   = trim($m[2]);

                    if (!preg_match('/\d{4}/', $startRaw)) {
                        $startRaw .= ' ' . date('Y');
                    }

                    if (!preg_match('/\d{4}/', $endRaw)) {
                        $endRaw .= ' ' . date('Y');
                    }

                    $start = Carbon::parse($startRaw)->toDateString();
                    $end   = Carbon::parse($endRaw)->toDateString();

                    if ($start <= $end) {
                        $results[] = [$start, $end];
                    }
                } catch (\Exception $e) {}
            }
        }

        return collect($results)->unique()->values()->toArray();
    }

    private function saveExtras($hotel, $room, $row, $columnMap, &$extrasMap, &$dateRangeCache)
    {
        if (!$room) return;

        $name = strtolower($row[4]);

        $code = match (true) {
            str_contains($name, 'adult') => 'AWB',
            str_contains($name, 'without') => 'CNB',
            str_contains($name, 'child') => 'CWB',
            default => null,
        };

        if (!$code) return;

        if (!isset($extrasMap[$code])) {
            $extra = Extra::firstOrCreate(['code' => $code]);
            $extrasMap[$code] = $extra->id;
        }

        foreach ($columnMap as $colIndex => $map) {

            $value = $row[$colIndex] ?? null;
            if ($value === null || $value === '') continue;
            $value = is_numeric($value) ? $value : floatval($value);

            $meal = MealPlan::firstOrCreate(['name' => $map['meal']]);

            $ranges = $this->parseSeasonRanges($map['range']);

            foreach ($ranges as [$start, $end]) {

                $cacheKey = "{$start}_{$end}";
                $rangeId = $dateRangeCache[$cacheKey] ?? null;

                if (!$rangeId) continue;

                $exists = HotelPriceExtra::where([
                    'hotel_id' => $hotel->id,
                    'extra_id' => $extrasMap[$code],
                    'meal_plan_id' => $meal->id,
                    'season_date_ranges_id' => $rangeId,
                ])->exists();

                if ($exists) continue;

                HotelPriceExtra::create([
                    'hotel_id' => $hotel->id,
                    'extra_id' => $extrasMap[$code],
                    'meal_plan_id' => $meal->id,
                    'season_date_ranges_id' => $rangeId,
                    'price' => $value
                ]);
            }
        }
    }
}