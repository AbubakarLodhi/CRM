<div class="space-y-4 text-sm">
    <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
        <div>
            <div class="text-gray-500">Original Amount</div>
            <div class="font-semibold">PKR {{ number_format((float) ($base->amount ?? 0), 2) }}</div>
        </div>
        <div>
            <div class="text-gray-500">Settled</div>
            <div class="font-semibold">PKR {{ number_format((float) ($settled ?? 0), 2) }}</div>
        </div>
        <div>
            <div class="text-gray-500">Remaining</div>
            <div class="font-semibold">PKR {{ number_format((float) ($remaining ?? 0), 2) }}</div>
        </div>
        <div>
            <div class="text-gray-500">Account Type</div>
            <div class="font-semibold">{{ \App\Models\CashFlow::flowTypeLabel($base->flow_type ?? null) }}</div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full min-w-[640px] divide-y divide-gray-200 text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 font-medium">Date</th>
                    <th class="px-3 py-2 font-medium">Type</th>
                    <th class="px-3 py-2 font-medium">Direction</th>
                    <th class="px-3 py-2 font-medium text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($history as $entry)
                    <tr>
                        <td class="px-3 py-2">{{ optional($entry->flow_date)->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $entry->settlement_for_id ? 'Settlement' : 'Original' }}</td>
                        <td class="px-3 py-2">{{ $entry->direction === 'in' ? 'In' : 'Out' }}</td>
                        <td class="px-3 py-2 text-right">PKR {{ number_format((float) ($entry->amount ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-3 text-center text-gray-500">No history found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
