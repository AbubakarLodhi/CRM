<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateMonthlyPayrolls extends Command
{
    protected $signature = 'payrolls:generate-monthly';

    protected $description = 'Generate payroll records for all active staff members at the start of each month';

    public function handle(): int
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $this->info("Generating payrolls for {$currentMonth}/{$currentYear}...");

        $users = User::where('is_active', true)
            ->where('status', 'verified')
            ->with('merchant')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('No active staff members found.');

            return self::SUCCESS;
        }

        $generated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Check if payroll already exists for this user and period
            $existingPayroll = Payroll::where('merchant_id', $user->merchant_id)
                ->where('user_id', $user->id)
                ->where('period_month', $currentMonth)
                ->where('period_year', $currentYear)
                ->first();

            if ($existingPayroll) {
                $this->warn("Payroll already exists for {$user->name} ({$currentMonth}/{$currentYear}). Skipping...");
                $skipped++;
                continue;
            }

            // Get base salary from last payroll, or default to 0
            $lastPayroll = Payroll::where('merchant_id', $user->merchant_id)
                ->where('user_id', $user->id)
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc')
                ->first();

            $baseSalary = $lastPayroll?->base_salary ?? 0;
            $allowances = $lastPayroll?->allowances ?? [];
            $deductions = $lastPayroll?->deductions ?? [];

            $totalAllowances = collect($allowances)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
            $totalDeductions = collect($deductions)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
            $netSalary = $baseSalary + $totalAllowances - $totalDeductions;

            Payroll::create([
                'id' => Str::uuid(),
                'merchant_id' => $user->merchant_id,
                'user_id' => $user->id,
                'payroll_no' => 'PAY-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6)),
                'period_month' => $currentMonth,
                'period_year' => $currentYear,
                'base_salary' => $baseSalary,
                'allowances' => $allowances,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
                'status' => Payroll::STATUS_PENDING,
                'created_by' => null,
            ]);

            $this->info("Generated payroll for {$user->name} (Merchant: {$user->merchant->name})");
            $generated++;
        }

        $this->info("Payroll generation complete! Generated: {$generated}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
