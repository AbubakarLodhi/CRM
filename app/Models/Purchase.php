<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Purchase extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id', 'business_id', 'branch_id', 'purchase_no', 'purchase_date', 'subtotal', 'discount', 'tax', 'total_amount', 'notes', 'created_by'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
