@php
    $payments = \App\Services\PaymentLedgerService::displayablePayments($document);

    $totalAmount = (float) ($document->total_amount ?? 0);
    $paidAmount = (float) ($document->paid_amount ?? 0);
    $dueAmount = (float) ($document->due_amount ?? 0);
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="rounded-lg border border-gray-200 p-3">
            <div class="text-xs text-gray-500">Total Amount</div>
            <div class="mt-1 text-base font-semibold text-gray-900">PKR {{ number_format($totalAmount, 2) }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 p-3">
            <div class="text-xs text-gray-500">Paid Amount</div>
            <div class="mt-1 text-base font-semibold text-gray-900">PKR {{ number_format($paidAmount, 2) }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 p-3">
            <div class="text-xs text-gray-500">Due Amount</div>
            <div class="mt-1 text-base font-semibold {{ $dueAmount > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                PKR {{ number_format($dueAmount, 2) }}
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-gray-900">Payment History</h4>

        @if($payments->isEmpty())
            <p class="mt-2 text-sm text-gray-500">No payments recorded yet.</p>
        @else
            <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Date & Time</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Type</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Amount</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($payments as $payment)
                            <tr>
                                <td class="px-3 py-2 text-gray-700">
                                    {{ $payment->payment_date?->format('d/m/Y') ?? '—' }}
                                    <span class="ml-1 text-xs text-gray-500">
                                        {{ $payment->created_at?->format('h:i A') ?? '' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-700">{{ ucfirst((string) ($payment->entry_type ?? 'payment')) }}</td>
                                <td class="px-3 py-2 font-medium text-gray-900">PKR {{ number_format((float) ($payment->display_amount ?? 0), 2) }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $payment->reference_no ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
