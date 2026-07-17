<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Services\Demo\DemoMerchantAccess;
use Database\Seeders\PermissionsModulesSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Console\Command;

class GrantMerchantFullAccessCommand extends Command
{
    protected $signature = 'merchant:grant-full-access
                            {email : Merchant email address}
                            {--password= : Set or reset the merchant password}';

    protected $description = 'Grant a merchant the Admin role, all permission modules, and all merchant-guard permissions';

    public function handle(DemoMerchantAccess $demoMerchantAccess): int
    {
        $email = (string) $this->argument('email');

        $this->ensurePermissionCatalogExists();

        $merchant = Merchant::query()->where('email', $email)->first();

        if (! $merchant) {
            $this->components->error("No merchant found with email: {$email}");

            return self::FAILURE;
        }

        if ($password = $this->option('password')) {
            $merchant->forceFill([
                'password' => $password,
                'status' => Merchant::STATUS_VERIFIED,
                'is_active' => true,
            ])->save();

            $this->components->info('Password updated and account activated.');
        }

        $demoMerchantAccess->grantFullAccess($merchant);

        $moduleCount = $merchant->permissionModules()->count();
        $permissionCount = $merchant->getAllPermissions()->count();

        $this->components->info("Full access granted to {$email}");
        $this->components->twoColumnDetail('Permission modules', (string) $moduleCount);
        $this->components->twoColumnDetail('Permissions (direct + via roles)', (string) $permissionCount);
        $this->components->twoColumnDetail('Roles', $merchant->getRoleNames()->implode(', '));

        return self::SUCCESS;
    }

    private function ensurePermissionCatalogExists(): void
    {
        if (\App\Models\Permission::query()->where('guard_name', 'merchant')->doesntExist()) {
            $this->components->warn('Seeding permissions catalog…');
            (new PermissionsSeeder)->run();
        }

        if (\App\Models\Role::query()->where('guard_name', 'merchant')->doesntExist()) {
            $this->components->warn('Seeding merchant roles…');
            (new RolesSeeder)->run();
        }

        if (\App\Models\PermissionModule::query()->doesntExist()) {
            $this->components->warn('Seeding permission modules…');
            (new PermissionsModulesSeeder)->run();
        }
    }
}
