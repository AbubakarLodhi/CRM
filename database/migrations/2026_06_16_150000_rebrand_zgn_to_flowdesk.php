<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceInColumn('merchants', 'email', '@zgngreenpvt.com', '@flowdesk.com');
        $this->replaceInColumn('users', 'email', '@zgngreenpvt.com', '@flowdesk.com');
        $this->replaceInColumn('customers', 'email', '@zgngreenpvt.com', '@flowdesk.com');
        $this->replaceInColumn('vendors', 'email', '@zgngreenpvt.com', '@flowdesk.com');

        if (Schema::hasTable('merchants')) {
            DB::table('merchants')
                ->where(function ($query): void {
                    $query->where('email', config('branding.legacy_merchant_email', 'info@zgngreenpvt.com'))
                        ->orWhere('email', config('branding.primary_merchant_email', 'info@flowdesk.com'));
                })
                ->update([
                    'email' => config('branding.primary_merchant_email', 'info@flowdesk.com'),
                    'name' => config('branding.name', 'Flowdesk'),
                    'website' => config('branding.primary_merchant_website', 'https://flowdesk.app'),
                ]);

            $this->replaceInColumn('merchants', 'website', 'zgngreenpvt.com', 'flowdesk.app');
        }

        foreach (['merchants', 'users', 'businesses', 'branches', 'brands', 'brand_models', 'products', 'customers', 'vendors'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($this->columnsFor($table) as $column) {
                $this->replaceZgnBranding($table, $column);
            }
        }
    }

    public function down(): void
    {
        // Irreversible content migration.
    }

    /**
     * @return list<string>
     */
    private function columnsFor(string $table): array
    {
        return match ($table) {
            'merchants' => ['name', 'website'],
            'users', 'customers', 'vendors', 'brands', 'brand_models' => ['name', 'email'],
            'businesses' => ['name', 'description'],
            'branches' => ['name', 'address'],
            'products' => ['name', 'sku', 'description'],
            default => [],
        };
    }

    private function replaceZgnBranding(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        $pairs = [
            'ZGN Green Private Limited' => 'Flowdesk',
            'ZGN GREEN PVT LTD' => 'Flowdesk',
            'ZGN Green Pvt' => 'Flowdesk',
            'ZGN Demo Business' => 'Flowdesk Demo Business',
            'ZGN Admin User' => 'Flowdesk Admin',
            'ZGN Supervisor' => 'Flowdesk Supervisor',
            'ZGN Fabrication' => 'Flowdesk Fabrication',
            'ZGN Accessories' => 'Flowdesk Accessories',
            'ZGN Services' => 'Flowdesk Services',
            'Evee Zgn Green' => 'Evee Flowdesk',
            'Evee zgn green Ellahabad' => 'Evee Flowdesk Ellahabad',
            'zgn green solar ELLAHABAD' => 'Flowdesk Solar Ellahabad',
            'ZGN ' => 'Flowdesk ',
            'zgn ' => 'flowdesk ',
            'ZGN-' => 'FD-',
            'zgn-' => 'fd-',
        ];

        foreach ($pairs as $search => $replace) {
            DB::update(
                "UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, ?, ?) WHERE `{$column}` LIKE ?",
                [$search, $replace, '%'.$search.'%']
            );
        }
    }

    private function replaceInColumn(string $table, string $column, string $search, string $replace): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::update(
            "UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, ?, ?) WHERE `{$column}` LIKE ?",
            [$search, $replace, '%'.$search.'%']
        );
    }
};
