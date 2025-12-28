<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchaseItem extends Model
{
    use HasUuids;

    /** @var string[] $fillable */
    protected $fillable = ['purchase_id','product_id','quantity','unit_price','line_total'];

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /** @var bool $timestamps */
    public $timestamps = false;
}
