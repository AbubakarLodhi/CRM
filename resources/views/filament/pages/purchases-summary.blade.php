<x-filament-panels::page>

    @php($stats = $this->getPurchaseStats())

    {{-- ========================= --}}
    {{-- PURCHASE OVERVIEW --}}
    {{-- ========================= --}}
    <div class="mb-6">
        <h2 class="mb-3 text-lg font-semibold">Purchase Overview</h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

            {{-- Total Purchases --}}
            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-shopping-cart class="h-4 w-4 text-primary-500"/>
                    Total Purchases
                </div>
                <div class="text-2xl font-bold">
                    {{ $stats['total_purchases'] }}
                </div>
            </x-filament::card>

            {{-- Item Lines Count --}}
            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-rectangle-stack class="h-4 w-4 text-info-500"/>
                    Items Count
                </div>
                <div class="text-2xl font-bold">
                    {{ $stats['total_items_count'] }}
                </div>
                <div class="text-xs text-gray-400">
                    Number of  items
                </div>
            </x-filament::card>

            {{-- Quantity Purchased --}}
            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-cube class="h-4 w-4 text-success-500"/>
                    Total Quantity
                </div>
                <div class="text-2xl font-bold text-success-700">
                    {{ number_format($stats['total_items_quantity']) }}
                </div>
                <div class="text-xs text-gray-400">
                    Actual units purchased
                </div>
            </x-filament::card>

            {{-- Total Purchase Amount --}}
            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-currency-dollar class="h-4 w-4 text-success-600"/>
                    Total Amount
                </div>
                <div class="text-2xl font-bold text-success-700">
                    {{ number_format($stats['total_amount'], 2) }}
                </div>
            </x-filament::card>

            {{-- Avg Purchase Value --}}
            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-chart-bar class="h-4 w-4 text-warning-500"/>
                    Avg Purchase
                </div>
                <div class="text-2xl font-bold text-warning-600">
                    {{ number_format($stats['avg_purchase'], 2) }}
                </div>
            </x-filament::card>

        </div>
    </div>

    {{-- ========================= --}}
    {{-- FINANCIAL BREAKDOWN --}}
    {{-- ========================= --}}
    <div class="mb-6">
        <h2 class="mb-3 text-lg font-semibold">Financial Breakdown</h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-receipt-percent class="h-4 w-4 text-primary-500"/>
                    Subtotal
                </div>
                <div class="text-2xl font-bold text-primary-700">
                    {{ number_format($stats['total_subtotal'], 2) }}
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-tag class="h-4 w-4 text-danger-500"/>
                    Total Discount
                </div>
                <div class="text-2xl font-bold text-danger-600">
                    {{ number_format($stats['total_discount'], 2) }}
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-scale class="h-4 w-4 text-success-500"/>
                    Total Tax
                </div>
                <div class="text-2xl font-bold text-success-700">
                    {{ number_format($stats['total_tax'], 2) }}
                </div>
            </x-filament::card>

        </div>
    </div>


    {{-- ========================= --}}
    {{-- TABLE --}}
    {{-- ========================= --}}
    {{ $this->table }}

</x-filament-panels::page>
