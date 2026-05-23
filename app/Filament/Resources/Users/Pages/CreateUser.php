<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Branch;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // 🔒 FINAL SAFETY
        if ($data['status'] === User::STATUS_VERIFIED) {
            $data['email_verified_at'] = now();
        } else {
            $data['email_verified_at'] = null;
        }

        return $data;
    }


    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        /** -------------------------------
         * Sync Branches
         * -------------------------------- */
        if (! empty($data['branches'])) {
            foreach ($data['branches'] as $branchId) {
                $this->record->branches()->attach($branchId, [
                    'id' => Str::uuid(),
                ]);
            }

            /** -------------------------------
             * Derive Businesses from Branches
             * -------------------------------- */
            $businessIds = Branch::whereIn('id', $data['branches'])
                ->pluck('business_id')
                ->unique()
                ->values();

            foreach ($businessIds as $businessId) {
                $this->record->businesses()->attach($businessId, [
                    'id' => Str::uuid(),
                ]);
            }
        }
    }

}
