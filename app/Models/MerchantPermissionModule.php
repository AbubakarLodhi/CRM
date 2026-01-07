<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MerchantPermissionModule extends Model
{
    use HasUuids;

    protected $table = 'merchant_permission_modules';

    protected $fillable = [
        'id',
        'merchant_id',
        'permission_module_id',
    ];

    public $incrementing = false;
    protected $keyType = 'string';


}
