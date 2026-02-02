<?php

namespace App\Http\Controllers\Invoice;

use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Http\Request;
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
            ])->find($id),

            'purchase' => Purchase::with([
                'merchant.logo',
                'items.product',
                'items.variants.variant',
            ])->find($id),

            default => throw new NotFoundHttpException(),
        };

        if (! $record) {
            abort(404);
        }

        return view('filament.pages.invoice', [
            'type'   => $type,
            'record' => $record,
        ]);
    }
}
