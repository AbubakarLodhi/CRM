<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-500">Sales Summary</p>
                    <p class="text-xs text-gray-400">All time</p>
                </div>
                <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600">Sales</span>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-400">Total Sales</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($sales['total_sales']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Items Count</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($sales['total_items_count']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Quantity Sold</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($sales['total_quantity'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Avg Sale</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($sales['avg_sale'], 2) }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-2 text-sm">
                <div class="flex items-center justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span class="font-medium">{{ number_format($sales['total_subtotal'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-600">
                    <span>Discount</span>
                    <span class="font-medium">{{ number_format($sales['total_discount'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-600">
                    <span>Tax</span>
                    <span class="font-medium">{{ number_format($sales['total_tax'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-900">
                    <span class="font-semibold">Total Amount</span>
                    <span class="font-semibold">{{ number_format($sales['total_amount'], 2) }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-500">Purchases Summary</p>
                    <p class="text-xs text-gray-400">All time</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-600">Purchases</span>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-400">Total Purchases</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($purchases['total_purchases']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Items Count</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($purchases['total_items_count']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Quantity Bought</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($purchases['total_items_quantity'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Avg Purchase</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($purchases['avg_purchase'], 2) }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-2 text-sm">
                <div class="flex items-center justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span class="font-medium">{{ number_format($purchases['total_subtotal'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-600">
                    <span>Discount</span>
                    <span class="font-medium">{{ number_format($purchases['total_discount'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-600">
                    <span>Tax</span>
                    <span class="font-medium">{{ number_format($purchases['total_tax'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-900">
                    <span class="font-semibold">Total Amount</span>
                    <span class="font-semibold">{{ number_format($purchases['total_amount'], 2) }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-500">Stock Summary</p>
                    <p class="text-xs text-gray-400">All time</p>
                </div>
                <span class="rounded-full bg-orange-50 px-2 py-1 text-xs font-medium text-orange-600">Stock</span>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-400">Products</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($stock['total_products']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Available Stock</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($stock['available_stock'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Purchased Qty</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($stock['total_purchased_qty'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Sold Qty</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($stock['total_sold_qty'], 2) }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-2 text-sm">
                <div class="flex items-center justify-between text-gray-600">
                    <span>Total Revenue</span>
                    <span class="font-medium">{{ number_format($stock['total_revenue'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-600">
                    <span>Avg Selling Price</span>
                    <span class="font-medium">{{ number_format($stock['avg_selling_price'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-gray-600">
                    <span>Avg Buying Price</span>
                    <span class="font-medium">{{ number_format($stock['avg_buying_price'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
