<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\Merchant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BranchUsersSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $zgn = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        $zgnBranches = Branch::where('merchant_id', $zgn->id)->get();

        $halaynoor = Merchant::where('email', 'info@halaynoor.com')->first();
        $halaynoorBranches = Branch::where('merchant_id', $halaynoor->id)->get();

        if ($zgn) {
            foreach ($zgnBranches as $index => $branch) {
                $this->createUser(
                    merchant: $zgn,
                    name: $branch->name . ' Staff',
                    email: "branch{$index}@zgngreenpvt.com",
                    roleName: 'Data Entry',
                    branchId: $branch->id
                );
            }
        }

        if ($halaynoor) {
            foreach ($halaynoorBranches as $index => $branch) {
                $this->createUser(
                    merchant: $zgn,
                    name: $branch->name . ' Staff',
                    email: "branch{$index}@halaynoor.com",
                    roleName: 'Data Entry',
                    branchId: $branch->id
                );
            }
        }
    }

    /**
     * @param Merchant $merchant
     * @param string $name
     * @param string $email
     * @param string $roleName
     * @param string $branchId
     * @return void
     */
    private function createUser(
        Merchant $merchant,
        string   $name,
        string   $email,
        string   $roleName,
        string   $branchId
    ): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'id' => Str::uuid(),
                'name' => $name,
                'merchant_id' => $merchant->id,
                'password' => 'DD@2025@DD',
                'status' => 'verified',
                'is_active' => true,
            ]
        );

        $role = Role::where('name', $roleName)->where('guard_name', 'merchant')->first();
        if ($role) $user->assignRole($role);

        BranchUser::firstOrCreate(
            [
                'user_id' => $user->id,
                'branch_id' => $branchId,
            ],
            [
                'id' => Str::uuid(),
            ]
        );
    }
}
