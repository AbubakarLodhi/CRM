<?php

namespace Tests\Unit;

use App\Filament\Resources\Sales\Pages\CreateSale;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PosSaleCreditReminderTest extends TestCase
{
    public function test_pos_credit_sale_payload_sets_credit_payment_type_and_due_date(): void
    {
        $data = [
            'sale_date' => '2026-06-02',
            'total_amount' => 200.0,
            'payment_method' => 'credit',
            'paid_amount' => 50.0,
            'due_date' => '2026-06-04',
        ];

        $this->applyPaymentFields($data);

        $this->assertSame('credit', $data['payment_type']);
        $this->assertSame(50.0, $data['paid_amount']);
        $this->assertSame(150.0, $data['due_amount']);
        $this->assertSame('2026-06-04', $data['due_date']);
        $this->assertArrayNotHasKey('payment_method', $data);
    }

    public function test_pos_partial_payment_with_cash_method_still_becomes_credit(): void
    {
        $data = [
            'sale_date' => '2026-06-02',
            'total_amount' => 200.0,
            'payment_method' => 'cash',
            'paid_amount' => 50.0,
            'due_date' => '2026-06-10',
        ];

        $this->applyPaymentFields($data);

        $this->assertSame('credit', $data['payment_type']);
        $this->assertSame(50.0, $data['paid_amount']);
        $this->assertSame(150.0, $data['due_amount']);
    }

    public function test_pos_full_payment_clears_credit_reminders_eligibility(): void
    {
        $data = [
            'sale_date' => '2026-06-02',
            'total_amount' => 200.0,
            'payment_method' => 'cash',
            'paid_amount' => 200.0,
            'due_date' => '2026-06-10',
        ];

        $this->applyPaymentFields($data);

        $this->assertSame('cash', $data['payment_type']);
        $this->assertSame(0.0, $data['due_amount']);
        $this->assertNull($data['due_date']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyPaymentFields(array &$data): void
    {
        $method = new ReflectionMethod(CreateSale::class, 'applyPaymentFields');
        $method->invokeArgs(null, [&$data]);
    }
}
