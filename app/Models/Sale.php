<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Sale extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id', 'business_id', 'branch_id', 'customer_id', 'sale_no', 'sale_date', 'subtotal', 'discount', 'tax', 'total_amount', 'notes', 'created_by'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
