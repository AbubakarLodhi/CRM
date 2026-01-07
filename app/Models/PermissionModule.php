<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PermissionModule extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'module',
        'label',
//        'is_enabled',
    ];

    public $incrementing = false;
    protected $keyType = 'string';


    public function merchants()
    {
        return $this->belongsToMany(
            Merchant::class,
            'merchant_permission_modules'
        )->withTimestamps();
    }

    public static function isEnabledForMerchant(string $module, string $merchantId): bool
    {
        return self::where('module', $module)
            ->whereHas('merchants', function ($query) use ($merchantId) {
                $query->where('merchant_id', $merchantId);
            })
            ->exists();
    }


    public static function enabledForCurrentMerchant(): array
    {
        $user = Filament::auth()->user();

        // Admin sees everything
        if ($user instanceof \App\Models\Admin) {
            return self::pluck('module')->toArray();
        }

        // Merchant → only enabled modules
        return $user->permissionModules()
            ->pluck('module')
            ->toArray();
    }

    public static function isEnabledForCurrentMerchant(string $module): bool
    {
        $user = Filament::auth()->user();
        if ($user instanceof \App\Models\Admin) {
            return true;
        }
        return $user->permissionModules()
            ->where('module', $module)
            ->exists();
    }
}

