<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use App\Models\SeasonDateRange;
use App\Models\SpecialServices;
use App\Models\SpecialServicePricing;

class SpecialServicePricingMatrix implements ToCollection
{
    public function collection(Collection $collection)
    {
        DB::transaction(function () use ($collection) {

            $rows = $collection->toArray();

            if (count($rows) < 3) {
                return;
            }

            $seasonRanges = [];

            $seasonRow = $rows[0] ?? [];
            $dateRow   = $rows[1] ?? [];

            for ($i = 1; $i < count($seasonRow); $i++) {

                $seasonName = trim($seasonRow[$i] ?? '');
                $dateText   = trim($dateRow[$i] ?? '');

                if (!$seasonName || !$dateText) {
                    continue;
                }

                if (str_contains($dateText, ' - ')) {

                    $parts = explode(' - ', $dateText);

                    if (count($parts) === 2) {

                        try {

                            $startDate = Carbon::parse(trim($parts[0]))->format('Y-m-d');
                            $endDate   = Carbon::parse(trim($parts[1]))->format('Y-m-d');

                            $season = SeasonDateRange::updateOrCreate(
                                [
                                    'start_date' => $startDate,
                                    'end_date'   => $endDate,
                                ],
                                [
                                    'name' => $seasonName,
                                ]
                            );

                            $seasonRanges[$i] = $season->id;

                        } catch (\Exception $e) {
                        }
                    }
                }
            }

            for ($rowIndex = 2; $rowIndex < count($rows); $rowIndex++) {

                $row = $rows[$rowIndex];

                if (count($row) < 2) {
                    continue;
                }

                $serviceName = trim($row[0] ?? '');

                if (!$serviceName) {
                    continue;
                }

                $service = SpecialServices::updateOrCreate(
                    [
                        'name' => $serviceName,
                    ],
                    [
                        'import_source' => 'pricing_matrix',
                        'updated_by' => auth()->id(),
                    ]
                );

                for ($col = 1; $col < count($row); $col++) {

                    $price = trim($row[$col] ?? '');

                    if ($price === '' || !is_numeric($price)) {
                        continue;
                    }

                    $seasonId = $seasonRanges[$col] ?? null;

                    if (!$seasonId) {
                        continue;
                    }

                    SpecialServicePricing::updateOrCreate(
                        [
                            'special_service_id'    => $service->id,
                            'season_date_range_id'  => $seasonId,
                        ],
                        [
                            'price' => (float) $price,
                        ]
                    );
                }
            }
        });
    }
}