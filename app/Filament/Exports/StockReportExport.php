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

class StockReportExport implements
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
        protected array $totals = [],
        protected array $stats = []
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Product',
            'Variant',
            'SKU',
            'Last Updated',
            'Purchased',
            'Sold',
            'Stock',
            'Total Amount',
            'Cost',
            'Sale',
            'Active',
        ];
    }

    public function map($variant): array
    {
        $this->rowCount++;

        return [
            $variant->product?->name,
            $variant->name,
            $variant->sku,
            $variant->last_updated
                ? \Illuminate\Support\Carbon::parse($variant->last_updated)->format('d/m/Y')
                : '—',
            (float) $variant->total_purchased,
            (float) $variant->total_sold,
            (float) $variant->current_stock,
            (float) $variant->total_amount,
            (float) $variant->purchase_price,
            (float) $variant->selling_price,
            $variant->is_active ? 'Yes' : 'No',
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

                $event->sheet->setCellValue("E{$totalRow}", $this->totals['purchased'] ?? 0);
                $event->sheet->setCellValue("F{$totalRow}", $this->totals['sold'] ?? 0);
                $event->sheet->setCellValue("G{$totalRow}", $this->totals['stock'] ?? 0);

                $event->sheet
                    ->getStyle("D{$totalRow}:G{$totalRow}")
                    ->getFont()
                    ->setBold(true);

                if (! empty($this->stats)) {
                    $statsRow = $totalRow + 2;

                    $event->sheet->setCellValue("C{$statsRow}", 'STATS');
                    $event->sheet->getStyle("C{$statsRow}")->getFont()->setBold(true);

                    $statsRow++;
                    $rows = [
                        'Total Products'      => $this->stats['total_products'] ?? 0,
                        'Total Purchased Qty' => $this->stats['total_purchased_qty'] ?? 0,
                        'Total Sold Qty'      => $this->stats['total_sold_qty'] ?? 0,
                        'Available Stock'     => $this->stats['available_stock'] ?? 0,
                        'Total Revenue'       => $this->stats['total_revenue'] ?? 0,
                        'Avg Selling Price'   => $this->stats['avg_selling_price'] ?? 0,
                        'Avg Buying Price'    => $this->stats['avg_buying_price'] ?? 0,
                    ];

                    foreach ($rows as $label => $value) {
                        $event->sheet->setCellValue("C{$statsRow}", $label);
                        $event->sheet->setCellValue("D{$statsRow}", $value);
                        $statsRow++;
                    }
                }
            },
        ];
    }
}
