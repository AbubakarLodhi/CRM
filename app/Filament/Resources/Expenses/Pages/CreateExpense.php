<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $user = Filament::auth()->user();
            if ($user && ! ($user instanceof \App\Models\Admin)) {
                $data['merchant_id'] = $user->id;
            }

            $subtotal = collect($items)->sum(fn ($i) => (float) ($i['line_total'] ?? 0));
            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);

            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal - $discount + $tax;

            $expense = static::getModel()::create($data);

            foreach ($items as $item) {
                $expense->items()->create($item);
            }

            return $expense;
        });
    }
}
