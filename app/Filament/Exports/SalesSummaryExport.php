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
            'Return Status',
            'Returned Qty',
            'Returned Amount',
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

        $returnsCount = (int) ($sale->returns_count ?? 0);
        $returnedQty = (float) $sale->returns->sum(fn ($return) => $return->items->sum('quantity'));
        $hasRemaining = $sale->items->contains(fn ($item) => (int) ($item->quantity ?? 0) > 0);
        $returnStatus = $returnsCount === 0
            ? '-'
            : ($hasRemaining ? 'Partially Returned' : 'Returned');

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
            $returnStatus,
            $returnedQty,
            (float) ($sale->returned_amount ?? 0),
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
                $event->sheet->setCellValue("L{$totalRow}", $this->totals['returned_quantity'] ?? 0);
                $event->sheet->setCellValue("M{$totalRow}", $this->totals['returned_amount'] ?? 0);

                $event->sheet
                    ->getStyle("E{$totalRow}:M{$totalRow}")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
