@php
    $merchant = \Filament\Facades\Filament::auth()->user() instanceof \App\Models\Merchant
        ? \Filament\Facades\Filament::auth()->user()
        : \Filament\Facades\Filament::auth()->user()?->merchant;

    $logo = $merchant?->logo?->photo_url;
@endphp

<div class="flex flex-col items-center space-y-2">
    @if ($logo)
        <img
            src="{{ asset('storage/' . $logo) }}"
            class="h-28 w-28 rounded-lg object-contain border"
        />
    @else
        <div class="h-28 w-28 border-2 border-dashed rounded-lg flex items-center justify-center text-sm">
            Upload logo
        </div>
    @endif

    <div class="text-lg font-semibold">
        {{ $merchant?->name }}
    </div>

    <div class="text-sm text-gray-500">
        {{ $merchant?->country ?? '' }}
    </div>
</div>
