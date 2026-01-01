<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        {{-- IDENTICAL footer spacing to Create / Edit resource pages --}}
        <div style="margin-top: 30px; display: flex; gap: 10px">

            <x-filament::button type="submit">
                Save changes
            </x-filament::button>

            <x-filament::button
                    color="gray"
                    tag="a"
                    href="{{ filament()->getHomeUrl() }}"
            >
                Cancel
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
