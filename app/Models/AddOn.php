<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AddOn extends Model
{
    use HasUuids;
    protected $fillable = ['merchant_id', 'brand_model_id', 'name', 'price'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function model()
    {
        return $this->belongsTo(BrandModel::class, 'brand_model_id');
    }
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}

