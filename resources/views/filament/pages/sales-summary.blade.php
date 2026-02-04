<x-filament-panels::page>

    @php
        $user  = \Filament\Facades\Filament::auth()->user();
        $guard = \Filament\Facades\Filament::getCurrentPanel()->getAuthGuard();

        // EXACT same logic as Sales canView()
        $canView = $user
            && \App\Models\PermissionModule::isEnabledForCurrentMerchant('reports')
            && $user->hasPermissionTo('reports.view', $guard);
    @endphp

    {{-- ========================= --}}
    {{-- ACCESS LOCKED --}}
    {{-- ========================= --}}
    @if (! $canView)

        <div class="flex items-center justify-center h-[60vh]">
            <div class="text-center max-w-md">

                <x-heroicon-o-lock-closed class="mx-auto h-16 w-16 text-gray-400" />

                <h2 class="mt-4 text-2xl font-semibold text-gray-900">
                    Sales Locked
                </h2>

                <p class="mt-3 text-sm text-gray-500">
                    You don’t have permission to view sales.
                    Please contact your administrator to request access.
                </p>

            </div>
        </div>

        {{-- ========================= --}}
        {{-- SALES SUMMARY CONTENT --}}
        {{-- ========================= --}}
    @else

        @php($stats = $this->getSalesStats())

        {{-- ========================= --}}
        {{-- SALES OVERVIEW --}}
        {{-- ========================= --}}
        <div class="mb-6">
            <h2 class="mb-3 text-lg font-semibold">Sales Overview</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-receipt-percent class="h-4 w-4 text-primary-500"/>
                        Total Sales
                    </div>
                    <div class="text-2xl font-bold">
                        {{ $stats['total_sales'] }}
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-rectangle-stack class="h-4 w-4 text-info-500"/>
                        Item Count
                    </div>
                    <div class="text-2xl font-bold">
                        {{ $stats['total_items_count'] }}
                    </div>
                    <div class="text-xs text-gray-400">
                        Number of sale item rows
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-cube class="h-4 w-4 text-danger-500"/>
                        Quantity Sold
                    </div>
                    <div class="text-2xl font-bold text-danger-700">
                        {{ number_format($stats['total_quantity']) }}
                    </div>
                    <div class="text-xs text-gray-400">
                        Actual units sold
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-currency-dollar class="h-4 w-4 text-success-600"/>
                        Total Revenue
                    </div>
                    <div class="text-2xl font-bold text-success-700">
                        Rs {{ number_format($stats['total_amount'], 2) }}
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-chart-bar class="h-4 w-4 text-warning-500"/>
                        Avg Sale Value
                    </div>
                    <div class="text-2xl font-bold text-warning-600">
                        Rs {{ number_format($stats['avg_sale'], 2) }}
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
                    <div class="text-sm text-gray-500">Subtotal</div>
                    <div class="text-2xl font-bold text-primary-700">
                        Rs {{ number_format($stats['total_subtotal'], 2) }}
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500">Total Discount</div>
                    <div class="text-2xl font-bold text-danger-600">
                        Rs {{ number_format($stats['total_discount'], 2) }}
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500">Total Tax</div>
                    <div class="text-2xl font-bold text-success-700">
                        Rs {{ number_format($stats['total_tax'], 2) }}
                    </div>
                </x-filament::card>

            </div>
        </div>

        {{-- ========================= --}}
        {{-- SALES TABLE --}}
        {{-- ========================= --}}
        {{ $this->table }}

    @endif

</x-filament-panels::page>
