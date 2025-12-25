<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MerchantSetting extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id','logo_path','primary_color','secondary_color','currency','timezone'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
