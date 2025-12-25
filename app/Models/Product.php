<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id', 'business_id', 'name', 'sku', 'description', 'purchase_price', 'selling_price', 'is_active'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
