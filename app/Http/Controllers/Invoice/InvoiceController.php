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
                'items.business',
                'items.branch',
            ])->find($id),

            'purchase' => Purchase::with([
                'merchant.logo',
                'vendor',
                'items.product',
                'items.variants.variant',
                'items.business',
                'items.branch',
            ])->find($id),

            default => throw new NotFoundHttpException(),
        };

        if (! $record) {
            abort(404);
        }

        $headerGroupOptions = $this->invoiceGroupOptions((string) $record->merchant_id, 'header');
        $footerGroupOptions = $this->invoiceGroupOptions((string) $record->merchant_id, 'footer');

        $headerGroup = trim((string) request()->query('header_group', '__default'));
        $footerGroup = trim((string) request()->query('footer_group', '__default'));

        if (! array_key_exists($headerGroup, $headerGroupOptions)) {
            $headerGroup = '__default';
        }

        if (! array_key_exists($footerGroup, $footerGroupOptions)) {
            $footerGroup = '__default';
        }

        $dynamicGroups = InvoiceDynamicFieldResolver::resolveGroups($record, $headerGroup, $footerGroup);

        return view('filament.pages.invoice', [
            'type'   => $type,
            'record' => $record,
            'headerGroups' => $dynamicGroups['header'],
            'footerGroups' => $dynamicGroups['footer'],
            'headerGroupOptions' => $headerGroupOptions,
            'footerGroupOptions' => $footerGroupOptions,
            'selectedHeaderGroup' => $headerGroup,
            'selectedFooterGroup' => $footerGroup,
            'showDefaultHeader' => $headerGroup === '__default',
            'showDefaultFooter' => $footerGroup === '__default',
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
}
