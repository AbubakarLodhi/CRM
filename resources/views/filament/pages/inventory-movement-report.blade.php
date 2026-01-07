<x-filament::page>

    {{-- FILTERS --}}
    <form wire:submit.prevent class="mb-6">
        {{ $this->form }}
    </form>

    {{-- STATS --}}
    @php($stats = $this->getMovementStats())

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">

        {{-- Total In --}}
        <div class="rounded-xl  bg-white px-4 py-3 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4 text-success-500" />
                <span>Total In</span>
            </div>
            <div class="mt-1 text-xl font-semibold text-success-600">
                {{ number_format($stats['in']) }}
            </div>
        </div>

        {{-- Total Out --}}
        <div class="rounded-xl  bg-white px-4 py-3 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                <x-heroicon-o-arrow-up-tray class="h-4 w-4 text-danger-500" />
                <span>Total Out</span>
            </div>
            <div class="mt-1 text-xl font-semibold text-danger-600">
                {{ number_format($stats['out']) }}
            </div>
        </div>

        {{-- Net Movement --}}
        <div class="rounded-xl  bg-white px-4 py-3 shadow-sm">
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

</x-filament::page>
