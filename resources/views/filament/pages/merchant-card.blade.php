@php
    $merchant = \Filament\Facades\Filament::auth()->user() instanceof \App\Models\Merchant
        ? \Filament\Facades\Filament::auth()->user()
        : \Filament\Facades\Filament::auth()->user()?->merchant;

    $logo = $merchant?->logo?->photo_url;
@endphp

<div class="flex flex-col items-center space-y-2 text-center">
    @if ($logo)
        <div class="h-24 w-24 flex items-center justify-center overflow-hidden rounded-lg bg-white shadow-sm">
            <img
                src="{{ asset('storage/' . $logo) }}"
                class="h-full w-full object-contain"
                alt="Merchant Logo"
            />
        </div>
    @else
        <div class="h-24 w-24 border-2 border-dashed rounded-lg flex items-center justify-center text-sm text-gray-400">
            Upload logo
        </div>
    @endif

    <div class="max-w-[12rem] text-base font-semibold leading-tight break-words">
        {{ $merchant?->name }}
    </div>
</div>
