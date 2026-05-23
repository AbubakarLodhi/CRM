<x-filament-panels::page>
    {{ $this->table }}

    <div class="mt-8 reminder-templates-panel">
        <form wire:submit.prevent="saveReminders">
            <div class="reminder-templates-scroll">
                {{ $this->reminderForm }}
            </div>

            @if($this->userCanSaveReminders())
                <div class="reminder-templates-footer flex flex-wrap items-center gap-3">
                    <x-filament::button type="submit">
                        Save reminder templates
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-paper-airplane"
                        wire:click="pushCreditReminders"
                        wire:loading.attr="disabled"
                        wire:target="pushCreditReminders"
                        :disabled="! ($this->reminderData['reminders_enabled'] ?? false)"
                    >
                        <span wire:loading.remove wire:target="pushCreditReminders">Push reminders</span>
                        <span wire:loading wire:target="pushCreditReminders">Pushing…</span>
                    </x-filament::button>

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Push re-syncs dates and sends due emails immediately via SMTP (check terminal: php artisan credit-reminders:push).
                    </span>
                </div>
            @endif
        </form>
    </div>

    <style>
        .reminder-templates-panel {
            border-radius: 0.75rem;
        }

        .reminder-templates-scroll {
            max-height: min(28rem, 50vh);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.5rem;
            margin-right: -0.25rem;
        }

        .reminder-templates-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .reminder-templates-scroll::-webkit-scrollbar-track {
            background: rgb(241 245 249);
            border-radius: 6px;
        }

        .reminder-templates-scroll::-webkit-scrollbar-thumb {
            background: rgb(203 213 225);
            border-radius: 6px;
        }

        .reminder-templates-scroll::-webkit-scrollbar-thumb:hover {
            background: rgb(148 163 184);
        }

        .reminder-templates-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgb(226 232 240);
            flex-shrink: 0;
        }
    </style>
</x-filament-panels::page>
