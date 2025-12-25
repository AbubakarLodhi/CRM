<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SaleItem extends Model
{
    use HasUuids;

    protected $fillable = ['sale_id','product_id','quantity','unit_price','line_total'];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
