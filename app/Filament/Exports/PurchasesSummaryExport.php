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
            'Purchase No',
            'Date',
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

        $returnsCount = (int) ($purchase->returns_count ?? 0);
        $returnedQty = (float) $purchase->returns->sum(fn ($return) => $return->items->sum('quantity'));
        $hasRemaining = $purchase->items->contains(fn ($item) => (int) ($item->quantity ?? 0) > 0);
        $returnStatus = $returnsCount === 0
            ? '-'
            : ($hasRemaining ? 'Partially Returned' : 'Returned');

        $returnedSubtotal = (float) $purchase->returns->sum('subtotal');
        $returnedDiscount = (float) $purchase->returns->sum('total_discount');
        $returnedTax = (float) $purchase->returns->sum('total_tax');
        $returnedTotal = (float) $purchase->returns->sum('total_amount');

        return [
            $purchase->purchase_no,
            optional($purchase->purchase_date)->format('d/m/Y'),
            $purchase->merchant?->name,
            $branchText ?: '-',
            (int) $purchase->items_count,
            (float) $purchase->subtotal + $returnedSubtotal,
            (float) $purchase->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);

                return $lineTotal * ($discountRate / 100);
            }) + $returnedDiscount,
            (float) $purchase->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);
                $taxRate = (float) ($item->tax ?? 0);
                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;

                return $taxableAmount * ($taxRate / 100);
            }) + $returnedTax,
            (float) $purchase->total_amount + $returnedTotal,
            $returnStatus,
            $returnedQty,
            (float) ($purchase->returned_amount ?? 0),
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

                $event->sheet->setCellValue("D{$totalRow}", 'TOTAL');

                $event->sheet->setCellValue("E{$totalRow}", $this->totals['items_count'] ?? 0);
                $event->sheet->setCellValue("F{$totalRow}", $this->totals['subtotal'] ?? 0);
                $event->sheet->setCellValue("G{$totalRow}", $this->totals['discount'] ?? 0);
                $event->sheet->setCellValue("H{$totalRow}", $this->totals['tax'] ?? 0);
                $event->sheet->setCellValue("I{$totalRow}", $this->totals['total'] ?? 0);
                $event->sheet->setCellValue("K{$totalRow}", $this->totals['returned_quantity'] ?? 0);
                $event->sheet->setCellValue("L{$totalRow}", $this->totals['returned_amount'] ?? 0);

                $event->sheet->getStyle("D{$totalRow}:L{$totalRow}")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
