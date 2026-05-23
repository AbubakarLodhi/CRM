<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PermissionModule extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
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

        // Merchant panel
        if ($user instanceof \App\Models\Merchant) {
            return $user->permissionModules()
                ->pluck('module')
                ->toArray();
        }

        // Staff panel → inherit from merchant
        if ($user instanceof \App\Models\User && $user->merchant) {
            return $user->merchant
                ->permissionModules()
                ->pluck('module')
                ->toArray();
        }

        return [];
    }


    public static function isEnabledForCurrentMerchant(string $module): bool
    {
        $user = Filament::auth()->user();

        // ✅ Merchant owns modules
        if ($user instanceof \App\Models\Merchant) {
            return $user->permissionModules()
                ->where('module', $module)
                ->exists();
        }

        // ✅ Staff inherits from merchant
        if ($user instanceof \App\Models\User) {
            return optional($user->merchant)
                ->permissionModules()
                ->where('module', $module)
                ->exists() ?? false;
        }

        return false;
    }
}

