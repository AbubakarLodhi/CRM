<?php

namespace App\Filament\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesSummaryExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithChunkReading,
    WithEvents
{
    protected int $rowCount = 0;

    public function __construct(
        protected Builder $query,
        protected array $totals = []
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Sale No',
            'Date',
            'Customer',
            'Merchant',
            'Branch',
            'Items Count',
            'Subtotal',
            'Discount',
            'Tax',
            'Total Amount',
        ];
    }

    public function map($sale): array
    {
        $this->rowCount++;

        // Same branch logic as UI
        $branches = $sale->items
            ->pluck('branch.name')
            ->filter()
            ->unique()
            ->values();

        if ($branches->count() > 2) {
            $branchText = $branches->take(2)->implode(', ')
                . ' +' . ($branches->count() - 2) . ' more';
        } else {
            $branchText = $branches->implode(', ');
        }

        return [
            $sale->sale_no,
            optional($sale->sale_date)->format('d/m/Y'),
            $sale->customer?->name,
            $sale->merchant?->name,
            $branchText ?: '-',
            (int) $sale->items_count,
            (float) $sale->subtotal,
            (float) $sale->discount,
            (float) $sale->tax,
            (float) $sale->total_amount,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $endRow   = $this->rowCount + 1;
                $totalRow = $endRow + 1;

                $event->sheet->setCellValue("E{$totalRow}", 'TOTAL');

                $event->sheet->setCellValue("F{$totalRow}", $this->totals['items_count'] ?? 0);
                $event->sheet->setCellValue("G{$totalRow}", $this->totals['subtotal'] ?? 0);
                $event->sheet->setCellValue("H{$totalRow}", $this->totals['discount'] ?? 0);
                $event->sheet->setCellValue("I{$totalRow}", $this->totals['tax'] ?? 0);
                $event->sheet->setCellValue("J{$totalRow}", $this->totals['total'] ?? 0);

                $event->sheet
                    ->getStyle("E{$totalRow}:J{$totalRow}")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
