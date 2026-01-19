<x-filament::page>

    @php
        $user  = \Filament\Facades\Filament::auth()->user();
        $guard = \Filament\Facades\Filament::getCurrentPanel()->getAuthGuard();


        $canView = $user
            && \App\Models\PermissionModule::isEnabledForCurrentMerchant('reports')
            && (
                $user->hasPermissionTo('reports.view', $guard)
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
                    Inventory Movement Locked
                </h2>

                <p class="mt-3 text-sm text-gray-500">
                    You don’t have permission to view inventory movements.
                    Please ensure you have access to Sales or Purchases,
                    or contact your administrator.
                </p>

            </div>
        </div>

        {{-- ========================= --}}
        {{-- MOVEMENT CONTENT --}}
        {{-- ========================= --}}
    @else

        {{-- FILTERS --}}
        <form wire:submit.prevent class="mb-6">
            {{ $this->form }}
        </form>

        {{-- STATS --}}
        @php($stats = $this->getMovementStats())

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Total In --}}
            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <x-heroicon-o-arrow-down-tray class="h-4 w-4 text-success-500" />
                    <span>Total In</span>
                </div>
                <div class="mt-1 text-xl font-semibold text-success-600">
                    {{ number_format($stats['in']) }}
                </div>
            </div>

            {{-- Total Out --}}
            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4 text-danger-500" />
                    <span>Total Out</span>
                </div>
                <div class="mt-1 text-xl font-semibold text-danger-600">
                    {{ number_format($stats['out']) }}
                </div>
            </div>

            {{-- Net Movement --}}
            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <x-heroicon-o-arrows-right-left
                        class="h-4 w-4 {{ $stats['net'] < 0 ? 'text-danger-500' : 'text-success-500' }}"
                    />
                    <span>Net Movement</span>
                </div>
                <div class="mt-1 text-xl font-semibold {{ $stats['net'] < 0 ? 'text-danger-600' : 'text-success-600' }}">
                    {{ number_format($stats['net']) }}
                </div>
            </div>

        </div>

        {{-- TABLE --}}
        {{ $this->table }}

    @endif

</x-filament::page>
