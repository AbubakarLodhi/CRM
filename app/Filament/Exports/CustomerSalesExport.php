<?php

namespace App\Filament\Exports;

use App\Models\CashFlow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class CustomerSalesExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected int $rowCount = 0;

    public function __construct(
        protected Collection $sales,
        protected Collection $cashFlows,
        protected array $totals = [],
        protected array $selectedColumns = [],
    ) {}

    public static function selectableColumns(): array
    {
        return [
            'party_name' => 'Customer',
            'merchant' => 'Merchant',
            'branch' => 'Branch',
            'payment_type' => 'Type',
            'paid_amount' => 'Paid Amount',
            'due_amount' => 'Due Amount',
            'items_count' => 'Items Count',
            'subtotal' => 'Subtotal',
            'discount' => 'Discount',
            'tax' => 'Tax',
            'total_amount' => 'Total Amount',
        ];
    }

    public static function headingsFor(array $selectedColumns = []): array
    {
        $headings = ['Date', 'Description', 'Debit', 'Credit', 'Balance'];

        foreach (self::normalizeSelectedColumns($selectedColumns) as $column) {
            $headings[] = self::selectableColumns()[$column] ?? ucfirst(str_replace('_', ' ', $column));
        }

        return $headings;
    }

    public function headings(): array
    {
        return self::headingsFor($this->selectedColumns);
    }

    public static function buildStatementRows(Collection $sales, Collection $cashFlows, array $selectedColumns = []): array
    {
        $entries = self::buildChronologicalEntries($sales, $cashFlows);
        $runningBalance = 0.0;
        $selected = self::normalizeSelectedColumns($selectedColumns);
        $rows = [];

        foreach ($entries as $entry) {
            $runningBalance = round($runningBalance + (float) $entry['debit'] - (float) $entry['credit'], 2);

            $row = [
                $entry['date']->format('d/m/Y'),
                $entry['description'],
                self::moneyCell($entry['debit']),
                self::moneyCell($entry['credit']),
                self::moneyCell($runningBalance),
            ];

            foreach ($selected as $column) {
                $value = $entry['extras'][$column] ?? '-';

                if (in_array($column, ['paid_amount', 'due_amount', 'subtotal', 'discount', 'tax', 'total_amount'], true)) {
                    $row[] = self::moneyCell($value);
                } else {
                    $row[] = $value;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public static function calculateTotals(Collection $sales, Collection $cashFlows): array
    {
        $entries = self::buildChronologicalEntries($sales, $cashFlows);
        $totalDebits = round((float) collect($entries)->sum('debit'), 2);
        $totalCredits = round((float) collect($entries)->sum('credit'), 2);

        return [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'closing_balance' => round($totalDebits - $totalCredits, 2),
        ];
    }

    public function array(): array
    {
        $rows = self::buildStatementRows($this->sales, $this->cashFlows, $this->selectedColumns);
        $this->rowCount = count($rows);

        return $rows;
    }

    protected static function normalizeSelectedColumns(array $selectedColumns): array
    {
        $allowed = array_keys(self::selectableColumns());
        $selected = array_values(array_intersect($selectedColumns, $allowed));

        return $selected;
    }

    protected static function buildChronologicalEntries(Collection $sales, Collection $cashFlows): array
    {
        $entries = [];

        foreach ($sales as $sale) {
            $branches = $sale->items
                ->pluck('branch.name')
                ->filter()
                ->unique()
                ->values();

            $branchText = $branches->count() > 2
                ? $branches->take(2)->implode(', ') . ' +' . ($branches->count() - 2) . ' more'
                : $branches->implode(', ');

            $discount = (float) $sale->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);

                return $lineTotal * ($discountRate / 100);
            });

            $tax = (float) $sale->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);
                $taxRate = (float) ($item->tax ?? 0);
                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;

                return $taxableAmount * ($taxRate / 100);
            });

            $invoiceDate = $sale->sale_date ?? $sale->created_at;

            $entries[] = [
                'date' => $invoiceDate->copy()->startOfDay(),
                'created_at' => $sale->created_at,
                'debit' => round((float) ($sale->total_amount ?? 0), 2),
                'credit' => 0.0,
                'description' => self::invoiceDescription($sale),
                'extras' => [
                    'party_name' => (string) ($sale->customer?->name ?? '-'),
                    'merchant' => (string) ($sale->merchant?->name ?? '-'),
                    'branch' => (string) ($branchText ?: '-'),
                    'payment_type' => 'Invoice',
                    'paid_amount' => 0.0,
                    'due_amount' => (float) ($sale->due_amount ?? 0),
                    'items_count' => (int) ($sale->items_count ?? $sale->items->count()),
                    'subtotal' => (float) ($sale->subtotal ?? 0),
                    'discount' => $discount,
                    'tax' => $tax,
                    'total_amount' => round((float) ($sale->total_amount ?? 0), 2),
                ],
            ];

            $ledgerPaid = 0.0;

            foreach (($sale->payments ?? collect())->sortBy([['payment_date', 'asc'], ['created_at', 'asc']]) as $payment) {
                $amount = round((float) ($payment->amount ?? 0), 2);
                if ($amount == 0.0) {
                    continue;
                }

                $ledgerPaid = round($ledgerPaid + $amount, 2);

                $entries[] = [
                    'date' => ($payment->payment_date ?? $payment->created_at)->copy()->startOfDay(),
                    'created_at' => $payment->created_at,
                    'debit' => $amount < 0 ? abs($amount) : 0.0,
                    'credit' => $amount > 0 ? $amount : 0.0,
                    'description' => self::paymentDescription($sale->sale_no, $payment->reference_no, $payment->method),
                    'extras' => [
                        'party_name' => (string) ($sale->customer?->name ?? '-'),
                        'merchant' => (string) ($sale->merchant?->name ?? '-'),
                        'branch' => (string) ($branchText ?: '-'),
                        'payment_type' => ucfirst((string) ($payment->entry_type ?? 'payment')),
                        'paid_amount' => $amount > 0 ? $amount : 0.0,
                        'due_amount' => '-',
                        'items_count' => '-',
                        'subtotal' => '-',
                        'discount' => '-',
                        'tax' => '-',
                        'total_amount' => 0.0,
                    ],
                ];
            }

            $returnAdjustment = round(max(0, $ledgerPaid - (float) ($sale->total_amount ?? 0)), 2);

            if ($returnAdjustment > 0 && ($sale->returns ?? collect())->isNotEmpty()) {
                $latestReturn = ($sale->returns ?? collect())
                    ->sortByDesc(fn ($return) => $return->return_date ?? $return->created_at)
                    ->first();
                $returnDate = $latestReturn?->return_date ?? $latestReturn?->created_at ?? $sale->updated_at ?? $sale->created_at;

                $entries[] = [
                    'date' => $returnDate->copy()->startOfDay(),
                    'created_at' => $latestReturn?->created_at ?? $sale->updated_at ?? $sale->created_at,
                    'debit' => $returnAdjustment,
                    'credit' => 0.0,
                    'description' => self::saleReturnAdjustmentDescription($sale->sale_no),
                    'extras' => [
                        'party_name' => (string) ($sale->customer?->name ?? '-'),
                        'merchant' => (string) ($sale->merchant?->name ?? '-'),
                        'branch' => (string) ($branchText ?: '-'),
                        'payment_type' => 'Sale Return Adjustment',
                        'paid_amount' => 0.0,
                        'due_amount' => '-',
                        'items_count' => '-',
                        'subtotal' => '-',
                        'discount' => '-',
                        'tax' => '-',
                        'total_amount' => 0.0,
                    ],
                ];
            }
        }

        foreach ($cashFlows as $cashFlow) {
            $amount = round((float) ($cashFlow->amount ?? 0), 2);
            if ($amount == 0.0) {
                continue;
            }

            $isCredit = (string) ($cashFlow->direction ?? '') === 'in';
            $flowType = CashFlow::flowTypeLabel($cashFlow->flow_type, 'Cash Flow');
            $direction = $isCredit ? 'In' : 'Out';

            $entries[] = [
                'date' => ($cashFlow->flow_date ?? $cashFlow->created_at)->copy()->startOfDay(),
                'created_at' => $cashFlow->created_at,
                'debit' => $isCredit ? 0.0 : $amount,
                'credit' => $isCredit ? $amount : 0.0,
                'description' => self::cashFlowDescription($flowType, $direction, $cashFlow->reference_no, $cashFlow->method),
                'extras' => [
                    'party_name' => (string) ($cashFlow->party?->name ?? '-'),
                    'merchant' => (string) ($cashFlow->merchant?->name ?? '-'),
                    'branch' => '-',
                    'payment_type' => 'Cash Flow',
                    'paid_amount' => $isCredit ? $amount : 0.0,
                    'due_amount' => '-',
                    'items_count' => '-',
                    'subtotal' => '-',
                    'discount' => '-',
                    'tax' => '-',
                    'total_amount' => $amount,
                ],
            ];
        }

        usort($entries, function (array $a, array $b): int {
            $dateCompare = $a['date']->timestamp <=> $b['date']->timestamp;
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return ($a['created_at']?->timestamp ?? 0) <=> ($b['created_at']?->timestamp ?? 0);
        });

        return $entries;
    }

    protected static function invoiceDescription($sale): string
    {
        $variantNames = $sale->items
            ->flatMap(function ($item) {
                $variants = $item->variants
                    ->pluck('variant.name')
                    ->filter()
                    ->values();

                if ($variants->isNotEmpty()) {
                    return $variants;
                }

                return collect([(string) ($item->product?->name ?? '')])->filter();
            })
            ->filter()
            ->unique()
            ->values();

        $variantsText = $variantNames->isNotEmpty() ? $variantNames->implode(', ') : 'No variants';

        return 'Invoice ' . (string) ($sale->sale_no ?? '-') . ' - ' . $variantsText;
    }

    protected static function paymentDescription(?string $saleNo, ?string $reference, ?string $method): string
    {
        $parts = ['Receipt'];

        if (filled($reference)) {
            $parts[] = (string) $reference;
        }

        if (filled($method)) {
            $parts[] = '(' . (string) $method . ')';
        }

        $parts[] = 'against invoice ' . (string) ($saleNo ?? '-');

        return implode(' ', $parts);
    }

    protected static function saleReturnAdjustmentDescription(?string $saleNo): string
    {
        return 'Sale return adjustment against invoice ' . (string) ($saleNo ?? '-');
    }

    protected static function cashFlowDescription(string $flowType, string $direction, ?string $reference, ?string $method): string
    {
        $parts = ['Cash Flow - ' . $flowType . ' (' . $direction . ')'];

        if (filled($reference)) {
            $parts[] = '- Ref: ' . $reference;
        }

        if (filled($method)) {
            $parts[] = '- Method: ' . $method;
        }

        return implode(' ', $parts);
    }

    protected function columnLetter(int $index): string
    {
        $index = max(1, $index);
        $letter = '';

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    protected static function moneyCell(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        $number = round((float) $value, 2);

        return abs($number) < 0.01 ? '' : number_format($number, 2, '.', '');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $totalColumns = count($this->headings());
                $labelCol = $this->columnLetter(max(1, $totalColumns - 1));
                $valueCol = $this->columnLetter($totalColumns);
                $summaryStart = $this->rowCount + 3;
                $closing = (float) ($this->totals['closing_balance'] ?? 0);

                $lines = [
                    ['Total debits', number_format((float) ($this->totals['total_debits'] ?? 0), 2)],
                    ['Total credits', number_format((float) ($this->totals['total_credits'] ?? 0), 2)],
                    ['Closing balance', number_format($closing, 2)],
                ];

                foreach ($lines as $index => [$label, $value]) {
                    $row = $summaryStart + $index;
                    $event->sheet->setCellValue("{$labelCol}{$row}", $label);
                    $event->sheet->setCellValue("{$valueCol}{$row}", $value);
                }

                $summaryEnd = $summaryStart + count($lines) - 1;
                $event->sheet->getStyle("{$labelCol}{$summaryStart}:{$valueCol}{$summaryEnd}")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
