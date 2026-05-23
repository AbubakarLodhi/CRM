<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MerchantPermissionModule extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
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
