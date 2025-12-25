<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RolesResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateRoles extends CreateRecord
{
    protected static string $resource = RolesResource::class;


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $permissions = $data['permissions'] ?? [];
        $data['guard_name'] = \Filament\Facades\Filament::getCurrentPanel()->getAuthGuard();
        unset($data['permissions']);

        return DB::transaction(function () use ($data, $permissions) {
            $record = static::getModel()::create([
                'name' => $data['name'],
                'guard_name' =>$data['guard_name'],
            ]);

            RolesResource::afterCreate($record, $permissions);

            return $record;
        }, 3);

    }
}
