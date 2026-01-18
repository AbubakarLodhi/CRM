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

class PurchasesSummaryExport implements
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
            'Purchase No',   // A
            'Date',          // B
            'Merchant',      // C
            'Business',      // D
            'Branch',        // E
            'Items Count',   // F
            'Subtotal',      // G
            'Discount',      // H
            'Tax',           // I
            'Total Amount',  // J
        ];
    }

    public function map($purchase): array
    {
        $this->rowCount++;

        return [
            $purchase->purchase_no,
            optional($purchase->purchase_date)->format('d/m/Y'),
            $purchase->merchant?->name,
            $purchase->business?->name,
            $purchase->branch?->name,
            (int) $purchase->items_count,
            (float) $purchase->subtotal,
            (float) $purchase->discount,
            (float) $purchase->tax,
            (float) $purchase->total_amount,
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

                // Header row = 1, data starts at 2
                $endRow   = $this->rowCount + 1;
                $totalRow = $endRow + 1;

                // Label
                $event->sheet->setCellValue("E{$totalRow}", 'TOTAL');

                // Write numeric totals (NO formulas => works perfectly in LibreOffice)
                $event->sheet->setCellValue("F{$totalRow}", $this->totals['items_count'] ?? 0);
                $event->sheet->setCellValue("G{$totalRow}", $this->totals['subtotal'] ?? 0);
                $event->sheet->setCellValue("H{$totalRow}", $this->totals['discount'] ?? 0);
                $event->sheet->setCellValue("I{$totalRow}", $this->totals['tax'] ?? 0);
                $event->sheet->setCellValue("J{$totalRow}", $this->totals['total'] ?? 0);

                // Bold totals row
                $event->sheet->getStyle("E{$totalRow}:J{$totalRow}")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
