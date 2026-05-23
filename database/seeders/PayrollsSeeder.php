<?php

namespace Database\Seeders;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PayrollsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_active', true)
            ->where('status', 'verified')
            ->with('merchant')
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('No active staff members found. Please run StaffsSeeder first.');

            return;
        }

        // Generate payrolls for the last 3 months including current month
        $months = [];
        $currentMonth = now()->month;
        $currentYear = now()->year;

        for ($i = 0; $i < 3; $i++) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->month,
                'year' => $date->year,
            ];
        }

        foreach ($users as $user) {
            foreach ($months as $period) {
                // Check if payroll already exists
                $existingPayroll = Payroll::where('merchant_id', $user->merchant_id)
                    ->where('user_id', $user->id)
                    ->where('period_month', $period['month'])
                    ->where('period_year', $period['year'])
                    ->first();

                if ($existingPayroll) {
                    $this->command->warn("Payroll already exists for {$user->name} ({$period['month']}/{$period['year']}). Skipping...");

                    continue;
                }

                // Determine base salary based on role/email
                $baseSalary = $this->getBaseSalaryForUser($user);

                // Generate allowances and deductions
                $allowances = $this->generateAllowances($baseSalary);
                $deductions = $this->generateDeductions($baseSalary);

                $totalAllowances = collect($allowances)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
                $totalDeductions = collect($deductions)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
                $netSalary = $baseSalary + $totalAllowances - $totalDeductions;

                // Determine status - older months are paid, current month is pending
                $status = ($period['month'] === $currentMonth && $period['year'] === $currentYear)
                    ? Payroll::STATUS_PENDING
                    : Payroll::STATUS_PAID;

                $paymentDate = $status === Payroll::STATUS_PAID
                    ? now()->setYear($period['year'])->setMonth($period['month'])->setDay(5)
                    : null;

                Payroll::create([
                    'id' => Str::uuid(),
                    'merchant_id' => $user->merchant_id,
                    'user_id' => $user->id,
                    'payroll_no' => 'PAY-'.str_pad($period['year'], 4, '0', STR_PAD_LEFT).str_pad($period['month'], 2, '0', STR_PAD_LEFT).'-'.strtoupper(substr(uniqid(), -6)),
                    'period_month' => $period['month'],
                    'period_year' => $period['year'],
                    'base_salary' => $baseSalary,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'net_salary' => $netSalary,
                    'status' => $status,
                    'payment_date' => $paymentDate,
                    'notes' => "Monthly payroll for {$period['month']}/{$period['year']}",
                    'created_by' => null,
                ]);

                $this->command->info("Created payroll for {$user->name} ({$user->merchant->name}) - {$period['month']}/{$period['year']}");
            }
        }

        $this->command->info('Payroll seeding completed!');
    }

    private function getBaseSalaryForUser(User $user): float
    {
        // Determine salary based on email/role
        $email = strtolower($user->email);

        if (str_contains($email, 'admin')) {
            return rand(80000, 120000) / 100; // $800 - $1200
        }

        if (str_contains($email, 'supervisor')) {
            return rand(60000, 90000) / 100; // $600 - $900
        }

        if (str_contains($email, 'support')) {
            return rand(50000, 70000) / 100; // $500 - $700
        }

        // Default salary
        return rand(40000, 60000) / 100; // $400 - $600
    }

    private function generateAllowances(float $baseSalary): array
    {
        $allowances = [];

        // Housing allowance (10-15% of base salary)
        if (rand(0, 10) > 3) {
            $allowances[] = [
                'name' => 'Housing Allowance',
                'amount' => round($baseSalary * (rand(10, 15) / 100), 2),
            ];
        }

        // Transportation allowance
        if (rand(0, 10) > 4) {
            $allowances[] = [
                'name' => 'Transportation Allowance',
                'amount' => rand(5000, 15000) / 100, // $50 - $150
            ];
        }

        // Medical allowance (5-8% of base salary)
        if (rand(0, 10) > 2) {
            $allowances[] = [
                'name' => 'Medical Allowance',
                'amount' => round($baseSalary * (rand(5, 8) / 100), 2),
            ];
        }

        return $allowances;
    }

    private function generateDeductions(float $baseSalary): array
    {
        $deductions = [];

        // Tax deduction (10-15% of base salary)
        $deductions[] = [
            'name' => 'Income Tax',
            'amount' => round($baseSalary * (rand(10, 15) / 100), 2),
        ];

        // Provident Fund (5-8% of base salary)
        if (rand(0, 10) > 3) {
            $deductions[] = [
                'name' => 'Provident Fund',
                'amount' => round($baseSalary * (rand(5, 8) / 100), 2),
            ];
        }

        // Insurance (2-4% of base salary)
        if (rand(0, 10) > 4) {
            $deductions[] = [
                'name' => 'Health Insurance',
                'amount' => round($baseSalary * (rand(2, 4) / 100), 2),
            ];
        }

        return $deductions;
    }
}
