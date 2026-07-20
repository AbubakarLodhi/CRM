<?php

namespace App\Services\Demo;

use App\Models\Asset;
use App\Models\CashFlow;
use App\Models\Expense;
use App\Models\Merchant;
use App\Models\Payroll;
use App\Models\Purchase;
use App\Models\Sale;
use App\Support\DemoAccount;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class TemporaryDemoProvisioner
{
    public function __construct(private DemoMerchantAccess $demoMerchantAccess) {}

    public function provisionForSession(string $sessionId): Merchant
    {
        $email = DemoAccount::temporaryEmailForSession($sessionId);
        $password = (string) config('demo.password');

        $merchant = Merchant::query()->where('email', $email)->first();

        if ($merchant) {
            return $this->ensureMerchantReady($merchant);
        }

        $merchant = Merchant::query()->create([
            'id' => Str::uuid()->toString(),
            'email' => $email,
            'name' => (string) config('demo.merchant_name'),
            'phone' => null,
            'address_line_1' => 'Demo City',
            'city' => 'Karachi',
            'website' => config('branding.primary_merchant_website'),
            'status' => Merchant::STATUS_VERIFIED,
            'is_active' => true,
            'password' => $password,
        ]);

        return $this->ensureMerchantReady($merchant);
    }

    private function ensureMerchantReady(Merchant $merchant): Merchant
    {
        $merchant->forceFill([
            'name' => (string) config('demo.merchant_name'),
            'status' => Merchant::STATUS_VERIFIED,
            'is_active' => true,
        ])->save();

        $this->demoMerchantAccess->grantFullAccess($merchant);

        if ($this->needsSampleData($merchant)) {
            $this->seedSampleData($merchant);
        }

        return $merchant->refresh();
    }

    private function needsSampleData(Merchant $merchant): bool
    {
        if (! $merchant->businesses()->exists()) {
            return true;
        }

        if ($merchant->products()->count() === 0) {
            return true;
        }

        if (Sale::query()->where('merchant_id', $merchant->id)->count() === 0) {
            return true;
        }

        if (Purchase::query()->where('merchant_id', $merchant->id)->count() === 0) {
            return true;
        }

        if ($merchant->customers()->count() === 0) {
            return true;
        }

        if (Expense::query()->where('merchant_id', $merchant->id)->count() === 0) {
            return true;
        }

        if (Payroll::query()->where('merchant_id', $merchant->id)->count() === 0) {
            return true;
        }

        if (CashFlow::query()->where('merchant_id', $merchant->id)->count() === 0) {
            return true;
        }

        return Asset::query()->where('merchant_id', $merchant->id)->count() === 0;
    }

    private function seedSampleData(Merchant $merchant): void
    {
        $demoSeeder = new DemoSeeder;
        $demoSeeder->setCommand($this->silentCommand());
        $demoSeeder->forMerchant($merchant->email);
    }

    private function silentCommand(): Command
    {
        $command = new class extends Command
        {
            protected $signature = 'demo:provision-silent';

            public function handle(): int
            {
                return self::SUCCESS;
            }
        };

        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));

        return $command;
    }
}
