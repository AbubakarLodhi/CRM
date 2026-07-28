<?php

namespace App\Services\Demo;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\CashFlow;
use App\Models\DemoVisitorSession;
use App\Models\Expense;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Support\DemoAccount;
use Illuminate\Support\Facades\DB;

class TemporaryDemoCleanup
{
    public function purgeExpiredSessions(): int
    {
        $purged = 0;

        DemoVisitorSession::query()
            ->where('expires_at', '<=', now())
            ->each(function (DemoVisitorSession $session) use (&$purged): void {
                $this->purgeExpiredSession($session);
                $purged++;
            });

        return $purged;
    }

    public function purgeExpiredSession(DemoVisitorSession $session): void
    {
        if ($session->merchant_id !== null) {
            $this->purgeSessionMerchant($session);

            return;
        }

        $merchant = Merchant::query()
            ->where('email', DemoAccount::temporaryEmailForSession($session->id))
            ->first();

        if ($merchant instanceof Merchant) {
            $this->deleteMerchant($merchant);
        }
    }

    public function purgeSessionMerchant(DemoVisitorSession $session): void
    {
        if ($session->merchant_id === null) {
            return;
        }

        $merchantId = $session->merchant_id;

        DB::transaction(function () use ($merchantId, $session): void {
            $merchant = Merchant::query()->find($merchantId);

            if ($merchant instanceof Merchant) {
                $this->deleteMerchant($merchant);
            }

            $session->update(['merchant_id' => null]);
        });
    }

    public function purgeAllTemporaryMerchants(): int
    {
        $purged = 0;

        Merchant::query()
            ->where('email', 'like', 'demo-%@'.config('demo.temporary_email_domain', 'crmdemo.com'))
            ->each(function (Merchant $merchant) use (&$purged): void {
                $this->deleteMerchant($merchant);
                $purged++;
            });

        return $purged;
    }

    public function deleteMerchant(Merchant $merchant): void
    {
        if (! DemoAccount::isTemporaryDemoEmail($merchant->email)) {
            return;
        }

        DB::transaction(function () use ($merchant): void {
            $this->deleteMerchantTransactionalData($merchant);

            $merchant->roles()->detach();

            User::query()
                ->where('merchant_id', $merchant->id)
                ->each(function (User $user): void {
                    $user->roles()->detach();
                    $user->delete();
                });

            $merchant->delete();
        });
    }

    private function deleteMerchantTransactionalData(Merchant $merchant): void
    {
        $merchantId = $merchant->id;

        $branchIds = DB::table('branches')->where('merchant_id', $merchantId)->pluck('id');
        $businessIds = DB::table('businesses')->where('merchant_id', $merchantId)->pluck('id');

        SaleReturn::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (SaleReturn $saleReturn) => $saleReturn->forceDelete()
        );

        PurchaseReturn::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (PurchaseReturn $purchaseReturn) => $purchaseReturn->forceDelete()
        );

        if ($branchIds->isNotEmpty()) {
            DB::table('purchase_items')->whereIn('branch_id', $branchIds)->update(['branch_id' => null]);
        }

        if ($businessIds->isNotEmpty()) {
            DB::table('purchase_items')->whereIn('business_id', $businessIds)->update(['business_id' => null]);
        }

        Payment::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (Payment $payment) => $payment->forceDelete()
        );

        Purchase::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (Purchase $purchase) => $purchase->forceDelete()
        );

        Sale::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (Sale $sale) => $sale->forceDelete()
        );

        Order::query()->where('merchant_id', $merchantId)->delete();

        Expense::query()->where('merchant_id', $merchantId)->delete();

        CashFlow::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (CashFlow $cashFlow) => $cashFlow->forceDelete()
        );

        Payroll::query()->where('merchant_id', $merchantId)->delete();

        Asset::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (Asset $asset) => $asset->forceDelete()
        );

        AssetType::withTrashed()->where('merchant_id', $merchantId)->each(
            fn (AssetType $assetType) => $assetType->forceDelete()
        );
    }
}
