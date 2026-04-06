<x-filament-widgets::widget>
    @php
        $labels = $trend['labels'] ?? [];
        $salesSeries = $trend['sales'] ?? [];
        $purchaseSeries = $trend['purchases'] ?? [];
        $seriesAll = array_merge($salesSeries, $purchaseSeries);
        $maxValue = max($seriesAll ?: [1]);
        $chartWidth = 560;
        $chartHeight = 260;
        $axisLeft = 36;
        $axisRight = 12;
        $axisTop = 10;
        $axisBottom = 24;
        $plotWidth = $chartWidth - $axisLeft - $axisRight;
        $plotHeight = $chartHeight - $axisTop - $axisBottom;
        $pointCount = max(count($salesSeries), 1);
        $step = $pointCount > 1 ? $plotWidth / ($pointCount - 1) : 0;
        $makePoints = function (array $series) use ($axisLeft, $axisTop, $plotHeight, $pointCount, $step, $maxValue) {
            $points = [];
            foreach ($series as $index => $value) {
                $x = $axisLeft + ($index * $step);
                $normalized = $maxValue > 0 ? $value / $maxValue : 0;
                $y = $axisTop + ($plotHeight - ($plotHeight * $normalized));
                $points[] = $x . ',' . $y;
            }
            return implode(' ', $points);
        };
        $salesPoints = $makePoints($salesSeries);
        $purchasePoints = $makePoints($purchaseSeries);
        $periodSalesTotal = array_sum($salesSeries);
        $periodPurchasesTotal = array_sum($purchaseSeries);
        $leaders = $leaders ?? ['customers' => [], 'vendors' => [], 'variants' => []];
        $credit = $credit ?? ['receivable_total' => 0, 'payable_total' => 0, 'top_customers' => [], 'top_vendors' => []];
        $expenses = $expenses ?? ['total_expenses' => 0, 'total_amount' => 0, 'avg_expense' => 0];
        $funds = $funds ?? ['opening_total_funds' => 0, 'sales_cash_inflow' => 0, 'purchases_cash_outflow' => 0, 'expenses_outflow' => 0, 'payroll_outflow' => 0, 'cash_flow_net' => 0, 'net_cash_movement' => 0, 'current_total_funds' => 0];
        $barMax = max($seriesAll ?: [1]);
        $barHeights = [
            'sales' => array_map(fn ($value) => $barMax > 0 ? (int) round(($value / $barMax) * 100) : 0, $salesSeries),
            'purchases' => array_map(fn ($value) => $barMax > 0 ? (int) round(($value / $barMax) * 100) : 0, $purchaseSeries),
        ];
        $tickCount = 4;
        $ticks = [];
        for ($i = 0; $i <= $tickCount; $i++) {
            $ticks[] = [
                'value' => (int) round($maxValue * (1 - ($i / $tickCount))),
                'y' => $axisTop + ($plotHeight * ($i / $tickCount)),
            ];
        }
    @endphp

    <div class="space-y-6 stats-dashboard" x-data="{ showSales: true, showPurchases: true, toggle(which) { if (which === 'sales') { this.showSales = !this.showSales; if (!this.showSales && !this.showPurchases) this.showPurchases = true; } if (which === 'purchases') { this.showPurchases = !this.showPurchases; if (!this.showPurchases && !this.showSales) this.showSales = true; } } }">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 stats-card dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700/40">
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">Overview</p>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-200">All time</span>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm ring-1 ring-blue-100 stats-panel stats-panel-blue dark:bg-slate-900 dark:ring-blue-900/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Sales Summary</p>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 dark:bg-blue-900/40 dark:text-blue-200">Sales</span>
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($sales['total_amount'], 2) }}</p>
                    <div class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>Total Sales</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($sales['total_sales']) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Quantity Sold</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($sales['total_quantity'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm ring-1 ring-emerald-100 stats-panel stats-panel-emerald dark:bg-slate-900 dark:ring-emerald-900/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Purchases Summary</p>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-200">Purchases</span>
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($purchases['total_amount'], 2) }}</p>
                    <div class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>Total Purchases</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($purchases['total_purchases']) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Quantity Bought</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($purchases['total_items_quantity'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-orange-50 to-white p-5 shadow-sm ring-1 ring-orange-100 stats-panel stats-panel-orange dark:bg-slate-900 dark:ring-orange-900/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Stock Summary</p>
                        <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-900/40 dark:text-orange-200">Stock</span>
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stock['available_stock'], 2) }}</p>
                    <div class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>Total Products</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($stock['total_products']) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Total Revenue</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($stock['total_revenue'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm ring-1 ring-sky-100 stats-panel stats-panel-sky dark:bg-slate-900 dark:ring-sky-900/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Inventory Movement</p>
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-600 dark:bg-sky-900/40 dark:text-sky-200">All time</span>
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stock['total_purchased_qty'] - $stock['total_sold_qty'], 2) }}</p>
                    <div class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>Inbound Qty</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($stock['total_purchased_qty'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Outbound Qty</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($stock['total_sold_qty'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Available Stock</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($stock['available_stock'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm ring-1 ring-rose-100 stats-panel dark:bg-slate-900 dark:ring-rose-900/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Expenses Summary</p>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 dark:bg-rose-900/40 dark:text-rose-200">Expenses</span>
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($expenses['total_amount'], 2) }}</p>
                    <div class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>Total Expenses</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($expenses['total_expenses']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-indigo-50 to-white p-5 shadow-sm ring-1 ring-indigo-100 stats-panel dark:bg-slate-900 dark:ring-indigo-900/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Total Funds</p>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-200">Cash Pool</span>
                    </div>
                    <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($funds['current_total_funds'], 2) }}</p>
                    <div class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>Opening Funds</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($funds['opening_total_funds'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Sales Cash In</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($funds['sales_cash_inflow'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Purchases Cash Out</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($funds['purchases_cash_outflow'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Expenses Out</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($funds['expenses_outflow'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Payroll Out</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($funds['payroll_outflow'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Cash Flow Net</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($funds['cash_flow_net'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 stats-card dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700/40">
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">Top Performers</p>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-200">Top 3</span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm ring-1 ring-blue-100 dark:from-slate-950 dark:to-slate-950 dark:ring-blue-900/40">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Customers by Sales</p>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 dark:bg-blue-900/40 dark:text-blue-200">Sales</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse (($leaders['customers'] ?? []) as $index => $row)
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100 dark:bg-slate-800/80 dark:ring-slate-700/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">{{ $index + 1 }}</span>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($row['count']) }} sales</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($row['amount'], 2) }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No customer sales data available.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm ring-1 ring-emerald-100 dark:from-slate-950 dark:to-slate-950 dark:ring-emerald-900/40">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Vendors by Purchases</p>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-200">Purchases</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse (($leaders['vendors'] ?? []) as $index => $row)
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100 dark:bg-slate-800/80 dark:ring-slate-700/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">{{ $index + 1 }}</span>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($row['count']) }} purchases</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($row['amount'], 2) }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No vendor purchase data available.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm ring-1 ring-amber-100 dark:from-slate-950 dark:to-slate-950 dark:ring-amber-900/40">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Top Variants Sold</p>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-600 dark:bg-amber-900/40 dark:text-amber-200">Products</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse (($leaders['variants'] ?? []) as $index => $row)
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100 dark:bg-slate-800/80 dark:ring-slate-700/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">{{ $index + 1 }}</span>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $row['product'] }} • SKU: {{ $row['sku'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($row['qty'], 2) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">qty sold</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No product variant sales data available.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 stats-card dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700/40">
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">Credit Overview</p>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-200">Outstanding</span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-4">
                <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm ring-1 ring-blue-100 stats-panel stats-panel-blue dark:from-slate-950 dark:to-slate-950 dark:ring-blue-900/40 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Receivable from Customers</p>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 dark:bg-blue-900/40 dark:text-blue-200">Sales Credit</span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($credit['receivable_total'], 2) }}</p>
                    <div class="mt-4 space-y-3">
                        @forelse (($credit['top_customers'] ?? []) as $index => $row)
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100 dark:bg-slate-800/80 dark:ring-slate-700/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">{{ $index + 1 }}</span>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($row['count']) }} credit sales</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($row['amount'], 2) }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No customer credit data available.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm ring-1 ring-emerald-100 stats-panel stats-panel-emerald dark:from-slate-950 dark:to-slate-950 dark:ring-emerald-900/40 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Payable to Vendors</p>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-200">Purchase Credit</span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($credit['payable_total'], 2) }}</p>
                    <div class="mt-4 space-y-3">
                        @forelse (($credit['top_vendors'] ?? []) as $index => $row)
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100 dark:bg-slate-800/80 dark:ring-slate-700/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">{{ $index + 1 }}</span>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($row['count']) }} credit purchases</p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($row['amount'], 2) }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No vendor credit data available.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 stats-card dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700/40">
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">Business Pulse</p>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-200">Last 6 months</span>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-4">
                <div class="lg:col-span-3">
                    <div class="rounded-2xl bg-gradient-to-br from-blue-50 via-white to-emerald-50 p-6 ring-1 ring-gray-950/5 stats-panel dark:bg-slate-900 dark:ring-slate-700/40">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Sales vs Purchases</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Monthly trend</p>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <button type="button" class="flex items-center gap-2" @click="toggle('sales')" :class="showSales ? 'opacity-100' : 'opacity-40'">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>Sales
                                </button>
                                <button type="button" class="flex items-center gap-2" @click="toggle('purchases')" :class="showPurchases ? 'opacity-100' : 'opacity-40'">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>Purchases
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-xl bg-white/80 p-4 shadow-sm dark:bg-slate-900/80">
                            <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-64 w-full">
                                @foreach ($ticks as $tick)
                                    <line x1="{{ $axisLeft }}" x2="{{ $chartWidth - $axisRight }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="#e2e8f0" stroke-width="1"></line>
                                    <text x="2" y="{{ $tick['y'] + 4 }}" font-size="10" fill="#64748b">{{ number_format($tick['value']) }}</text>
                                @endforeach
                                <polyline points="{{ $purchasePoints }}" fill="none" stroke="#34d399" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" x-show="showPurchases"></polyline>
                                <polyline points="{{ $salesPoints }}" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" x-show="showSales"></polyline>
                                @foreach ($salesSeries as $index => $value)
                                    @php
                                        $x = $axisLeft + ($index * $step);
                                        $normalized = $maxValue > 0 ? $value / $maxValue : 0;
                                        $y = $axisTop + ($plotHeight - ($plotHeight * $normalized));
                                    @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="#3b82f6" x-show="showSales"></circle>
                                    <text x="{{ $x }}" y="{{ $y - 6 }}" text-anchor="middle" font-size="10" fill="#1e293b" x-show="showSales">{{ number_format($value) }}</text>
                                @endforeach
                                @foreach ($purchaseSeries as $index => $value)
                                    @php
                                        $x = $axisLeft + ($index * $step);
                                        $normalized = $maxValue > 0 ? $value / $maxValue : 0;
                                        $y = $axisTop + ($plotHeight - ($plotHeight * $normalized));
                                    @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="#34d399" x-show="showPurchases"></circle>
                                    <text x="{{ $x }}" y="{{ $y - 6 }}" text-anchor="middle" font-size="10" fill="#047857" x-show="showPurchases">{{ number_format($value) }}</text>
                                @endforeach

                                @foreach ($labels as $index => $label)
                                    @php $x = $axisLeft + ($index * $step); @endphp
                                    <text x="{{ $x }}" y="{{ $chartHeight - 6 }}" text-anchor="middle" font-size="10" fill="#64748b">{{ $label }}</text>
                                @endforeach
                            </svg>
                        </div>

                        <div class="mt-4 rounded-xl bg-white/80 p-4 shadow-sm dark:bg-slate-900/80">
                            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                                <span class="font-semibold text-slate-700 dark:text-slate-100">Monthly Volume (Bar)</span>
                                <div class="flex items-center gap-4">
                                    <span class="text-slate-500 dark:text-slate-400">Counts</span>
                                    <button type="button" class="flex items-center gap-2" @click="toggle('sales')" :class="showSales ? 'opacity-100' : 'opacity-40'">
                                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>Sales
                                    </button>
                                    <button type="button" class="flex items-center gap-2" @click="toggle('purchases')" :class="showPurchases ? 'opacity-100' : 'opacity-40'">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>Purchases
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-6 gap-3">
                                @foreach ($labels as $index => $label)
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="relative flex h-48 items-end gap-1">
                                            <div class="relative w-3 rounded-full bg-blue-500" style="height: {{ $barHeights['sales'][$index] ?? 0 }}%;" x-show="showSales">
                                                <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-semibold text-slate-700">{{ number_format($salesSeries[$index] ?? 0) }}</span>
                                            </div>
                                            <div class="relative w-3 rounded-full bg-emerald-500" style="height: {{ $barHeights['purchases'][$index] ?? 0 }}%;" x-show="showPurchases">
                                                <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-semibold text-slate-700">{{ number_format($purchaseSeries[$index] ?? 0) }}</span>
                                            </div>
                                        </div>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>


                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl bg-slate-900 p-5 text-white shadow-sm stats-dark-card">
                        <p class="text-xs uppercase tracking-wide text-slate-300">Sales Count</p>
                        <p class="mt-2 text-2xl font-semibold">{{ number_format($periodSalesTotal) }}</p>
                        <p class="mt-1 text-xs text-slate-300">All sales in the period</p>
                    </div>
                    <div class="rounded-xl bg-emerald-600 p-5 text-white shadow-sm stats-success-card">
                        <p class="text-xs uppercase tracking-wide text-emerald-100">Purchases Count</p>
                        <p class="mt-2 text-2xl font-semibold">{{ number_format($periodPurchasesTotal) }}</p>
                        <p class="mt-1 text-xs text-emerald-100">All purchases in the period</p>
                    </div>
                    <div class="rounded-xl bg-gradient-to-br from-slate-50 to-white p-5 shadow-sm ring-1 ring-gray-950/5 stats-panel dark:bg-slate-900 dark:ring-slate-700/40">
                        <p class="text-xs uppercase tracking-wide text-slate-700 dark:text-slate-200">Inventory Health</p>
                        <div class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                            <div class="flex items-center justify-between">
                                <span>Available Stock</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stock['available_stock'], 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Sold Qty</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stock['total_sold_qty'], 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Purchased Qty</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stock['total_purchased_qty'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm ring-1 ring-rose-100 stats-panel stats-panel-rose dark:bg-slate-900 dark:ring-rose-900/50">
                        <div class="flex items-center justify-between">
                            <p class="text-xs uppercase tracking-wide text-rose-600">Sale Returns</p>
                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-600">Sales</span>
                        </div>
                        <div class="mt-3 space-y-2 text-sm text-slate-700">
                            <div class="flex items-center justify-between">
                                <span>Total Returns</span>
                                <span class="font-semibold text-slate-900">{{ number_format($returns['sales']['total_returns']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Returned Qty</span>
                                <span class="font-semibold text-slate-900">{{ number_format($returns['sales']['total_quantity'], 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Total Amount</span>
                                <span class="font-semibold text-slate-900">{{ number_format($returns['sales']['total_amount'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm ring-1 ring-amber-100 stats-panel stats-panel-amber dark:bg-slate-900 dark:ring-amber-900/50">
                        <div class="flex items-center justify-between">
                            <p class="text-xs uppercase tracking-wide text-amber-600">Purchase Returns</p>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-600">Purchases</span>
                        </div>
                        <div class="mt-3 space-y-2 text-sm text-slate-700">
                            <div class="flex items-center justify-between">
                                <span>Total Returns</span>
                                <span class="font-semibold text-slate-900">{{ number_format($returns['purchases']['total_returns']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Returned Qty</span>
                                <span class="font-semibold text-slate-900">{{ number_format($returns['purchases']['total_quantity'], 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Total Amount</span>
                                <span class="font-semibold text-slate-900">{{ number_format($returns['purchases']['total_amount'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
