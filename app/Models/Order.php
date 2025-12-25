<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id', 'business_id', 'branch_id', 'sale_id', 'status', 'status_notes'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
