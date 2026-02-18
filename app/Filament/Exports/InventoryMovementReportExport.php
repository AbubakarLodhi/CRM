<?php

namespace App\Filament\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class InventoryMovementReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents
{
    protected int $rowCount = 0;

    public function __construct(
        protected Collection $records,
        protected array $totals = [],
        protected array $stats = []
    ) {
        $this->rowCount = $records->count();
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Reference No',
            'Product',
            'Variant',
            'SKU',
            'Quantity',
            'Unit Price',
            'Total',
            'Direction',
        ];
    }

    public function map($record): array
    {
        $date = $record['date'] ?? null;
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('d/m/Y');
        }

        $direction = $record['direction'] === 'in' ? 'In' : 'Out';

        return [
            $date,
            $record['type'] ?? null,
            $record['reference'] ?? null,
            $record['product_name'] ?? null,
            $record['variant_name'] ?? null,
            $record['product_sku'] ?? null,
            (float) ($record['quantity'] ?? 0),
            (float) ($record['unit_price'] ?? 0),
            (float) ($record['total'] ?? 0),
            $direction,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $endRow   = $this->rowCount + 1;
                $totalRow = $endRow + 1;

                $event->sheet->setCellValue("F{$totalRow}", 'TOTAL');

                $event->sheet->setCellValue("G{$totalRow}", $this->totals['quantity'] ?? 0);
                $event->sheet->setCellValue("I{$totalRow}", $this->totals['total'] ?? 0);

                $event->sheet
                    ->getStyle("F{$totalRow}:I{$totalRow}")
                    ->getFont()
                    ->setBold(true);

                if (! empty($this->stats)) {
                    $statsRow = $totalRow + 2;

                    $event->sheet->setCellValue("F{$statsRow}", 'STATS');
                    $event->sheet->getStyle("F{$statsRow}")->getFont()->setBold(true);

                    $statsRow++;
                    $rows = [
                        'Total In'  => $this->stats['in'] ?? 0,
                        'Total Out' => $this->stats['out'] ?? 0,
                        'Net'       => $this->stats['net'] ?? 0,
                    ];

                    foreach ($rows as $label => $value) {
                        $event->sheet->setCellValue("F{$statsRow}", $label);
                        $event->sheet->setCellValue("G{$statsRow}", $value);
                        $statsRow++;
                    }
                }
            },
        ];
    }
}
