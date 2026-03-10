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

class CustomerSalesExport implements
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
            'Payment Type',
            'Paid Amount',
            'Due Amount',
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

        $returnedSubtotal = (float) $sale->returns->sum('subtotal');
        $returnedDiscount = (float) $sale->returns->sum('total_discount');
        $returnedTax = (float) $sale->returns->sum('total_tax');
        $returnedTotal = (float) $sale->returns->sum('total_amount');

        return [
            $sale->sale_no,
            optional($sale->sale_date)->format('d/m/Y'),
            $sale->customer?->name,
            $sale->merchant?->name,
            $branchText ?: '-',
            ucfirst((string) ($sale->payment_type ?? '')),
            (float) ($sale->paid_amount ?? 0),
            (float) ($sale->due_amount ?? 0),
            (int) $sale->items_count,
            (float) $sale->subtotal + $returnedSubtotal,
            (float) $sale->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);

                return $lineTotal * ($discountRate / 100);
            }) + $returnedDiscount,
            (float) $sale->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);
                $taxRate = (float) ($item->tax ?? 0);
                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;

                return $taxableAmount * ($taxRate / 100);
            }) + $returnedTax,
            (float) $sale->total_amount + $returnedTotal,
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

                $event->sheet->setCellValue("H{$totalRow}", 'TOTAL');

                $event->sheet->setCellValue("I{$totalRow}", $this->totals['items_count'] ?? 0);
                $event->sheet->setCellValue("J{$totalRow}", $this->totals['subtotal'] ?? 0);
                $event->sheet->setCellValue("K{$totalRow}", $this->totals['discount'] ?? 0);
                $event->sheet->setCellValue("L{$totalRow}", $this->totals['tax'] ?? 0);
                $event->sheet->setCellValue("M{$totalRow}", $this->totals['total'] ?? 0);

                $event->sheet
                    ->getStyle("H{$totalRow}:M{$totalRow}")
                    ->getFont()
                    ->setBold(true);

                $summaryStart = $totalRow + 2;
                $event->sheet->setCellValue("L{$summaryStart}", 'Total Amount');
                $event->sheet->setCellValue("M{$summaryStart}", $this->totals['total_amount'] ?? ($this->totals['total'] ?? 0));

                $event->sheet->setCellValue("L" . ($summaryStart + 1), 'Amount Paid');
                $event->sheet->setCellValue("M" . ($summaryStart + 1), $this->totals['amount_paid'] ?? 0);

                $event->sheet->setCellValue("L" . ($summaryStart + 2), 'Amount Pending');
                $event->sheet->setCellValue("M" . ($summaryStart + 2), $this->totals['amount_pending'] ?? 0);

                $event->sheet
                    ->getStyle("L{$summaryStart}:M" . ($summaryStart + 2))
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
