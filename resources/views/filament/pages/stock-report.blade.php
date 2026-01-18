<x-filament-panels::page>

    @php
        $user  = \Filament\Facades\Filament::auth()->user();
        $guard = \Filament\Facades\Filament::getCurrentPanel()->getAuthGuard();

        // User can view inventory if they can view sales OR purchases
        $canView = $user
            && \App\Models\PermissionModule::isEnabledForCurrentMerchant('sales')
            && (
                $user->hasPermissionTo('sales.view', $guard)
                || $user->hasPermissionTo('purchases.view', $guard)
            );
    @endphp

    {{-- ========================= --}}
    {{-- ACCESS LOCKED --}}
    {{-- ========================= --}}
    @if (! $canView)

        <div class="flex items-center justify-center h-[60vh]">
            <div class="text-center max-w-md">

                <x-heroicon-o-lock-closed class="mx-auto h-16 w-16 text-gray-400" />

                <h2 class="mt-4 text-2xl font-semibold text-gray-900">
                    Inventory Locked
                </h2>

                <p class="mt-3 text-sm text-gray-500">
                    You don’t have permission to view inventory.
                    Please ensure you have access to Sales or Purchases,
                    or contact your administrator.
                </p>

            </div>
        </div>

        {{-- ========================= --}}
        {{-- INVENTORY CONTENT --}}
        {{-- ========================= --}}
    @else

        @php($stats = $this->getTopStats())

        {{-- ========================= --}}
        {{-- INVENTORY OVERVIEW --}}
        {{-- ========================= --}}
        <div class="mb-8">
            <h2 class="mb-3 text-lg font-semibold">Inventory Overview</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

                {{-- Total Products --}}
                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-archive-box class="h-4 w-4 text-primary-500" />
                        <span>Total Products</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-primary-700">
                        {{ $stats['total_products'] }}
                    </div>
                </x-filament::card>

                {{-- Total Purchased Qty --}}
                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4 text-indigo-500" />
                        <span>Total Purchased Qty</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-indigo-700">
                        {{ number_format($stats['total_purchased_qty']) }}
                    </div>
                </x-filament::card>

                {{-- Total Sold Qty --}}
                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-arrow-up-tray class="h-4 w-4 text-emerald-500" />
                        <span>Total Sold Qty</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-700">
                        {{ number_format($stats['total_sold_qty']) }}
                    </div>
                </x-filament::card>

                {{-- Available Stock --}}
                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-cube-transparent
                            class="h-4 w-4 {{ $stats['available_stock'] < 0 ? 'text-danger-500' : 'text-success-500' }}"
                        />
                        <span>Available Stock</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold {{ $stats['available_stock'] < 0 ? 'text-danger-600' : 'text-success-600' }}">
                        {{ number_format($stats['available_stock']) }}
                    </div>
                </x-filament::card>

            </div>
        </div>

        {{-- ========================= --}}
        {{-- FINANCIAL OVERVIEW --}}
        {{-- ========================= --}}
        <div class="mb-8">
            <h2 class="mb-3 text-lg font-semibold">Financial Overview</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

                {{-- Total Revenue --}}
                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-banknotes class="h-4 w-4 text-success-500" />
                        <span>Total Revenue</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-success-700">
                        {{ number_format($stats['total_revenue'], 2) }}
                    </div>
                </x-filament::card>

                {{-- Avg Selling Price --}}
                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-tag class="h-4 w-4 text-sky-500" />
                        <span>Avg Selling Price</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-sky-700">
                        {{ number_format($stats['avg_selling_price'], 2) }}
                    </div>
                </x-filament::card>

                {{-- Avg Buying Price --}}
                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-shopping-cart class="h-4 w-4 text-amber-500" />
                        <span>Avg Buying Price</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-amber-700">
                        {{ number_format($stats['avg_buying_price'], 2) }}
                    </div>
                </x-filament::card>

                {{-- Avg Profit --}}
                @php($profit = $stats['avg_selling_price'] - $stats['avg_buying_price'])

                <x-filament::card>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-heroicon-o-chart-bar
                            class="h-4 w-4 {{ $profit < 0 ? 'text-danger-500' : 'text-emerald-500' }}"
                        />
                        <span>Avg Profit / Item</span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold {{ $profit < 0 ? 'text-danger-600' : 'text-emerald-600' }}">
                        {{ number_format($profit, 2) }}
                    </div>
                </x-filament::card>

            </div>
        </div>

        {{-- ========================= --}}
        {{-- TABLE --}}
        {{-- ========================= --}}
        <div class="mt-6">
            {{ $this->table }}
        </div>

    @endif

</x-filament-panels::page>
