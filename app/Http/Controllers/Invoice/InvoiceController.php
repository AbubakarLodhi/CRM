<?php

namespace App\Http\Controllers\Invoice;

use App\Services\InvoiceDynamicFieldResolver;
use App\Models\Sale;
use App\Models\Purchase;
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

        $headerGroup = trim((string) request()->query('header_group', '__default'));
        $footerGroup = trim((string) request()->query('footer_group', '__default'));

        $dynamicGroups = InvoiceDynamicFieldResolver::resolveGroups($record, $headerGroup, $footerGroup);

        return view('filament.pages.invoice', [
            'type'   => $type,
            'record' => $record,
            'headerGroups' => $dynamicGroups['header'],
            'footerGroups' => $dynamicGroups['footer'],
            'showDefaultHeader' => $headerGroup === '__default',
            'showDefaultFooter' => $footerGroup === '__default',
        ]);
    }
}
