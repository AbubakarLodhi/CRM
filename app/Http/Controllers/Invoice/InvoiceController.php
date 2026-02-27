<?php

namespace App\Http\Controllers\Invoice;

use App\Models\InvoiceDynamicGroup;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\InvoiceDynamicFieldResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvoiceController
{
    public function show(string $type, string $id)
    {
        $record = match ($type) {
            'sale' => Sale::with([
                'merchant.logo',
                'customer',
                'items.product',
                'items.variants.variant',
                'items.business.logo',
                'items.branch',
            ])->find($id),

            'purchase' => Purchase::with([
                'merchant.logo',
                'vendor',
                'items.product',
                'items.variants.variant',
                'items.business.logo',
                'items.branch',
            ])->find($id),

            default => throw new NotFoundHttpException(),
        };

        if (! $record) {
            abort(404);
        }

        $headerGroupOptions = $this->invoiceGroupOptions((string) $record->merchant_id, 'header');
        $footerGroupOptions = $this->invoiceGroupOptions((string) $record->merchant_id, 'footer');
        $combinations = [];

        foreach ($headerGroupOptions as $headerGroupId => $headerGroupLabel) {
            foreach ($footerGroupOptions as $footerGroupId => $footerGroupLabel) {
                $dynamicGroups = InvoiceDynamicFieldResolver::resolveGroups(
                    $record,
                    (string) $headerGroupId,
                    (string) $footerGroupId
                );

                $combinations[] = [
                    'id' => (string) $headerGroupId . '|' . (string) $footerGroupId,
                    'headerLabel' => (string) $headerGroupLabel,
                    'footerLabel' => (string) $footerGroupLabel,
                    'headerGroups' => $dynamicGroups['header'],
                    'footerGroups' => $dynamicGroups['footer'],
                    'showDefaultHeader' => (string) $headerGroupId === '__default',
                    'showDefaultFooter' => (string) $footerGroupId === '__default',
                ];
            }
        }

        if (empty($combinations)) {
            $combinations[] = [
                'id' => '__default|__default',
                'headerLabel' => 'Default (Current Header)',
                'footerLabel' => 'Default (Current Footer)',
                'headerGroups' => [],
                'footerGroups' => [],
                'showDefaultHeader' => true,
                'showDefaultFooter' => true,
            ];
        }

        $selectedCombination = trim((string) request()->query('combo', $combinations[0]['id']));
        $selectedCombinationIndex = 0;

        foreach ($combinations as $index => $combination) {
            if ((string) $combination['id'] === $selectedCombination) {
                $selectedCombinationIndex = $index;
                break;
            }
        }

        [$previousInvoiceUrl, $nextInvoiceUrl] = $this->adjacentInvoiceUrls($type, $record);

        return view('filament.pages.invoice', [
            'type'   => $type,
            'record' => $record,
            'combinations' => $combinations,
            'selectedCombinationIndex' => $selectedCombinationIndex,
            'previousInvoiceUrl' => $previousInvoiceUrl,
            'nextInvoiceUrl' => $nextInvoiceUrl,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function invoiceGroupOptions(string $merchantId, string $section): array
    {
        $groups = InvoiceDynamicGroup::query()
            ->where('merchant_id', $merchantId)
            ->where('is_active', true)
            ->where('section', $section)
            ->orderBy('name')
            ->pluck('name', 'id');

        $defaultLabel = $section === 'header'
            ? 'Default (Current Header)'
            : 'Default (Current Footer)';

        $options = ['__default' => $defaultLabel];

        foreach ($groups as $groupId => $groupName) {
            $options[(string) $groupId] = trim((string) $groupName) ?: 'Details';
        }

        return $options;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function adjacentInvoiceUrls(string $type, Sale|Purchase $record): array
    {
        $ids = match ($type) {
            'sale' => Sale::query()
                ->where('merchant_id', $record->merchant_id)
                ->orderBy('sale_date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id')
                ->values(),
            'purchase' => Purchase::query()
                ->where('merchant_id', $record->merchant_id)
                ->orderBy('purchase_date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id')
                ->values(),
            default => collect(),
        };

        $currentIndex = $ids->search((string) $record->id);
        if ($currentIndex === false) {
            return [null, null];
        }

        $combo = request()->query('combo');
        $buildUrl = function (?string $invoiceId) use ($type, $combo): ?string {
            if (! $invoiceId) {
                return null;
            }

            $params = ['type' => $type, 'id' => $invoiceId];
            if (filled($combo)) {
                $params['combo'] = $combo;
            }

            return route('invoices.show', $params);
        };

        $previousId = $ids->get($currentIndex - 1);
        $nextId = $ids->get($currentIndex + 1);

        return [$buildUrl($previousId), $buildUrl($nextId)];
    }
}
