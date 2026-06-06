<?php

namespace App\Exports;

use App\Models\HotelPrice;
use App\Models\HotelPriceExtra;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HotelPricingMatrixExport implements FromCollection, WithEvents
{
    public function collection()
    {
        return collect([]);
    }

    private function cleanPrice($value)
    {
        if (!$value) return null;

        if (is_string($value) && str_starts_with($value, '=')) {
            return null;
        }

        return is_numeric($value) ? (float)$value : null;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();

                $prices = HotelPrice::with([
                    'hotel.hotelGroup',
                    'roomType',
                    'mealPlan',
                    'season',
                    'seasonDateRange'
                ])->get();

                $extras = HotelPriceExtra::with('extra')->get();

                // ✅ Build Seasons with Ranges
                $seasons = $prices
                    ->filter(fn($p) => $p->season && $p->seasonDateRange)
                    ->groupBy('season_id')
                    ->map(function ($group) {

                        $ranges = $group
                            ->map(fn($p) => [
                                'id' => $p->season_date_ranges_id,
                                'start' => $p->seasonDateRange->start_date,
                                'end' => $p->seasonDateRange->end_date,
                            ])
                            ->unique('id')
                            ->sortBy('start')
                            ->values();

                        return [
                            'key' => $group->first()->season_id,
                            'name' => $group->first()->season->name,
                            'ranges' => $ranges
                        ];
                    })
                    ->values();

                // ✅ Static Headers
                $sheet->setCellValue('A1','Group Name');
                $sheet->setCellValue('B1','Location');
                $sheet->setCellValue('C1','Name');
                $sheet->setCellValue('D1','Star');
                $sheet->setCellValue('E1','Room');

                // ✅ Dynamic Header (Seasons + Multi Ranges)
                $col = 6;
                $maxRangeRows = $seasons->max(fn($s) => count($s['ranges']));

                foreach ($seasons as $s) {

                    $rangeCount = count($s['ranges']);
                    $startCol = $col;
                    $endCol = $col + ($rangeCount * 3) - 1;

                    // Season Name
                    $sheet->mergeCellsByColumnAndRow($startCol,1,$endCol,1);
                    $sheet->setCellValueByColumnAndRow($startCol,1,$s['name']);

                    $currentRow = 2;

                    foreach ($s['ranges'] as $range) {

                        $rangeText = date('d M Y', strtotime($range['start'])) . ' - ' . date('d M Y', strtotime($range['end']));

                        $sheet->mergeCellsByColumnAndRow($col,$currentRow,$col+2,$currentRow);
                        $sheet->setCellValueByColumnAndRow($col,$currentRow,$rangeText);

                        $currentRow++;
                        $col += 3;
                    }

                    // Meal Plan Row
                    $mealRow = 2 + $rangeCount;
                    $tempCol = $startCol;

                    foreach ($s['ranges'] as $range) {
                        $sheet->setCellValueByColumnAndRow($tempCol,$mealRow,'CP');
                        $sheet->setCellValueByColumnAndRow($tempCol+1,$mealRow,'MAP');
                        $sheet->setCellValueByColumnAndRow($tempCol+2,$mealRow,'AP');
                        $tempCol += 3;
                    }
                }

                // ✅ Data Start Row
                $row = 2 + $maxRangeRows + 1;

                $hotels = $prices->groupBy('hotel_id');

                foreach ($hotels as $hotelPrices) {

                    $hotel = $hotelPrices->first()->hotel;
                    $rooms = $hotelPrices->groupBy('room_type_id');
                    $startRow = $row;

                    foreach ($rooms as $roomPrices) {

                        $sheet->setCellValue("E{$row}", $roomPrices->first()->roomType->name);

                        $col = 6;

                        foreach ($seasons as $s) {

                            foreach ($s['ranges'] as $range) {

                                $rangePrices = $prices->filter(fn($p) =>
                                    (int)$p->hotel_id === (int)$hotel->id &&
                                    (int)$p->room_type_id === (int)$roomPrices->first()->room_type_id &&
                                    (int)$p->season_id === (int)$s['key'] &&
                                    (int)$p->season_date_ranges_id === (int)$range['id']
                                );

                                $mealMap = ['CP'=>null,'MAP'=>null,'AP'=>null];

                                foreach ($rangePrices as $p) {

                                    $mealName = strtoupper(trim($p->mealPlan->name ?? ''));

                                    if (str_contains($mealName, 'CP')) {
                                        $mealMap['CP'] = $this->cleanPrice($p->base_price);
                                    } elseif (str_contains($mealName, 'MAP')) {
                                        $mealMap['MAP'] = $this->cleanPrice($p->base_price);
                                    } elseif (str_contains($mealName, 'AP')) {
                                        $mealMap['AP'] = $this->cleanPrice($p->base_price);
                                    }
                                }

                                $sheet->setCellValueByColumnAndRow($col,$row,$mealMap['CP']);
                                $sheet->setCellValueByColumnAndRow($col+1,$row,$mealMap['MAP']);
                                $sheet->setCellValueByColumnAndRow($col+2,$row,$mealMap['AP']);

                                $col += 3;
                            }
                        }

                        $row++;
                    }

                    // ✅ Merge hotel info
                    $endRow = $row - 1;

                    if ($endRow > $startRow) {
                        $sheet->mergeCells("A{$startRow}:A{$endRow}");
                        $sheet->mergeCells("B{$startRow}:B{$endRow}");
                        $sheet->mergeCells("C{$startRow}:C{$endRow}");
                        $sheet->mergeCells("D{$startRow}:D{$endRow}");
                    }

                    $sheet->setCellValue("A{$startRow}", ($hotel->hotelGroup?->name === 'Default Hotel Group' ? '' : $hotel->hotelGroup?->name));
                    $sheet->setCellValue("B{$startRow}", $hotel->location);
                    $sheet->setCellValue("C{$startRow}", $hotel->name);
                    $sheet->setCellValue("D{$startRow}", $hotel->star);
                }

                // ✅ Styling
                $highestCol = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:{$highestCol}{$highestRow}")
                    ->applyFromArray([
                        'borders'=>[
                            'allBorders'=>[
                                'borderStyle'=>Border::BORDER_THIN
                            ]
                        ]
                    ]);

                // Yellow header
                $sheet->getStyle("F1:{$highestCol}".(2 + $maxRangeRows + 1))
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => 'solid',
                            'startColor' => ['rgb' => 'FFF200']
                        ]
                    ]);

                // Center align
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")
                    ->applyFromArray([
                        'alignment' => [
                            'horizontal' => 'center',
                            'vertical' => 'center'
                        ]
                    ]);

                // Auto width
                foreach (range('A', $highestCol) as $colLetter) {
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }

                // Freeze pane
                $sheet->freezePane('F' . (2 + $maxRangeRows + 2));
            }
        ];
    }
}