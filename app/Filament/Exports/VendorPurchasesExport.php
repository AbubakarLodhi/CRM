<?php

namespace App\Filament\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class VendorPurchasesExport implements
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
            'Purchase No',
            'Date',
            'Vendor',
            'Merchant',
            'Branch',
            'Payment Type',
            'Items Count',
            'Subtotal',
            'Discount',
            'Tax',
            'Total Amount',
        ];
    }

    public function map($purchase): array
    {
        $this->rowCount++;

        $branches = $purchase->items
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
            $purchase->purchase_no,
            optional($purchase->purchase_date)->format('d/m/Y'),
            $purchase->vendor?->name,
            $purchase->merchant?->name,
            $branchText ?: '-',
            ucfirst((string) ($purchase->payment_type ?? '')),
            (int) $purchase->items_count,
            (float) $purchase->subtotal,
            (float) $purchase->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);
                return $lineTotal * ($discountRate / 100);
            }),
            (float) $purchase->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);
                $taxRate = (float) ($item->tax ?? 0);
                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;
                return $taxableAmount * ($taxRate / 100);
            }),
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

                $endRow   = $this->rowCount + 1;
                $totalRow = $endRow + 1;

                $event->sheet->setCellValue("F{$totalRow}", 'TOTAL');

                $event->sheet->setCellValue("G{$totalRow}", $this->totals['items_count'] ?? 0);
                $event->sheet->setCellValue("H{$totalRow}", $this->totals['subtotal'] ?? 0);
                $event->sheet->setCellValue("I{$totalRow}", $this->totals['discount'] ?? 0);
                $event->sheet->setCellValue("J{$totalRow}", $this->totals['tax'] ?? 0);
                $event->sheet->setCellValue("K{$totalRow}", $this->totals['total'] ?? 0);

                $event->sheet
                    ->getStyle("F{$totalRow}:K{$totalRow}")
                    ->getFont()
                    ->setBold(true);

                $summaryStart = $totalRow + 2;
                $event->sheet->setCellValue("J{$summaryStart}", 'Total Amount');
                $event->sheet->setCellValue("K{$summaryStart}", $this->totals['total_amount'] ?? ($this->totals['total'] ?? 0));

                $event->sheet->setCellValue("J" . ($summaryStart + 1), 'Amount Paid');
                $event->sheet->setCellValue("K" . ($summaryStart + 1), $this->totals['amount_paid'] ?? 0);

                $event->sheet->setCellValue("J" . ($summaryStart + 2), 'Amount Pending');
                $event->sheet->setCellValue("K" . ($summaryStart + 2), $this->totals['amount_pending'] ?? 0);

                $event->sheet
                    ->getStyle("J{$summaryStart}:K" . ($summaryStart + 2))
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
