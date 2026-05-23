<?php

namespace App\Filament\Resources\Activities\Support;

use App\Models\Merchant;
use App\Models\User;

class ActivityPerformer
{
    public static function resolve(object $record): string
    {
        // Polymorphic user relationship covers both Merchant and User
        if ($record->user?->name) {
            return (string) $record->user->name;
        }

        // For staff records where the relationship is missing, try to find via audit payload
        if (($record->user_type ?? null) === User::class) {
            $staffId = self::extractStaffIdFromAuditPayload($record);

            if ($staffId) {
                $staff = User::query()->find($staffId);

                if ($staff?->name) {
                    return (string) $staff->name;
                }
            }
        }

        return 'System';
    }

    protected static function extractStaffIdFromAuditPayload(object $record): ?string
    {
        $keys = ['updated_by', 'created_by', 'deleted_by', 'user_id'];

        $newValues = self::normalizeArray($record->new_values ?? null);
        $oldValues = self::normalizeArray($record->old_values ?? null);

        foreach ($keys as $key) {
            $candidate = $newValues[$key] ?? $oldValues[$key] ?? null;

            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    protected static function normalizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
